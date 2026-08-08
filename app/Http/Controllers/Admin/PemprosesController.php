<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pemproses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PemprosesController extends Controller
{
    public function index()
    {
        $pemproses = Pemproses::orderBy('kategori')->orderBy('nama')->get();

        return view('admin.pemproses.index', compact('pemproses'));
    }

    public function create()
    {
        $kategoriOptions = Pemproses::kategoriOptions();

        return view('admin.pemproses.create', compact('kategoriOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|max:50',
            'warna' => 'nullable|max:10',
            'kategori' => ['required', Rule::in(array_keys(Pemproses::kategoriOptions()))],
        ]);

        Pemproses::create([
            'nama' => $request->nama,
            'warna' => $this->normalizeWarna($request->warna),
            'kategori' => $request->kategori,
        ]);

        return redirect()->route('pemproses.index')->withSuccess(__('Pemproses created successfully.'));
    }

    public function edit(Pemproses $pemproses)
    {
        $kategoriOptions = Pemproses::kategoriOptions();

        return view('admin.pemproses.edit', compact('pemproses', 'kategoriOptions'));
    }

    public function update(Request $request, Pemproses $pemproses)
    {
        $request->validate([
            'nama' => 'required|max:50',
            'warna' => 'nullable|max:10',
            'kategori' => ['required', Rule::in(array_keys(Pemproses::kategoriOptions()))],
        ]);

        $pemproses->update([
            'nama' => $request->nama,
            'warna' => $this->normalizeWarna($request->warna),
            'kategori' => $request->kategori,
        ]);

        return redirect()->route('pemproses.index')->withSuccess(__('Pemproses updated successfully.'));
    }

    private function normalizeWarna(?string $warna): ?string
    {
        if (empty($warna)) {
            return null;
        }

        return ltrim($warna, '#');
    }
}
