<?php

namespace App\Http\Controllers\Admin;

use App\Models\Chat;
use Gate;
use App\Models\Pemproses;
use App\Models\Produk;
use App\Models\Member;
use App\Models\Gaji;
use App\Models\Produksi;
use App\Models\ProjectMp;
use App\Services\StokService;
use Illuminate\Http\Request;
use App\Models\ProjectMpDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class ProjectMpDetailController extends Controller
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

    private function applyProduksiStatus(ProjectMpDetail $detail, int $produksiId): void
    {
        DB::transaction(function () use ($detail, $produksiId) {
            $produk = $detail->produk;

            if ($produk?->produkModel?->stok == 1) {
                $awalProduksi = Produksi::find($detail->produksi_id);
                $perubahanProduksi = Produksi::find($produksiId);

                if ($awalProduksi && $perubahanProduksi) {
                    $awal = $awalProduksi->grup;
                    $perubahan = $perubahanProduksi->grup;

                    if ($detail->projectMp?->konsumen) {
                        $username = '(' . $detail->projectMp->konsumen . ')';
                    } else {
                        $username = '';
                    }

                    $stokService = app(StokService::class);

                    if ($awal == 'awal' and $perubahan != 'awal' and $perubahan != 'batal') {
                        $stokService->kurang(
                            $produk->id,
                            $detail->jumlah,
                            'jual',
                            'barang dijual ke ' . ($detail->projectMp?->marketplace?->nama ?? '-') . ' ' . $username,
                            $detail->projectMp?->id,
                            [],
                            false
                        );
                    }
                    if ($awal == 'selesai' and $perubahan == 'batal') {
                        $stokService->tambah(
                            $produk->id,
                            $detail->jumlah,
                            'btl',
                            'barang dikembalikan dari ' . ($detail->projectMp?->kontak?->nama ?? '-') . ' ' . $username,
                            $detail->projectMp?->id
                        );
                    }
                }
            }

            $detail->update([
                'produksi_id' => $produksiId,
                'hpp' => $produk?->hpp,
            ]);
        });
    }

    public function detail(Request $request, $projectMp)
    {
        $projectMp = ProjectMp::find($projectMp);
        $projectMpdetails = ProjectMpDetail::where('project_id', $projectMp->id)
            ->with(['produksi', 'pemproses', 'produk', 'projectMp.buffer'])
            ->get();
        $marketplace = $projectMp->marketplace;

        $produksi = Produksi::orderBy('urutan')->get();
        $pemproses = Pemproses::orderBy('nama')->get();
        $chats = Chat::where('project_mp_id', $projectMp->id)->get();

        $isMarketingOnly = $this->isMarketingOnly();
        $canEditLimited = ! $isMarketingOnly;
        $isProduksiLevel = $this->isProduksiLevel();

        return view('admin.projectmps.detail', compact(
            'projectMp',
            'marketplace',
            'projectMpdetails',
            'produksi',
            'pemproses',
            'chats',
            'isMarketingOnly',
            'canEditLimited',
            'isProduksiLevel'
        ));
    }

    public function create(ProjectMp $projectMp)
    {
        return view('admin.projectmps.createDetail', compact('projectMp'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'produk_id' => 'required',
            'harga' => 'required',
            'jumlah' => 'required',
            'deadline' => 'required',
        ]);

        $produksi = Produksi::where('nama', 'persiapan')->first();
        $produk = Produk::find($request->produk_id);

        ProjectMpDetail::create([
            'project_id' => $request->project_id,
            'produk_id' => $request->produk_id,
            'tema' => $request->tema,
            'jumlah' => $request->jumlah,
            'harga' => $request->harga,
            'keterangan' => $request->keterangan,
            'produksi_id' => $produksi?->id,
            'deadline' => $request->deadline,
            'nota' => $request->nota,
            'hpp' => $produk?->hpp,
            'created_at' => Carbon::now(),
        ]);

        $total = ProjectMpDetail::where('project_id', $request->project_id)
            ->selectRaw('SUM(harga * jumlah) as total')
            ->value('total');

        ProjectMp::where('id', $request->project_id)->update(['total' => $total ?? 0]);

        return redirect()->route('projectmp.detail', $request->project_id)
            ->withSuccess(__('Project Detail created successfully.'));
    }

    public function updateStatus(Request $request, ProjectMpDetail $projectMp)
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if($this->isMarketingOnly(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->applyProduksiStatus($projectMp, (int) $request->produksi_id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('Status updated successfully.')]);
        }

        return redirect('/admin/projectMpDetail/' . $projectMp->projectMp->id)->withSuccess(__('Status updated successfully.'));
    }

    public function advanceStatus(Request $request, ProjectMpDetail $detail)
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->isProduksiLevel(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $nextProduksi = $detail->produksi?->nextInFlow();
        if (! $nextProduksi) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('Tidak ada proses selanjutnya.')], 422);
            }

            return redirect()->back()->withErrors(__('Tidak ada proses selanjutnya.'));
        }

        $this->applyProduksiStatus($detail, $nextProduksi->id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => __('Status updated successfully.'),
                'produksi' => $nextProduksi->nama,
            ]);
        }

        return redirect()->back()->withSuccess(__('Status updated successfully.'));
    }

    public function updatePemproses(Request $request, ProjectMpDetail $detail)
    {
        $detail->update([
            'pemproses_id' => $request->pemproses_id ?: null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('Pemproses updated successfully.')]);
        }

        return redirect('/admin/projectMpDetail/' . $detail->projectMp->id)->withSuccess(__('Pemproses updated successfully.'));
    }

    public function gambar(ProjectMpDetail $detail)
    {
        return view('admin.projectmps.gambar', compact('detail'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'gambar' => 'required|mimes:jpeg,png,jpg',
        ]);

        $ProjectMpDetail = ProjectMpDetail::find($request->ProjectMp_detail_id);
        $gambar = null;
        if ($request->hasFile('gambar')) {
            $img = $request->file('gambar');
            $filename = time() . '.' . $request->gambar->extension();
            $img_resize = Image::make($img->getRealPath());
            $img_resize->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $save_path = public_path('uploads/projectMp/');
            if (!file_exists($save_path)) {
                try {
                    mkdir($save_path, 0755, true);
                } catch (\Exception $e) {
                    throw new \Exception('Unable to create directory. Please check folder permissions.');
                }
            }
            $img_resize->save($save_path . $filename);
            $gambar = $filename;
        }

        $ProjectMpDetail->update([
            'gambar' => $gambar,
        ]);

        return redirect('/admin/projectMpDetail/' . $ProjectMpDetail->projectMp->id)->withSuccess(__('Gambar detail updated successfully.'));
    }
    public function editGambar(ProjectMpDetail $detail)
    {
        return view('admin.projectmps.editGambar', compact('detail'));
    }

    public function updateGambar(Request $request)
    {
        $request->validate([
            'gambar' => 'required|mimes:jpeg,png,jpg',
        ]);

        $ProjectMpDetail = ProjectMpDetail::find($request->ProjectMp_detail_id);
        $gambar = null;
        if ($request->hasFile('gambar')) {
            $img = $request->file('gambar');
            $filename = time() . '.' . $request->gambar->extension();
            $img_resize = Image::make($img->getRealPath());
            $img_resize->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $save_path = public_path('uploads/projectMp/');
            if (!file_exists($save_path)) {
                try {
                    mkdir($save_path, 0755, true);
                } catch (\Exception $e) {
                    throw new \Exception('Unable to create directory. Please check folder permissions.');
                }
            }
            $img_resize->save($save_path . $filename);
            $gambar = $filename;
        }

        if ($ProjectMpDetail->gambar) {
            unlink("uploads/projectMp/" . $ProjectMpDetail->gambar);
        }

        $ProjectMpDetail->update([
            'gambar' => $gambar,
        ]);

        return redirect('/admin/projectMpDetail/' . $ProjectMpDetail->projectMp->id)->withSuccess(__('Gambar detail updated successfully.'));
    }

    public function edit(ProjectMpDetail $detail)
    {
        return view('admin.projectmps.editDetail', compact('detail'));
    }

    public function update(Request $request, ProjectMpDetail $detail)
    {
        $detail->update($request->all());
        $detail->projectMp->update([
            'deadline' => $request->deadline,
        ]);
        return redirect('/admin/projectMpDetail/' . $detail->projectMp->id)->withSuccess(__('Detail updated successfully.'));
    }
}
