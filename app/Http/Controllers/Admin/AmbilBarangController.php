<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Produk;
use App\Services\AmbilBarangService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AmbilBarangController extends Controller
{
    private function authorizeStok(): void
    {
        abort_unless(Gate::allows('produk_stok_access') || Gate::allows('produk_access'), 403);
    }

    public function index(Request $request, AmbilBarangService $service)
    {
        $this->authorizeStok();
        $tab = $request->query('tab') === 'arsip' ? 'arsip' : 'permintaan';
        $cabangKelompok = collect();
        $arsipCabang = collect();
        $arsipPagination = null;
        $cekKosong = collect();

        if ($tab === 'arsip') {
            $arsipPagination = $service->arsip((int) $request->query('page', 1));
            $arsipPagination->withPath(route('ambilBarang.index'))->appends(['tab' => 'arsip']);
            $arsipCabang = $arsipPagination->getCollection()->groupBy('cabang_id')
                ->map(fn ($items) => $items->groupBy('produk_id')->map(function ($produkItems) {
                    $first = $produkItems->first();
                    return [
                        'produk' => $first['produk'],
                        'produk_id' => $first['produk_id'],
                        'stok' => app(StokService::class)->saldoTersedia($first['produk_id'], $first['cabang_id']),
                        'jumlah' => $first['total_produk'],
                        'items' => $produkItems->values(),
                    ];
                })->values())->sortKeys();
        } else {
            $kelompok = $service->kebutuhan();
            $cabangKelompok = $kelompok->groupBy('cabang_id')->map(fn ($groups) => $groups->groupBy(fn ($item) => $item['stok'] <= 0
                ? 'Kosong di sistem'
                : ($item['stok'] < $item['jumlah'] ? 'Stok kurang' : 'Stok cukup')))->sortKeys();
            $cekKosong = DB::table('cek_stok_kosongs')->select('cabang_id', 'produk_id')
                ->selectRaw('MAX(created_at) as dicek_pada')
                ->groupBy('cabang_id', 'produk_id')->get()
                ->keyBy(fn ($row) => $row->cabang_id . ':' . $row->produk_id);
        }

        $cabangIds = $tab === 'arsip' ? $arsipCabang->keys() : $cabangKelompok->keys();
        $namaCabangAmbilBarang = Cabang::whereIn('id', $cabangIds)->pluck('nama', 'id');

        return view('admin.ambilBarang.index', compact(
            'tab', 'cabangKelompok', 'arsipCabang', 'arsipPagination', 'namaCabangAmbilBarang', 'cekKosong'
        ));
    }

    public function store(Request $request, AmbilBarangService $service)
    {
        $this->authorizeStok();
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => ['required', 'string', 'regex:/^(order|project):[1-9][0-9]*$/'],
        ]);

        $jumlah = $service->ambil($data['items']);

        return back()->withSuccess("Barang untuk {$jumlah} detail order berhasil diambil.");
    }

    public function opname(Request $request, StokService $stokService)
    {
        $this->authorizeStok();
        abort_unless(Gate::allows('opname_access'), 403);
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|integer|exists:produks,id',
            'items.*.cabang_id' => 'required|integer|exists:cabangs,id',
            'items.*.pilih' => 'nullable|boolean',
            'items.*.stok_fisik' => 'nullable|integer|min:0',
            'keterangan' => 'required|string|max:255',
        ]);

        $selected = collect($data['items'])->filter(fn ($item) => !empty($item['pilih']))->values();
        if ($selected->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Pilih minimal satu produk untuk diopname.']);
        }

        $seen = [];
        foreach ($selected as $item) {
            abort_unless_can_edit_cabang((int) $item['cabang_id']);
            if (!isset($item['stok_fisik'])) {
                throw ValidationException::withMessages(['items' => 'Isi stok fisik untuk semua produk yang dipilih.']);
            }
            $key = $item['cabang_id'] . ':' . $item['produk_id'];
            if (isset($seen[$key])) {
                throw ValidationException::withMessages(['items' => 'Produk yang sama tidak boleh dipilih dua kali dalam cabang yang sama.']);
            }
            $seen[$key] = true;
        }

        $jumlah = DB::transaction(function () use ($selected, $data, $stokService) {
            $produkRows = Produk::with('produkModel')->whereIn('id', $selected->pluck('produk_id')->unique())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $updated = 0;
            foreach ($selected as $item) {
                $produk = $produkRows->get($item['produk_id']);
                if (!$produk || (int) ($produk->produkModel?->stok ?? 0) !== 1) {
                    throw ValidationException::withMessages(['items' => 'Salah satu produk tidak mencatat stok.']);
                }
                $cabangId = (int) $item['cabang_id'];
                $saldo = $stokService->saldoTersedia($produk->id, $cabangId);
                $selisih = (int) $item['stok_fisik'] - $saldo;
                if ($selisih === 0) {
                    continue;
                }
                $stokService->opname($produk->id, $cabangId, max(0, $selisih), max(0, -$selisih),
                    'Opname Ambil Barang: ' . $data['keterangan']);
                $updated++;
            }
            if ($updated === 0) {
                throw ValidationException::withMessages(['items' => 'Tidak ada selisih antara stok fisik dan stok sistem.']);
            }

            return $updated;
        });

        return back()->withSuccess("Stok {$jumlah} produk berhasil disesuaikan dengan hasil hitung fisik.");
    }

    public function kosong(Request $request)
    {
        $this->authorizeStok();
        $data = $request->validate([
            'produk_id' => 'required|integer|exists:produks,id',
            'cabang_id' => 'required|integer|exists:cabangs,id',
        ]);
        abort_unless_can_edit_cabang((int) $data['cabang_id']);
        $produk = Produk::with('produkModel')->findOrFail($data['produk_id']);
        if ((int) ($produk->produkModel?->stok ?? 0) !== 1) {
            throw ValidationException::withMessages(['produk_id' => 'Produk ini tidak mencatat stok.']);
        }

        DB::table('cek_stok_kosongs')->insert([
            'produk_id' => $data['produk_id'],
            'cabang_id' => $data['cabang_id'],
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return back()->withSuccess('Stok fisik kosong dicatat. Order tetap belum diambil.');
    }
}
