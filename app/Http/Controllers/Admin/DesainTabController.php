<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DesainGroup;
use App\Models\DesainTab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DesainTabController extends Controller
{
    public function index()
    {
        $groups = DesainGroup::ordered();
        $tabs = DesainTab::ordered()->groupBy('grup');

        return view('admin.desain.setup', compact('groups', 'tabs'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['urutan'] = (DesainTab::where('grup', $data['grup'])->max('urutan') ?? 0) + 1;
        DesainTab::create($data);

        return redirect()->route('desain.setup')->withSuccess('Sub kategori berhasil ditambahkan.');
    }

    public function update(Request $request, DesainTab $tab)
    {
        $data = $this->validated($request);
        if ($tab->grup !== $data['grup']) {
            $data['urutan'] = (DesainTab::where('grup', $data['grup'])->max('urutan') ?? 0) + 1;
        }
        $tab->update($data);

        return redirect()->route('desain.setup')->withSuccess('Sub kategori berhasil diperbarui.');
    }

    public function destroy(DesainTab $tab)
    {
        if ($tab->desains()->exists()) {
            return back()->withErrors(['tab' => 'Sub kategori masih dipakai oleh desain. Pindahkan desain ke sub kategori lain terlebih dahulu.']);
        }
        $tab->delete();

        return redirect()->route('desain.setup')->withSuccess('Sub kategori berhasil dihapus.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'grup' => ['required', 'string'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('desain_tabs', 'id')->where('grup', $request->input('grup'))],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['ids'] as $index => $id) {
                DesainTab::whereKey($id)->update(['urutan' => $index + 1]);
            }
        });

        return response()->json(['success' => true]);
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('desain_groups', 'nama')],
        ]);
        $data['urutan'] = (DesainGroup::max('urutan') ?? 0) + 1;
        DesainGroup::create($data);

        return redirect()->route('desain.setup')->withSuccess('Kategori berhasil ditambahkan.');
    }

    public function updateGroup(Request $request, DesainGroup $group)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('desain_groups', 'nama')->ignore($group->id)],
        ]);

        DB::transaction(function () use ($group, $data) {
            $namaLama = $group->nama;
            $group->update($data);

            if ($namaLama !== $data['nama']) {
                DesainTab::where('grup', $namaLama)->update(['grup' => $data['nama']]);
            }
        });

        return redirect()->route('desain.setup')->withSuccess('Kategori berhasil diperbarui.');
    }

    public function destroyGroup(DesainGroup $group)
    {
        if ($group->tabs()->exists()) {
            return back()->withErrors(['group' => 'Kategori masih memiliki sub kategori. Hapus atau pindahkan sub kategori terlebih dahulu.']);
        }
        $group->delete();

        return redirect()->route('desain.setup')->withSuccess('Kategori berhasil dihapus.');
    }

    public function reorderGroups(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('desain_groups', 'id')],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['ids'] as $index => $id) {
                DesainGroup::whereKey($id)->update(['urutan' => $index + 1]);
            }
        });

        return response()->json(['success' => true]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'grup' => ['required', 'string', 'max:255', Rule::exists('desain_groups', 'nama')],
            'warna' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
