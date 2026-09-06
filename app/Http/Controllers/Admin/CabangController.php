<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use Illuminate\Http\Request;

class CabangController extends Controller
{
    public function switch(Cabang $cabang)
    {
        abort_unless($cabang->status, 404);

        session(['cabang_id' => $cabang->id]);

        return redirect()->back()->withSuccess(__('Cabang diganti ke :nama', ['nama' => $cabang->nama]));
    }

    public function index()
    {
        $this->authorizeConfig();

        $cabangList = Cabang::orderBy('nama')->get();

        return view('admin.cabangs.index', compact('cabangList'));
    }

    public function create()
    {
        $this->authorizeConfig();

        return view('admin.cabangs.create');
    }

    public function store(Request $request)
    {
        $this->authorizeConfig();

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'nullable|string|max:50|unique:cabangs,kode',
            'alamat' => 'nullable|string',
            'status' => 'nullable',
        ]);

        $data['status'] = $request->has('status');

        Cabang::create($data);

        return redirect()->route('cabang.index')->withSuccess(__('Cabang berhasil ditambahkan.'));
    }

    public function edit(Cabang $cabang)
    {
        $this->authorizeConfig();

        return view('admin.cabangs.edit', compact('cabang'));
    }

    public function update(Request $request, Cabang $cabang)
    {
        $this->authorizeConfig();

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'nullable|string|max:50|unique:cabangs,kode,' . $cabang->id,
            'alamat' => 'nullable|string',
            'status' => 'nullable',
        ]);

        $data['status'] = $request->has('status');

        $cabang->update($data);

        if (session('cabang_id') == $cabang->id && !$cabang->status) {
            $fallback = Cabang::aktif()->orderBy('nama')->first();
            if ($fallback) {
                session(['cabang_id' => $fallback->id]);
            }
        }

        return redirect()->route('cabang.index')->withSuccess(__('Cabang berhasil diperbarui.'));
    }

    private function authorizeConfig(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super', 'admin']), 403);
    }
}
