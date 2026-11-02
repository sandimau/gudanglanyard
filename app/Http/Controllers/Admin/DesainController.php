<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desain;
use App\Models\DesainTab;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesainController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'tipe' => ['nullable', Rule::in(['offline', 'online'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $pencarian = trim((string) $request->query('q', ''));
        $tipe = trim((string) $request->query('tipe', ''));

        $desains = Desain::with(['tab', 'produk.produkModel.kategori'])
            ->when($pencarian !== '', function ($query) use ($pencarian) {
                $query->where(function ($query) use ($pencarian) {
                    $query->where('nama_konsumen', 'like', '%'.$pencarian.'%')
                        ->orWhere('tema', 'like', '%'.$pencarian.'%')
                        ->orWhere('tipe', 'like', '%'.$pencarian.'%')
                        ->orWhere('keterangan', 'like', '%'.$pencarian.'%')
                        ->orWhereHas('produk', function ($produk) use ($pencarian) {
                            $produk->where('nama', 'like', '%'.$pencarian.'%')
                                ->orWhereHas('produkModel', function ($model) use ($pencarian) {
                                    $model->where('nama', 'like', '%'.$pencarian.'%')
                                        ->orWhereHas('kategori', fn ($kategori) => $kategori->where('nama', 'like', '%'.$pencarian.'%'));
                                });
                        });
                });
            })
            ->when($tipe !== '', fn ($query) => $query->where('tipe', $tipe))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $bolehTambahDesain = DesainTab::exists();
        $canSetupDesain = auth()->user()?->can('desain_setup_access');

        return view('admin.desain.index', compact('desains', 'pencarian', 'tipe', 'bolehTambahDesain', 'canSetupDesain'));
    }

    public function proses()
    {
        $allTabs = DesainTab::ordered();
        $tabs = $allTabs->reject(fn (DesainTab $tab) => $tab->isHiddenFromProses())->groupBy('grup');
        $desains = Desain::with(['tab', 'produk.produkModel.kategori'])->orderByDesc('id')->get();
        $perTab = $desains->groupBy('desain_tab_id');
        $canSetupDesain = auth()->user()?->can('desain_setup_access');

        return view('admin.desain.proses', compact('tabs', 'allTabs', 'perTab', 'canSetupDesain'));
    }

    public function create(Request $request)
    {
        $tabs = DesainTab::ordered();
        if ($tabs->isEmpty()) {
            return $this->setupBelumAda();
        }

        $desain = new Desain;

        return view('admin.desain.form', compact('desain', 'tabs'));
    }

    public function show(Request $request, Desain $desain)
    {
        $desain->load(['tab', 'produk.produkModel.kategori', 'chats' => fn ($query) => $query->with(['member', 'user'])->orderBy('id')]);

        if ($request->ajax()) {
            return view('admin.desain.partials.detail', compact('desain'));
        }

        return view('admin.desain.show', compact('desain'));
    }

    public function storeChat(Request $request, Desain $desain)
    {
        $data = $request->validate(['isi' => ['required', 'string', 'max:5000']]);
        $member = Member::withoutGlobalScope('cabang')->where('user_id', auth()->id())->first();

        $desain->chats()->create([
            'isi' => $data['isi'],
            'member_id' => $member?->id,
            'user_id' => $member ? null : auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Catatan berhasil dikirim.'], 201);
        }

        return redirect()->route('desain.show', $desain)->withSuccess('Catatan berhasil dikirim.');
    }

    public function store(Request $request)
    {
        $tabPertama = DesainTab::ordered()->first();
        if (!$tabPertama) {
            return $this->setupBelumAda();
        }

        $data = $this->validated($request);
        $data['desain_tab_id'] = $tabPertama->id;
        Desain::create($data);

        $route = $this->returnLocation($request);

        return redirect($route)
            ->withSuccess('Desain berhasil ditambahkan.');
    }

    public function edit(Desain $desain)
    {
        $tabs = DesainTab::ordered();

        return view('admin.desain.form', compact('desain', 'tabs'));
    }

    public function update(Request $request, Desain $desain)
    {
        $data = $this->validated($request);
        $location = $this->returnLocation($request, $desain);
        $desain->update($data);

        return redirect($location)
            ->withSuccess('Desain berhasil diperbarui.');
    }

    public function destroy(Request $request, Desain $desain)
    {
        $location = $request->input('asal') === 'detail'
            ? route('desain.proses')
            : $this->returnLocation($request);
        $desain->delete();

        return redirect($location)
            ->withSuccess('Desain berhasil dihapus.');
    }

    public function updateTab(Request $request, Desain $desain)
    {
        $data = $request->validate([
            'desain_tab_id' => ['required', Rule::exists('desain_tabs', 'id')],
        ]);
        $desain->update($data);

        return back()->withSuccess('Proses desain berhasil diperbarui.');
    }

    private function setupBelumAda()
    {
        $canSetup = auth()->user()?->can('desain_setup_access');

        return redirect()->route($canSetup ? 'desain.setup' : 'desain.index')
            ->with('danger', $canSetup
                ? 'Buat kategori dan sub kategori desain terlebih dahulu sebelum menambahkan desain.'
                : 'Setup desain belum tersedia. Hubungi pengguna yang bisa mengatur kategori desain.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama_konsumen' => ['required', 'string', 'max:255'],
            'produk_id' => ['required', 'integer', 'exists:produks,id'],
            'tema' => ['nullable', 'string', 'max:255'],
            'tipe' => ['required', Rule::in(['offline', 'online'])],
            'keterangan' => ['nullable', 'string'],
        ]);
    }

    private function returnLocation(Request $request, ?Desain $desain = null): string
    {
        if ($request->input('asal') === 'proses') {
            return route('desain.proses');
        }

        if ($request->input('asal') === 'detail' && $desain) {
            return route('desain.show', $desain);
        }

        $data = $request->validate([
            'daftar_q' => ['nullable', 'string', 'max:255'],
            'daftar_tipe' => ['nullable', Rule::in(['offline', 'online'])],
            'daftar_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return route('desain.index', array_filter([
            'q' => $data['daftar_q'] ?? null,
            'tipe' => $data['daftar_tipe'] ?? null,
            'page' => $data['daftar_page'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));
    }
}
