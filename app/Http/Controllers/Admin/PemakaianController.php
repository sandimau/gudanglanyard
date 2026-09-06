<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProdukPakai;
use App\Services\StokService;
use App\Models\Produk;
use Illuminate\Http\Request;

class PemakaianController extends Controller
{
    public function index(Request $request)
    {
        $pemakaians = ProdukPakai::query()
            ->with(['produk.produkModel.kategori', 'user'])
            ->when($request->dari && $request->sampai, function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->dari, $request->sampai]);
            })
            ->when($request->produk_id, function ($query) use ($request) {
                $query->where('produk_id', $request->produk_id);
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->appends($request->only(['dari', 'sampai', 'produk_id']));

        return view('admin.pemakaian.index', compact('pemakaians'));
    }

    public function create()
    {
        return view('admin.pemakaian.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'required|string',
        ]);

        $produk = Produk::findOrFail($request->produk_id);
        $hpp = $produk->hpp ?? 0;

        $produkStok = app(StokService::class)->kurang(
            $request->produk_id,
            resolve_cabang_id(),
            $request->jumlah,
            'pakai',
            $request->keterangan ?? 'Pemakaian produk',
            null,
            ['user_id' => auth()->user()->id]
        );

        // Buat ProdukPakai
        $pemakaian = ProdukPakai::create([
            'produk_id' => $request->produk_id,
            'jumlah' => $request->jumlah,
            'keterangan' => $request->keterangan,
            'hpp' => $hpp,
            'produk_stok_id' => $produkStok->id,
            'user_id' => auth()->user()->id,
        ]);

        return redirect()->route('pemakaian.index')->withSuccess(__('Pemakaian berhasil ditambahkan'));
    }

    public function edit(ProdukPakai $pemakaian)
    {
        return view('admin.pemakaian.edit', compact('pemakaian'));
    }

    public function update(Request $request, ProdukPakai $pemakaian)
    {
        $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'required|string',
        ]);

        $produk = Produk::findOrFail($request->produk_id);
        $hpp = $produk->hpp ?? 0;

        if ($pemakaian->produk_stok_id) {
            $oldProdukStok = \App\Models\ProdukStok::find($pemakaian->produk_stok_id);
            if ($oldProdukStok) {
                $produkStok = app(StokService::class)->tambah(
                    $pemakaian->produk_id,
                    resolve_cabang_id($pemakaian->cabang_id ?? $oldProdukStok->cabang_id),
                    $pemakaian->jumlah,
                    'pakai',
                    'Balikin pemakaian - ' . $request->keterangan,
                    $pemakaian->id,
                    [
                        'user_id' => auth()->user()->id,
                        'status' => 'manual',
                    ]
                );

                $oldProdukStok->update([
                    'detail_id' => $pemakaian->id,
                    'status' => 'manual',
                ]);
            }
        }

        // Update ProdukPakai dengan ProdukStok yang baru
        $pemakaian->update([
            'produk_id' => $request->produk_id,
            'jumlah' => $request->jumlah - $pemakaian->jumlah,
            'keterangan' => $request->keterangan,
            'hpp' => $hpp,
            'produk_stok_id' => $produkStok->id,
        ]);

        return redirect()->route('pemakaian.index')->withSuccess(__('Pemakaian berhasil diupdate'));
    }
}
