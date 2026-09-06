<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ar;
use App\Models\Cabang;
use App\Models\Kontak;
use Illuminate\Http\Request;

class KontakController extends Controller
{
    public function index()
    {
        $kontaks = Kontak::with('cabang')->get();

        return view('admin.kontaks.index', compact('kontaks'));
    }

    public function create()
    {
        $ars = Ar::get();
        $cabangs = Cabang::aktif()->orderBy('nama')->get();

        return view('admin.kontaks.create', compact('ars', 'cabangs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'noTelp' => 'required',
            'cabang_id' => 'required|exists:cabangs,id',
            'konsumen' => 'required_without:supplier',
            'supplier' => 'required_without:konsumen',
        ]);

        Kontak::create([
            'nama' => $request->nama,
            'noTelp' => $request->noTelp,
            'email' => $request->email,
            'alamat' => $request->alamat,
            'supplier' => $request->supplier,
            'konsumen' => $request->konsumen,
            'marketplace' => $request->marketplace,
            'perusahaan' => $request->perusahaan,
            'ar_id' => $request->ar_id,
            'cabang_id' => (int) $request->cabang_id,
        ]);

        return redirect()->route('kontaks.index')->withSuccess(__('Kontak created successfully.'));
    }

    public function edit(Kontak $kontak)
    {
        $ars = Ar::get();
        $cabangs = Cabang::aktif()->orderBy('nama')->get();

        return view('admin.kontaks.edit', compact('kontak', 'ars', 'cabangs'));
    }

    public function update(Request $request, Kontak $kontak)
    {
        $request->validate([
            'cabang_id' => 'required|exists:cabangs,id',
        ]);

        $kontak->update([
            'nama' => $request->nama,
            'noTelp' => $request->noTelp,
            'email' => $request->email,
            'alamat' => $request->alamat,
            'supplier' => $request->supplier,
            'konsumen' => $request->konsumen,
            'marketplace' => $request->marketplace,
            'perusahaan' => $request->perusahaan,
            'ar_id' => $request->ar_id,
            'cabang_id' => (int) $request->cabang_id,
        ]);

        return redirect()->route('kontaks.index')->withSuccess(__('Kontak updated successfully.'));
    }

    public function show(Kontak $kontak)
    {
        $ars = Ar::get();
        return view('admin.kontaks.show', compact('ars', 'kontak'));
    }
}
