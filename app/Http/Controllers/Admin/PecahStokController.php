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

class PecahStokController extends Controller
{
    public function index()
    {
        $bolehInput = can_input_pindah_gudang();
        $cabangs = $bolehInput ? Cabang::aktif()->orderBy('nama')->get() : collect();
        $produkLabels = collect();

        if ($bolehInput) {
            $produkIds = collect([old('produk_asal_id'), old('produk_tujuan_id')])
                ->filter()
                ->unique();
            $produkLabels = Produk::with('produkModel.kategori')
                ->whereIn('id', $produkIds)
                ->get()
                ->mapWithKeys(fn (Produk $produk) => [$produk->id => $produk->namaLengkap]);
        }

        $riwayat = ProdukStok::with(['produk.produkModel.kategori', 'cabang', 'user'])
            ->where('kode', 'pch')
            ->where('kurang', '>', 0)
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $hasilPerMutasi = ProdukStok::with(['produk.produkModel.kategori'])
            ->whereIn('detail_id', $riwayat->pluck('id'))
            ->where('kode', 'pch')
            ->where('tambah', '>', 0)
            ->get()
            ->keyBy('detail_id');

        return view('admin.produkStoks.pecahStok', compact('bolehInput', 'cabangs', 'produkLabels', 'riwayat', 'hasilPerMutasi'));
    }

    public function store(Request $request, StokService $stokService)
    {
        abort_unless(can_input_pindah_gudang(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'gudang_id' => ['required', 'integer', Rule::exists('cabangs', 'id')->where('status', 1)],
            'produk_asal_id' => ['required', 'integer', 'exists:produks,id', 'different:produk_tujuan_id'],
            'produk_tujuan_id' => ['required', 'integer', 'exists:produks,id'],
            'jumlah_asal' => ['required', 'integer', 'min:1', 'max:100000'],
            'rasio' => ['required', 'integer', 'min:1', 'max:1000000'],
            'keterangan' => ['nullable', 'string', 'max:150'],
        ], [
            'produk_asal_id.different' => 'Produk asal dan produk tujuan tidak boleh sama.',
        ]);

        $produk = Produk::with('produkModel.kategori')
            ->whereIn('id', [(int) $data['produk_asal_id'], (int) $data['produk_tujuan_id']])
            ->get()
            ->keyBy('id');

        $errors = [];
        foreach (['produk_asal_id', 'produk_tujuan_id'] as $field) {
            $item = $produk->get((int) $data[$field]);

            if (!$item) {
                $errors[$field] = 'Produk tidak ditemukan.';
                continue;
            }

            if ((int) $item->status !== 1 || (int) ($item->produkModel?->stok ?? 0) !== 1) {
                $errors[$field] = 'Pilih produk stok yang aktif.';
            }
        }

        $produkAsal = $produk->get((int) $data['produk_asal_id']);
        $produkTujuan = $produk->get((int) $data['produk_tujuan_id']);
        $jumlahAsal = (int) $data['jumlah_asal'];
        $rasio = (int) $data['rasio'];
        $hasil = $jumlahAsal * $rasio;

        if ($hasil > 1000000) {
            $errors['rasio'] = 'Hasil pecahan terlalu besar (maksimal 1.000.000 satuan).';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $gudang = Cabang::findOrFail($data['gudang_id']);
        $satuanAsal = $produkAsal->produkModel?->satuan ?: 'unit';
        $satuanTujuan = $produkTujuan->produkModel?->satuan ?: 'unit';
        $labelAsal = $produkAsal->namaLengkap;
        $labelTujuan = $produkTujuan->namaLengkap;
        $note = trim($data['keterangan'] ?? '');
        $ringkasan = $jumlahAsal . ' ' . $satuanAsal . ' = ' . $hasil . ' ' . $satuanTujuan;

        DB::transaction(function () use (
            $data,
            $jumlahAsal,
            $hasil,
            $produkAsal,
            $produkTujuan,
            $labelAsal,
            $labelTujuan,
            $ringkasan,
            $note,
            $stokService
        ) {
            // Serialkan pecah stok untuk produk yang sama agar stok tidak terpakai ganda.
            $terkunci = Produk::whereIn('id', [(int) $data['produk_asal_id'], (int) $data['produk_tujuan_id']])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $produkAsal = $terkunci->get($produkAsal->id) ?? $produkAsal;
            $produkTujuan = $terkunci->get($produkTujuan->id) ?? $produkTujuan;

            $gudangId = (int) $data['gudang_id'];
            $tersedia = $stokService->saldoTersedia($produkAsal->id, $gudangId);

            if ($jumlahAsal > $tersedia) {
                throw ValidationException::withMessages([
                    'jumlah_asal' => "Stok gudang ini hanya tersedia {$tersedia} {$produkAsal->produkModel?->satuan} untuk {$labelAsal}.",
                ]);
            }

            $hppAsal = (int) ($produkAsal->hpp ?? 0);

            if ($hppAsal > 0 && $hasil > 0) {
                $hppPerUnit = (int) round(($hppAsal * $jumlahAsal) / $hasil);
                $produkTujuan->updateHpp($hppPerUnit, $hasil, $gudangId);
            }

            $keluar = $stokService->kurang(
                $produkAsal->id,
                $gudangId,
                $jumlahAsal,
                'pch',
                Str::limit('Pecah stok ke ' . $labelTujuan . ' (' . $ringkasan . ')' . ($note === '' ? '' : ' - ' . $note), 255, '')
            );

            $stokService->tambah(
                $produkTujuan->id,
                $gudangId,
                $hasil,
                'pch',
                Str::limit('Pecah stok dari ' . $labelAsal . ' (' . $ringkasan . ')' . ($note === '' ? '' : ' - ' . $note), 255, ''),
                $keluar->id
            );
        });

        return redirect()->route('pecahStok.index')
            ->withSuccess('Berhasil memecah ' . $ringkasan . ' ke stok ' . $labelTujuan . ' di gudang ' . $gudang->nama . '.');
    }
}
