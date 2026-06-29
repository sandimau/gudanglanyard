<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyLevelRequest;
use App\Http\Requests\StoreLevelRequest;
use App\Http\Requests\UpdateLevelRequest;
use App\Models\Level;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LevelController extends Controller
{
    public function index()
    {
        $levels = Level::all();

        return view('admin.levels.index', compact('levels'));
    }

    public function create()
    {
        return view('admin.levels.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'gaji_pokok' => 'required',
            'komunikasi' => 'required',
            'transportasi' => 'required',
            'kehadiran' => 'required',
            'lama_kerja' => 'required',
            'harga_lembur' => 'nullable|numeric',
        ]);

        $level = Level::create($request->all());

        return redirect()->route('level.index');
    }

    public function edit(Level $level)
    {
        return view('admin.levels.edit', compact('level'));
    }

    public function update(Request $request, Level $level)
    {
        $request->validate([
            'nama' => 'required',
            'gaji_pokok' => 'required',
            'komunikasi' => 'required',
            'transportasi' => 'required',
            'kehadiran' => 'required',
            'lama_kerja' => 'required',
            'harga_lembur' => 'nullable|numeric',
        ]);

        $level->update($request->all());

        return redirect()->route('level.index');
    }
}
