<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KontenTab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KontenTabController extends Controller
{
    public function index()
    {
        $tabs = KontenTab::ordered()->groupBy('grup');

        return view('admin.konten.setup', compact('tabs'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['urutan'] = (KontenTab::where('grup', $data['grup'])->max('urutan') ?? 0) + 1;
        KontenTab::create($data);

        return redirect()->route('konten.setup')->withSuccess('Tab konten berhasil ditambahkan.');
    }

    public function update(Request $request, KontenTab $tab)
    {
        $data = $this->validated($request);
        if ($tab->grup !== $data['grup']) {
            $data['urutan'] = (KontenTab::where('grup', $data['grup'])->max('urutan') ?? 0) + 1;
        }
        $tab->update($data);

        return redirect()->route('konten.setup')->withSuccess('Tab konten berhasil diperbarui.');
    }

    public function destroy(KontenTab $tab)
    {
        if ($tab->kontens()->exists()) {
            return back()->withErrors(['tab' => 'Tab masih dipakai oleh konten. Pindahkan konten ke tab lain terlebih dahulu.']);
        }
        $tab->delete();

        return redirect()->route('konten.setup')->withSuccess('Tab konten berhasil dihapus.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'grup' => ['required', 'string'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('konten_tabs', 'id')->where('grup', $request->input('grup'))],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['ids'] as $index => $id) {
                KontenTab::whereKey($id)->update(['urutan' => $index + 1]);
            }
        });

        return response()->json(['success' => true]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'grup' => ['required', 'string', 'max:255'],
            'warna' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
