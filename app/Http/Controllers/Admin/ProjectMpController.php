<?php

namespace App\Http\Controllers\Admin;

use App\Models\Chat;
use App\Models\Order;
use App\Models\Member;
use App\Models\Gaji;
use App\Models\Produksi;
use App\Models\ProjectMp;
use App\Models\Marketplace;
use Illuminate\Http\Request;
use App\Models\ProjectMpDetail;
use App\Models\MarketplaceBuffer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProjectMpController extends Controller
{
    private function hasRoleInsensitive(string ...$names): bool
    {
        $normalized = collect($names)->map(fn ($name) => strtolower($name));

        return auth()->user()->roles->contains(
            fn ($role) => $normalized->contains(strtolower($role->name))
        );
    }

    private function isMarketingOnly(): bool
    {
        return $this->hasRoleInsensitive('marketing')
            && ! $this->hasRoleInsensitive('supervisor', 'super', 'manager');
    }

    private function isProduksiLevel(): bool
    {
        if ($this->hasRoleInsensitive('supervisor', 'super', 'manager')) {
            return false;
        }

        if ($this->hasRoleInsensitive('produksi')) {
            return true;
        }

        $member = Member::where('user_id', auth()->id())->first();
        if (! $member) {
            return false;
        }

        $gaji = Gaji::with(['bagian', 'level'])->where('member_id', $member->id)->orderByDesc('id')->first();
        $bagianNama = strtolower($gaji?->bagian?->nama ?? '');
        $levelNama = strtolower($gaji?->level?->nama ?? '');

        return $bagianNama === 'produksi' || $levelNama === 'produksi';
    }

    /**
     * Dashboard untuk order marketplace custom (seperti OrderController dashboard)
     */
    public function dashboard()
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $produksis = Produksi::orderBy('grup')->orderBy('urutan')->get()
            ->groupBy('grup')
            ->sortKeysUsing(function ($a, $b) {
                if ($a === 'batal') {
                    return 1;
                }
                if ($b === 'batal') {
                    return -1;
                }

                return strcmp($a ?? '', $b ?? '');
            });

        $mps = ['semua' => 'Semua'];
        foreach (Marketplace::orderBy('nama')->pluck('nama', 'nama') as $key => $value) {
            $mps[str_replace(' ', '_', $key)] = str_replace(' ', '_', $value);
        }

        $isProduksiLevel = $this->isProduksiLevel();
        $dashboardData = $this->loadDashboardData($isProduksiLevel);

        return view('admin.projectmps.dashboard', array_merge(
            compact('produksis', 'mps', 'isProduksiLevel'),
            $dashboardData
        ));
    }

    private function loadDashboardData(bool $isProduksiLevel): array
    {
        $details = ProjectMpDetail::query()
            ->forDashboardCustom()
            ->whereNotNull('project_id')
            ->with([
                'projectMp.buffer',
                'projectMp.marketplace',
                'produk.produkModel',
                'pemproses',
                'produksi',
            ])
            ->orderBy('project_id')
            ->get();

        $detailsByProduksiId = $details->groupBy('produksi_id');
        $countsByProduksiId = $details->countBy('produksi_id');

        $nextProduksiById = [];
        if ($isProduksiLevel) {
            $flow = Produksi::flowItems();
            foreach ($flow as $index => $produksi) {
                $next = $flow->get($index + 1);
                if ($next) {
                    $nextProduksiById[$produksi->id] = $next;
                }
            }
        }

        return compact('detailsByProduksiId', 'countsByProduksiId', 'nextProduksiById');
    }

    /**
     * Daftar buffer marketplace yang belum terhubung ke project_mp
     */
    public function bufferPending()
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $buffers = MarketplaceBuffer::whereNull('marketplace_buffers.project_id')
            ->leftJoin('marketplaces', 'marketplaces.shop_id', '=', 'marketplace_buffers.shop_id')
            ->select(
                'marketplace_buffers.*',
                'marketplaces.nama as nama_marketplace'
            )
            ->orderBy('marketplace_buffers.created_at', 'desc')
            ->paginate(50);

        return view('admin.projectmps.bufferPending', compact('buffers'));
    }

    /**
     * Dashboard untuk packing (non-custom) berdasarkan status
     */
    public function packing()
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Ambil data marketplace buffer dengan relasi (packing = non-custom)
        $bufferData = MarketplaceBuffer::detail()->packing()->get();

        // Group data by status dan project_id
        $marketplaces = $this->group2level($bufferData, 'statusMp', 'project_id');

        // Ambil list marketplace untuk filter
        $mps = ['semua' => 'Semua'];
        $config = Marketplace::pluck('nama', 'nama');
        foreach ($config as $key => $value) {
            $mps[str_replace(' ', '_', $key)] = str_replace(' ', '_', $value);
        }

        // Status yang akan ditampilkan sebagai tab
        $statuses = [
            'READY_TO_SHIP' => ['nama' => 'Perlu diProses', 'warna' => '#28a745'],
            'PROCESSED' => ['nama' => 'Telah diproses', 'warna' => '#ffc107'],
        ];

        return view('admin.projectmps.packing', compact('marketplaces', 'mps', 'statuses'));
    }

    /**
     * Group collection by 2 level keys
     */
    private function group2level($obj, $key, $key2)
    {
        $hasil = [];
        foreach ($obj as $detail) {
            $hasil[$detail->$key][$detail->$key2][] = $detail;
        }
        return $hasil;
    }

    public function storeChat(Request $request, ProjectMp $projectmp)
    {
        $member = Member::where('user_id', auth()->user()->id)->first();

        Chat::create([
            'isi' => $request->isi,
            'member_id' => $member?->id,
            'user_id' => $member ? null : auth()->id(),
            'project_mp_id' => $projectmp->id,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('chat created successfully.')]);
        }

        return redirect('/admin/projectMpDetail/' . $projectmp->id)->withSuccess(__('chat created successfully.'));
    }

    public function index(Request $request)
    {
        // Tentukan sorting berdasarkan parameter
        $sortBy = 'created_at';
        $sortDirection = 'desc';

        if ($request->sort) {
            switch ($request->sort) {
                case 'total_asc':
                    $sortBy = 'total';
                    $sortDirection = 'asc';
                    break;
                case 'total_desc':
                    $sortBy = 'total';
                    $sortDirection = 'desc';
                    break;
                case 'bersih_asc':
                    $sortBy = 'bersih';
                    $sortDirection = 'asc';
                    break;
                case 'bersih_desc':
                    $sortBy = 'bersih';
                    $sortDirection = 'desc';
                    break;
                case 'persentase_asc':
                    // Untuk persentase, kita perlu menghitung (total - bayar) / total * 100
                    // Kita akan handle ini dengan raw query
                    break;
                case 'persentase_desc':
                    // Untuk persentase, kita perlu menghitung (total - bayar) / total * 100
                    // Kita akan handle ini dengan raw query
                    break;
            }
        }

        if ($request->dari == null && $request->sampai == null && $request->nota == null && $request->produk_id == null && $request->pembayaran == null && $request->marketplace_id == null) {
            // Jika hanya ada sorting tanpa filter lain
            if ($request->sort) {
                $query = ProjectMp::query();

                if ($request->sort == 'persentase_asc' || $request->sort == 'persentase_desc') {
                    $direction = $request->sort == 'persentase_asc' ? 'asc' : 'desc';
                    $query->orderBy('persen', $direction);
                } else {
                    $query->orderBy($sortBy, $sortDirection);
                }

                $projectmps = $query->with('marketplace')->paginate(10)->appends(['sort' => $request->sort]);
            } else {
                $projectmps = ProjectMp::with('marketplace')->orderBy('created_at', 'desc')->paginate(10);
            }
        } else {
            // Gunakan subquery untuk menghindari masalah pagination dengan JOIN dan DISTINCT
            $projectmpIds = ProjectMp::query()
                ->leftJoin('project_mp_details', 'project_mps.id', '=', 'project_mp_details.project_id')
                ->when($request->dari && $request->sampai, function ($query) use ($request) {
                    $query->whereBetween('project_mps.created_at', [$request->dari, $request->sampai]);
                })
                ->when($request->marketplace_id, function ($query) use ($request) {
                    $query->where('project_mps.marketplace_id', $request->marketplace_id);
                })
                ->when($request->nota, function ($query) use ($request) {
                    $query->where('project_mps.nota', 'LIKE', '%' . $request->nota . '%');
                })
                ->when($request->produk_id, function ($query) use ($request) {
                    $query->where('project_mp_details.produk_id', $request->produk_id);
                })
                ->when($request->pembayaran == '1', function ($query) use ($request) {
                    // Jika pembayaran = 1 (sudah dibayar), filter yang bayar >= total
                    $query->whereRaw('project_mps.bersih > 0');
                })
                ->when($request->pembayaran == '0', function ($query) use ($request) {
                    // Jika pembayaran = 0 (belum dibayar), filter yang bayar < total atau bersih null
                    $query->whereRaw('(project_mps.bersih = 0 OR project_mps.bersih IS NULL) and project_mps.total > 0');
                })
                ->distinct()
                ->pluck('project_mps.id');

            // Query utama untuk pagination
            $query = ProjectMp::with('marketplace')->whereIn('id', $projectmpIds);

            // Handle sorting untuk persentase
            if ($request->sort == 'persentase_asc' || $request->sort == 'persentase_desc') {
                $direction = $request->sort == 'persentase_asc' ? 'asc' : 'desc';
                $query->orderByRaw("CASE WHEN total = 0 THEN 0 ELSE ((total - bersih) / total * 100) END {$direction}");
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            $projectmps = $query->paginate(10)
                ->appends([
                    'dari' => $request->dari,
                    'sampai' => $request->sampai,
                    'marketplace_id' => $request->marketplace_id,
                    'nota' => $request->nota,
                    'produk_id' => $request->produk_id,
                    'pembayaran' => $request->pembayaran,
                    'sort' => $request->sort
                ]);
        }

        $marketplaces = Marketplace::orderBy('nama')->get();

        return view('admin.projectmps.index', compact('projectmps', 'marketplaces'));
    }
}
