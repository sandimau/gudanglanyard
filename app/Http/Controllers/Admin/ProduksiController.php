<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produksi;
use Illuminate\Http\Request;

class ProduksiController extends Controller
{
    public function index()
    {
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

        return view('admin.produksis.index', compact('produksis'));
    }

    public function updateUrutan(Request $request)
    {
        $ids = $request->input('ids', []);

        foreach ($ids as $urutan => $id) {
            Produksi::where('id', $id)->update(['urutan' => $urutan + 1]);
        }

        return response()->json(['success' => true]);
    }

    public function create()
    {
        return view('admin.produksis.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'grup' => 'required',
        ]);

        $produksi = Produksi::create($request->all());

        return redirect()->route('produksis.index')->withSuccess(__('Produksi created successfully.'));
    }

    public function edit(Produksi $produksi)
    {
        return view('admin.produksis.edit', compact('produksi'));
    }

    public function update(Request $request, Produksi $produksi)
    {
        $produksi->update($request->all());

        return redirect()->route('produksis.index')->withSuccess(__('Produksi updated successfully.'));
    }
}
