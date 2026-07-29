<?php

namespace App\Http\Controllers\Admin;

use App\Models\Chat;
use App\Models\Kontak;
use App\Models\Order;
use App\Models\Pemproses;
use App\Models\Member;
use App\Models\Gaji;
use App\Models\Produk;
use App\Models\Spek;
use App\Models\Produksi;
use App\Models\ProjectMp;
use App\Services\StokService;
use Illuminate\Http\Request;
use App\Models\OrderDetail;
use App\Models\ProjectMpDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    private function canAddOrderProduk(): bool
    {
        return $this->hasRoleInsensitive('super', 'manager', 'supervisor', 'cs_online');
    }

    private function authorizeAddOrderProduk(): void
    {
        abort_if(! $this->canAddOrderProduk(), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function resolveKontakFor(ProjectMp $projectMp): Kontak
    {
        $namaPembeli = trim((string) $projectMp->konsumen) ?: (string) $projectMp->nota;

        $kontak = Kontak::where('konsumen', 1)
            ->whereRaw('LOWER(nama) = ?', [strtolower($namaPembeli)])
            ->first();

        if ($kontak) {
            return $kontak;
        }

        $slug = Str::slug($namaPembeli, '.') ?: 'konsumen-' . $projectMp->id;
        $email = $slug . '@projectmp.local';

        try {
            return Kontak::create([
                'nama' => $namaPembeli,
                'noTelp' => '-',
                'email' => $email,
                'konsumen' => 1,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // email bentrok (nama sama persis dengan kontak lain) -> tambahkan suffix unik
            return Kontak::create([
                'nama' => $namaPembeli,
                'noTelp' => '-',
                'email' => $slug . '-' . $projectMp->id . '@projectmp.local',
                'konsumen' => 1,
            ]);
        }
    }

    private function resolveOrderFor(ProjectMp $projectMp): Order
    {
        if ($projectMp->order_id && $projectMp->order) {
            return $projectMp->order;
        }

        $kontak = $this->resolveKontakFor($projectMp);

        $order = Order::create([
            'kontak_id' => $kontak->id,
            'nota' => $projectMp->nota,
            'marketplace' => null,
            'deathline' => $projectMp->deadline,
        ]);

        $projectMp->update(['order_id' => $order->id]);

        return $order;
    }

    private function isAllowedProduksiStatus(ProjectMpDetail $detail, int $produksiId): bool
    {
        $allowedIds = Produksi::statusPathForDetail($detail)->pluck('id');

        if ($detail->produksi_id) {
            $allowedIds->push($detail->produksi_id);
        }

        return $allowedIds->unique()->contains($produksiId);
    }

    private function applyProduksiStatus(ProjectMpDetail $detail, int $produksiId): void
    {
        DB::transaction(function () use ($detail, $produksiId) {
            $detail->loadMissing(['produk.produkModel', 'projectMp.marketplace']);

            $from = Produksi::find($detail->produksi_id);
            $to = Produksi::find($produksiId);

            if (Produksi::produkTracksStock($detail) && $from && $to) {
                if ($detail->projectMp?->konsumen) {
                    $username = '(' . $detail->projectMp->konsumen . ')';
                } else {
                    $username = '';
                }

                $stokService = app(StokService::class);

                if (Produksi::shouldDeductStock($from, $to)) {
                    $stokService->kurang(
                        $detail->produk->id,
                        $detail->jumlah,
                        'jual',
                        'barang dijual ke ' . ($detail->projectMp?->marketplace?->nama ?? '-') . ' ' . $username,
                        $detail->projectMp?->id,
                        [],
                        false
                    );
                }

                if (Produksi::shouldRestoreStock($from, $to)) {
                    $stokService->tambah(
                        $detail->produk->id,
                        $detail->jumlah,
                        'btl',
                        'barang dikembalikan dari ' . ($detail->projectMp?->kontak?->nama ?? '-') . ' ' . $username,
                        $detail->projectMp?->id
                    );
                }
            }

            $detail->update([
                'produksi_id' => $produksiId,
                'hpp' => $detail->produk?->hpp,
            ]);
        });
    }

    public function detail(Request $request, ProjectMp $projectMp)
    {
        $projectMpdetails = $projectMp->details()
            ->with(['produk.produkModel.kategori.kategoriUtama', 'produksi', 'pemproses', 'projectMp.buffer'])
            ->get();
        $marketplace = $projectMp->marketplace;

        $produksi = Produksi::orderedForStatusSelect();
        $pemproses = Pemproses::orderBy('nama')->get();
        $chats = Chat::where('project_mp_id', $projectMp->id)->get();

        $isMarketingOnly = $this->isMarketingOnly();
        $canEditLimited = ! $isMarketingOnly;
        $isProduksiLevel = $this->isProduksiLevel();
        $canAddOrderProduk = $this->canAddOrderProduk();

        $projectMp->loadMissing(
            'order.orderDetail.produk.produkModel.kategori.kategoriUtama',
            'order.orderDetail.spek',
            'order.orderDetail.produksi',
            'order.orderDetail.pemproses'
        );
        $orderDetails = $projectMp->order?->orderDetail ?? collect();

        return view('admin.projectmps.detail', compact(
            'projectMp',
            'marketplace',
            'projectMpdetails',
            'produksi',
            'pemproses',
            'chats',
            'isMarketingOnly',
            'canEditLimited',
            'isProduksiLevel',
            'canAddOrderProduk',
            'orderDetails'
        ));
    }

    public function createOrderProduk(ProjectMp $projectMp)
    {
        $this->authorizeAddOrderProduk();

        $speks = Spek::all();

        return view('admin.projectmps.createOrderDetail', compact('projectMp', 'speks'));
    }

    public function storeOrderProduk(Request $request)
    {
        $this->authorizeAddOrderProduk();

        $request->validate([
            'project_mp_id' => 'required|exists:project_mps,id',
            'produk_id' => 'required',
            'harga' => 'required',
            'jumlah' => 'required',
            'deathline' => 'required',
        ]);

        $projectMp = ProjectMp::findOrFail($request->project_mp_id);
        $order = $this->resolveOrderFor($projectMp);

        $produksi = Produksi::initialStatus();
        $produk = Produk::find($request->produk_id);

        $orderDetail = OrderDetail::create([
            'order_id' => $order->id,
            'produk_id' => $request->produk_id,
            'tema' => $request->tema,
            'jumlah' => $request->jumlah,
            'harga' => $request->harga,
            'keterangan' => $request->keterangan,
            'produksi_id' => $produksi?->id,
            'deathline' => $request->deathline,
            'nota' => $order->nota,
            'hpp' => $produk?->hpp,
            'created_at' => Carbon::now(),
        ]);

        $speks = Spek::all();
        $sync = [];
        foreach ($speks as $spek) {
            if ($request->{$spek->nama}) {
                $sync[$spek->id] = ['keterangan' => $request->{$spek->nama}];
            }
        }
        $orderDetail->spek()->sync($sync);

        return redirect()->route('projectmp.detail', $projectMp->id)
            ->withSuccess(__('Produk tambahan berhasil ditambahkan ke order.'));
    }

    public function updateStatus(Request $request, ProjectMpDetail $projectMp)
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if($this->isMarketingOnly(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $produksiId = (int) $request->produksi_id;
        if (! $this->isAllowedProduksiStatus($projectMp, $produksiId)) {
            $message = __('Status tidak sesuai alur produksi.');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return redirect()->back()->withErrors($message);
        }

        $this->applyProduksiStatus($projectMp, $produksiId);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('Status updated successfully.')]);
        }

        return redirect()->back()->withSuccess(__('Status updated successfully.'));
    }

    public function advanceStatus(Request $request, ProjectMpDetail $detail)
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->isProduksiLevel(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $nextProduksi = $detail->produksi?->nextInFlow($detail);
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
