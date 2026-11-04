<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GagalProduksi;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GagalProduksiController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('gagal_produksi_access'), 403);

        $data = $request->validate(['tanggal' => 'nullable|date_format:Y-m-d']);
        $tanggal = $data['tanggal'] ?? now()->toDateString();

        $query = GagalProduksi::query()->whereDate('tanggal', $tanggal);
        $cabangFilter = cabang_id() ?: (can_edit_lintas_cabang() ? null : member_cabang_id());
        if ($cabangFilter) {
            $query->where(function ($query) use ($cabangFilter) {
                $query->where('cabang_id', $cabangFilter)
                    ->orWhere(function ($lama) use ($cabangFilter) {
                        $lama->whereNull('cabang_id')->whereHas('orderDetail', function ($detail) use ($cabangFilter) {
                            $detail->withTrashed()->whereHas('order', fn ($order) => $order->where('orders.cabang_id', $cabangFilter));
                        });
                    });
            });
        } elseif (!can_edit_lintas_cabang()) {
            $query->whereRaw('1 = 0');
        }

        $totalGagal = (clone $query)->sum('jumlah');
        $totalKerugian = (clone $query)->sum('kerugian');
        $catatan = $query->with(['orderDetail.produk.produkModel', 'orderDetail.order', 'user'])
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.gagalProduksi.index', compact('catatan', 'tanggal', 'totalGagal', 'totalKerugian'));
    }

    public function create()
    {
        $this->authorizeWriter();

        return view('admin.gagalProduksi.create');
    }

    public function cariProduk(Request $request)
    {
        $this->authorizeWriter();
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $cari = trim($data['q'] ?? '');
        if ($cari === '') {
            return response()->json([]);
        }

        $produk = Produk::with('produkModel.kategori')
            ->where('status', 1)
            ->whereHas('produkModel.kategori')
            ->where(function ($query) use ($cari) {
                $query->where('nama', 'like', '%' . $cari . '%')
                    ->orWhereHas('produkModel', fn ($model) => $model->where('nama', 'like', '%' . $cari . '%')
                        ->orWhereHas('kategori', fn ($kategori) => $kategori->where('nama', 'like', '%' . $cari . '%')));
            })
            ->limit(20)
            ->get();

        return response()->json($produk->map(fn ($item) => [
            'id' => $item->id,
            'label' => $item->nama_lengkap,
        ]));
    }

    public function store(Request $request)
    {
        $this->authorizeWriter();

        $data = $request->validate([
            'produk_id' => 'required|integer|exists:produks,id',
            'tahap' => 'nullable|string|max:255',
            'tanggal' => 'required|date_format:Y-m-d',
            'jumlah' => 'required|integer|min:1|max:1000000000',
            'keterangan' => 'nullable|string|max:2000',
        ]);
        $produk = Produk::with('produkModel.kategori')->whereHas('produkModel.kategori')->findOrFail($data['produk_id']);
        $hpp = max(0, (int) $produk->hpp);

        GagalProduksi::create([
            'produk_id' => $produk->id,
            'nama_produk' => mb_substr($produk->nama_lengkap, 0, 255),
            'cabang_id' => cabang_id() ?: member_cabang_id() ?: resolve_cabang_id(),
            'tanggal' => $data['tanggal'],
            'jumlah' => $data['jumlah'],
            'hpp_satuan' => $hpp,
            'kerugian' => $data['jumlah'] * $hpp,
            'tahap' => $data['tahap'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('gagalProduksi.index', ['tanggal' => $data['tanggal']])
            ->with('success', 'Produk gagal berhasil dicatat.');
    }

    public function destroy(GagalProduksi $gagalProduksi)
    {
        $this->authorizeWriter();
        $cabang = $gagalProduksi->cabang_id ?? $gagalProduksi->orderDetail?->order?->cabang_id;
        abort_unless(can_edit_cabang_record($cabang), 403);
        $tanggal = $gagalProduksi->tanggal->toDateString();
        $gagalProduksi->delete();

        return redirect()->route('gagalProduksi.index', ['tanggal' => $tanggal])
            ->with('success', 'Catatan produk gagal berhasil dihapus.');
    }

    private function authorizeWriter(): void
    {
        abort_if(Gate::denies('gagal_produksi_access') || Gate::denies('gagal_produksi_manage'), 403);
    }
}
