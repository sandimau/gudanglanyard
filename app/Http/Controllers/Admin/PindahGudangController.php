<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\ProdukStok;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PindahGudangController extends Controller
{
    public function index()
    {
        $bolehInput = can_input_pindah_gudang();
        $cabangs = $bolehInput ? Cabang::aktif()->orderBy('nama')->get() : collect();
        $produkLabels = collect();
        if ($bolehInput) {
            $produkIds = collect(old('items', []))->pluck('produk_id')->filter()->unique();
            $produkLabels = Produk::with('produkModel.kategori')
                ->whereIn('id', $produkIds)
                ->get()
                ->mapWithKeys(fn (Produk $produk) => [$produk->id => $produk->namaLengkap]);
        }

        $riwayat = ProdukStok::with(['produk.produkModel.kategori', 'cabang', 'user'])
            ->where('kode', 'pgh')
            ->where('kurang', '>', 0)
            ->orderByDesc('id')
            ->limit(30)
            ->get();
        $tujuanPerMutasi = ProdukStok::with('cabang')
            ->whereIn('detail_id', $riwayat->pluck('id'))
            ->where('kode', 'pgh')
            ->where('tambah', '>', 0)
            ->get()
            ->keyBy('detail_id');

        return view('admin.produkStoks.pindahGudang', compact('bolehInput', 'cabangs', 'produkLabels', 'riwayat', 'tujuanPerMutasi'));
    }

    public function store(Request $request, StokService $stokService)
    {
        abort_unless(can_input_pindah_gudang(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'gudang_asal_id' => ['required', 'integer', Rule::exists('cabangs', 'id')->where('status', 1)],
            'gudang_tujuan_id' => ['required', 'integer', 'different:gudang_asal_id', Rule::exists('cabangs', 'id')->where('status', 1)],
            'keterangan' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*' => ['array'],
            'items.*.produk_id' => ['nullable', 'integer', 'exists:produks,id'],
            'items.*.jumlah' => ['nullable', 'integer', 'min:1'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($item) => !empty($item['produk_id']) || !empty($item['jumlah']));

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Isi minimal satu produk yang akan dipindahkan.']);
        }

        $errors = [];
        $seen = [];
        foreach ($items as $index => $item) {
            if (empty($item['produk_id'])) {
                $errors["items.$index.produk_id"] = 'Pilih produk untuk baris ini.';
            }
            if (empty($item['jumlah'])) {
                $errors["items.$index.jumlah"] = 'Isi jumlah untuk baris ini.';
            }
            if (!empty($item['produk_id'])) {
                $produkId = (int) $item['produk_id'];
                if (isset($seen[$produkId])) {
                    $errors["items.$index.produk_id"] = 'Produk yang sama hanya boleh dipilih satu kali.';
                }
                $seen[$produkId] = true;
            }
        }

        $produkIds = $items->pluck('produk_id')->filter()->map(fn ($id) => (int) $id)->all();
        $produkAktif = Produk::whereIn('id', $produkIds)
            ->where('status', 1)
            ->whereHas('produkModel', fn ($query) => $query->where('stok', 1))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        foreach ($items as $index => $item) {
            if (!empty($item['produk_id']) && !in_array((int) $item['produk_id'], $produkAktif, true)) {
                $errors["items.$index.produk_id"] = 'Pilih produk stok yang aktif.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $asal = Cabang::findOrFail($data['gudang_asal_id']);
        $tujuan = Cabang::findOrFail($data['gudang_tujuan_id']);
        $note = trim($data['keterangan'] ?? '');

        DB::transaction(function () use ($items, $asal, $tujuan, $note, $stokService, $produkIds) {
            // Serialkan perpindahan produk yang sama agar dua permintaan pindah tidak memakai stok yang sama.
            Produk::whereIn('id', $produkIds)->orderBy('id')->lockForUpdate()->get();

            foreach ($items as $index => $item) {
                $tersedia = $stokService->saldoTersedia((int) $item['produk_id'], $asal->id);
                if ((int) $item['jumlah'] > $tersedia) {
                    throw ValidationException::withMessages([
                        "items.$index.jumlah" => "Stok gudang asal hanya tersedia {$tersedia} untuk produk #{$item['produk_id']}.",
                    ]);
                }
            }

            foreach ($items as $item) {
                $produkId = (int) $item['produk_id'];
                $jumlah = (int) $item['jumlah'];
                $keluar = $stokService->kurang(
                    $produkId,
                    $asal->id,
                    $jumlah,
                    'pgh',
                    Str::limit('Pindah gudang ke ' . $tujuan->nama . ($note === '' ? '' : ' - ' . $note), 255, '')
                );
                $stokService->tambah(
                    $produkId,
                    $tujuan->id,
                    $jumlah,
                    'pgh',
                    Str::limit('Pindah gudang dari ' . $asal->nama . ($note === '' ? '' : ' - ' . $note), 255, ''),
                    $keluar->id
                );
            }
        });

        return redirect()->route('pindahGudang.index')
            ->withSuccess('Berhasil memindahkan ' . $items->count() . ' produk ke gudang ' . $tujuan->nama . '.');
    }
}
