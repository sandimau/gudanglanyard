<?php

namespace App\Http\Controllers\Admin;

use App\Services\StokService;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use App\Models\ProduksiProduk;
use App\Models\ProdukProduksiHasil;
use App\Http\Controllers\Controller;

class ProdukProduksiController extends Controller
{
    public function index()
    {
        $produkProduksis = ProdukProduksi::with(['produk.produkModel.kategori', 'user'])
            ->orderBy('id', 'desc')
            ->get();
        return view('admin.produkProduksi.index', compact('produkProduksis'));
    }

    public function create()
    {
        return view('admin.produkProduksi.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'satuan' => 'required|in:berat,luas,persen',
            'panjang' => 'nullable|numeric|min:0',
            'lebar' => 'nullable|numeric|min:0',
            'perbandingan' => 'nullable|numeric|min:0',
        ]);

        ProdukProduksi::create([
            'produk_id' => $request->produk_id,
            'satuan' => $request->satuan,
            'panjang' => $request->panjang,
            'lebar' => $request->lebar,
            'perbandingan' => $request->perbandingan ?? $request->panjang * $request->lebar,
            'user_id' => auth()->user()->id ?? null,
        ]);

        return redirect()->route('produkProduksi.index')
            ->with('success', 'Produk Produksi berhasil ditambahkan');
    }

    public function hasilProduksi(ProduksiProduk $produksi)
    {
        return view('admin.produkProduksi.hasilProduksi', compact('produksi'));
    }

    public function hasilProduksiStore(Request $request, ProduksiProduk $produksi)
    {
        $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'jumlah' => 'required|numeric|min:0',
        ]);

        ProdukProduksiHasil::create([
            'produk_id' => $request->produk_id,
            'produksi_id' => $produksi->id,
            'jumlah' => $request->jumlah,
            'user_id' => auth()->user()->id ?? null,
        ]);

        return redirect()->route('produksi.show', $produksi->id)
            ->with('success', 'Hasil Produksi berhasil ditambahkan');
    }

    public function destroy(ProdukProduksi $produkProduksi)
    {
        $produkProduksi->delete();

        return redirect()->route('produkProduksi.index')
            ->with('success', 'Produk Produksi berhasil dihapus');
    }

    public function hasilProduksiDestroy(ProduksiProduk $produksi, ProdukProduksiHasil $hasil)
    {
        $hasil->delete();

        return redirect()->route('produksi.show', $produksi->id)
            ->with('success', 'Hasil Produksi berhasil dihapus');
    }

    public function selesaiHasilProduksiSatuan(ProduksiProduk $produksi, ProdukProduksiHasil $hasil)
    {
        if ($hasil->status == 'finish') {
            return redirect()->route('produksi.show', $produksi->id)
                ->with('error', 'Hasil produksi ini sudah diselesaikan');
        }

        $produk = $hasil->produk;
        $produk->updateHpp($hasil->hpp, $hasil->jumlah);

        app(StokService::class)->tambah(
            $produk->id,
            resolve_cabang_id($hasil->cabang_id ?? $hasil->produksi->cabang_id ?? null),
            $hasil->jumlah,
            'hasilProduksi',
            'hasil produksi',
            $hasil->id
        );

        $hasil->update(['status' => 'finish']);

        // Cek apakah semua hasil produksi sudah selesai
        $belumSelesai = $produksi->hasilProduksi()->where('status', '!=', 'finish')->count();
        if ($belumSelesai == 0) {
            $produksi->updated_at = now();
            $produksi->save();
        }

        return redirect()->route('produksi.show', $produksi->id)
            ->with('success', 'Hasil Produksi berhasil diselesaikan');
    }

    public function editHasilProduksi(ProduksiProduk $produksi, ProdukProduksiHasil $hasil)
    {
        $hasil->load('produk.produkModel.kategori');
        return view('admin.produkProduksi.editHasilProduksi', compact('produksi', 'hasil'));
    }

    public function updateHasilProduksi(Request $request, ProduksiProduk $produksi, ProdukProduksiHasil $hasil)
    {
        $request->validate([
            'jumlah' => 'required|numeric|min:0',
        ]);

        $hasil->update([
            'jumlah' => $request->jumlah,
        ]);

        return redirect()->route('produksi.show', $produksi->id)
            ->with('success', 'Hasil Produksi berhasil diupdate');
    }

    public function selesaiProduksi(ProduksiProduk $produksi)
    {
        foreach ($produksi->hasilProduksi as $item) {
            // Skip jika sudah selesai
            if ($item->status == 'finish') {
                continue;
            }

            $produk = $item->produk;
            $produk->updateHpp($item->hpp, $item->jumlah);

            app(StokService::class)->tambah(
                $produk->id,
                resolve_cabang_id($item->cabang_id ?? $produksi->cabang_id ?? null),
                $item->jumlah,
                'hasilProduksi',
                'hasil produksi',
                $item->id
            );

            $item->update(['status' => 'finish']);
        }

        $produksi->updated_at = now();
        $produksi->status = 'finish';
        $produksi->save();

        return redirect()->route('produksi.index')->with('success', 'Produksi berhasil diselesaikan');
    }
    public function produksiLagi(ProduksiProduk $produksi)
    {
        $produksiBaru = ProduksiProduk::create([
            'status' => 'proses',
            'cabang_id' => $produksi->cabang_id ?: cabang_id(),
            'ket' => $produksi->ket,
            'produk_id' => $produksi->produk_id,
            'company_id' => $produksi->company_id ?? session('company') ?? 1,
            'user_id' => auth()->id(),
        ]);

        foreach ($produksi->hasilProduksi as $hasil) {
            $produksiBaru->hasilProduksi()->create([
                'produk_id' => $hasil->produk_id,
                'jumlah' => $hasil->jumlah,
            ]);
        }

        foreach ($produksi->bahan as $bahan) {
            $stok = app(StokService::class)->kurang(
                $bahan->produk_id,
                resolve_cabang_id($produksiBaru->cabang_id ?? $bahan->cabang_id ?? null),
                $bahan->jumlah,
                'bahanProduksi',
                $produksiBaru->ket,
                $produksiBaru->id
            );
            $produksiBaru->bahan()->create([
                'produk_id' => $bahan->produk_id,
                'jumlah' => $bahan->jumlah,
                'hpp' => $bahan->hpp,
                'produk_stok_id' => $stok->id,
            ]);
        }

        $produksiBaru->hitungBiaya();

        $produksiBaru->hitungHpp();

        return redirect()->route('produksi.show', $produksiBaru->id)->with('success', 'Produksi berhasil diulang');
    }
}
