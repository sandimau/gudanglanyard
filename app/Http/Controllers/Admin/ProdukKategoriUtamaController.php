<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\ProdukKategoriUtama;
use App\Models\ProdukKategori;
use App\Models\ProdukModel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProdukKategoriUtamaController extends Controller
{
    public function index()
    {
        $kategoriUtamas = ProdukKategoriUtama::latest()->paginate(10);
        return view('produk-kategori-utama.index', compact('kategoriUtamas'));
    }

    public function create()
    {
        return view('produk-kategori-utama.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'jual' => 'nullable|boolean',
            'beli' => 'nullable|boolean',
            'stok' => 'nullable|boolean',
            'produksi' => 'nullable|boolean',
            'is_custom' => 'nullable|boolean',
        ]);

        $data['is_custom'] = $request->boolean('is_custom');
        if ($data['is_custom']) {
            $data = array_merge($data, ['jual' => 1, 'beli' => 0, 'stok' => 0]);
        }
        ProdukKategoriUtama::create($data);

        return redirect()->route('produk-kategori-utama.index')
            ->with('success', 'Kategori Utama berhasil ditambahkan');
    }

    public function edit(ProdukKategoriUtama $produkKategoriUtama)
    {
        return view('produk-kategori-utama.edit', compact('produkKategoriUtama'));
    }

    public function update(Request $request, ProdukKategoriUtama $produkKategoriUtama)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'jual' => 'nullable|boolean',
            'beli' => 'nullable|boolean',
            'stok' => 'nullable|boolean',
            'produksi' => 'nullable|boolean',
            'is_custom' => 'nullable|boolean',
        ]);

        $kategoriIds = ProdukKategori::where('kategori_utama_id', $produkKategoriUtama->id)->pluck('id');
        $isCustom = $request->boolean('is_custom');

        if ($isCustom && ! $produkKategoriUtama->is_custom
            && ProdukModel::whereIn('kategori_id', $kategoriIds)->where('stok', 1)->exists()) {
            throw ValidationException::withMessages([
                'is_custom' => 'Kategori berisi produk berstok. Pindahkan produk berstok sebelum mengaktifkan resep custom.',
            ]);
        }

        if (! $isCustom && $produkKategoriUtama->is_custom
            && DB::table('produk_custom_reseps as r')
                ->join('produks as p', 'p.id', '=', 'r.produk_id')
                ->join('produk_models as m', 'm.id', '=', 'p.produk_model_id')
                ->whereIn('m.kategori_id', $kategoriIds)->exists()) {
            throw ValidationException::withMessages([
                'is_custom' => 'Kategori masih memiliki resep produk. Hapus resep sebelum menonaktifkan fitur custom.',
            ]);
        }

        $data['is_custom'] = $isCustom;
        if ($isCustom) {
            $data = array_merge($data, ['jual' => 1, 'beli' => 0, 'stok' => 0]);
        }

        DB::transaction(function () use ($produkKategoriUtama, $data, $kategoriIds) {
            $produkKategoriUtama->update($data);
            ProdukModel::whereIn('kategori_id', $kategoriIds)->update([
                'jual' => $data['jual'] ?? 0,
                'beli' => $data['beli'] ?? 0,
                'stok' => $data['stok'] ?? 0,
                'produksi' => $data['produksi'] ?? 0,
            ]);
        });

        return redirect()->route('produk-kategori-utama.index')
            ->with('success', 'Kategori Utama berhasil diperbarui');
    }

    public function destroy(ProdukKategoriUtama $produkKategoriUtama)
    {
        $produkKategoriUtama->delete();

        return redirect()->route('produk-kategori-utama.index')
            ->with('success', 'Kategori Utama berhasil dihapus');
    }
}
