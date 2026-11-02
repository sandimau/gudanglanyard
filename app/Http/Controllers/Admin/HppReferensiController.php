<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HppReferensiController extends Controller
{
    public const KOLOM = [
        'bahan' => ['tali' => 'Tali', 'print1' => 'Print 1S', 'print2' => 'Print 2S (tambahan)', 'press' => 'Press', 'cantolan' => 'Cantolan', 'mataItik' => 'Mata itik', 'stopper' => 'Stopper', 'finishing' => 'Finishing'],
        'idcard' => ['kertas' => 'Kertas', 'print' => 'Print', 'overlay' => 'Overlay', 'penebal' => 'Penebal', 'plong' => 'Plong', 'press' => 'Press', 'finishing' => 'Finishing'],
        'aksesori' => ['harga' => 'Harga modal'],
        'resin' => ['stiker' => 'Stiker doff', 'cetak' => 'Cetak', 'resin' => 'Resin', 'cutting' => 'Cutting', 'makloon' => 'Makloon'],
        'tali' => ['modal' => 'Modal', 'bagi' => 'Bagi'],
        'idcard_satuan' => ['modal' => 'Harga Bahan Baku', 'bagi' => 'Bagi'],
    ];

    public function index(Request $request)
    {
        $jenis = $request->query('jenis', 'bahan');
        abort_unless(in_array($jenis, ['bahan', 'idcard'], true), 404);

        $data = DB::table('hpp_referensis')->where('jenis', $jenis)->orderBy('urutan')->get();
        $aksesori = $jenis === 'idcard' ? DB::table('hpp_referensis')->where('jenis', 'aksesori')->orderBy('urutan')->get() : collect();
        $resin = $jenis === 'idcard' ? DB::table('hpp_referensis')->where('jenis', 'resin')->orderBy('urutan')->get() : collect();
        if ($jenis === 'bahan') {
            $this->refreshModalTali();
            $data = DB::table('hpp_referensis')->where('jenis', 'bahan')->orderBy('urutan')->get();
        }
        $tali = $jenis === 'bahan' ? DB::table('hpp_referensis')->where('jenis', 'tali')->orderBy('urutan')->orderBy('id')->get() : collect();
        $bahanTali = $jenis === 'bahan' ? $data->pluck('nama', 'id') : collect();
        $produkTali = $jenis === 'bahan' ? DB::table('produks as p')
            ->join('produk_models as m', 'm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as k', 'k.id', '=', 'm.kategori_id')
            ->whereIn('p.id', $tali->pluck('produk_id_tali')->filter()->all())
            ->select('p.id', 'p.nama as varian', 'p.hpp', 'm.id as model_id', 'm.nama as model', 'm.harga as harga_jual', 'm.satuan', 'k.id as kategori_id', 'k.nama as kategori')
            ->selectSub(DB::table('belanja_details')->select('harga')->whereColumn('produk_id', 'p.id')->whereNull('deleted_at')->orderByDesc('id')->limit(1), 'harga_belanja_terakhir')
            ->get()->keyBy('id') : collect();
        $idcardSatuan = $jenis === 'idcard' ? DB::table('hpp_referensis')->where('jenis', 'idcard_satuan')->orderBy('urutan')->orderBy('id')->get() : collect();
        return view('admin.hpp.index', compact('jenis', 'data', 'aksesori', 'resin', 'tali', 'bahanTali', 'produkTali', 'idcardSatuan'));
    }

    public function storeTali(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'bagi' => ['required', 'numeric', 'gt:0', 'max:999999999', 'decimal:0,8'],
            'produk_id_tali' => ['required', 'integer', Rule::exists('produks', 'id')],
            'bahan_id_tali' => ['nullable', 'integer', Rule::in(DB::table('hpp_referensis')->where('jenis', 'bahan')->pluck('id')->all())],
        ]);
        $modal = $this->hargaBelanjaTerakhir((int) $validated['produk_id_tali']);
        if ($modal === null) {
            throw \Illuminate\Validation\ValidationException::withMessages(['produk_id_tali' => 'Produk belum memiliki riwayat belanja.']);
        }
        DB::transaction(function () use ($validated, $modal) {
            $produkId = (int) $validated['produk_id_tali'];
            DB::table('hpp_referensis')->insert([
                'jenis' => 'tali', 'nama' => $validated['nama'],
                'produk_id_tali' => $produkId,
                'bahan_id_tali' => ($validated['bahan_id_tali'] ?? null) ?: null,
                'biaya' => json_encode(['modal' => $modal, 'bagi' => (float) $validated['bagi']]),
                'urutan' => (int) DB::table('hpp_referensis')->where('jenis', 'tali')->max('urutan') + 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if (!empty($validated['bahan_id_tali'])) {
                $this->syncSatuanTali((int) $validated['bahan_id_tali'], $modal, (float) $validated['bagi']);
            }
        });

        return redirect()->route('hpp.index', ['jenis' => 'bahan'])->with('success', 'Perhitungan bahan berhasil ditambahkan.');
    }

    public function storeIdcardSatuan(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'modal' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,8'],
            'bagi' => ['nullable', 'numeric', 'gt:0', 'max:999999999', 'decimal:0,8'],
        ]);
        DB::table('hpp_referensis')->insert([
            'jenis' => 'idcard_satuan', 'nama' => $validated['nama'],
            'biaya' => json_encode([
                'modal' => isset($validated['modal']) ? round((float) $validated['modal'], 8) : null,
                'bagi' => isset($validated['bagi']) ? round((float) $validated['bagi'], 8) : null,
            ]),
            'urutan' => (int) DB::table('hpp_referensis')->where('jenis', 'idcard_satuan')->max('urutan') + 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return redirect()->route('hpp.index', ['jenis' => 'idcard'])->with('success', 'Perhitungan ID Card berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('hpp_referensis')->where('id', $id)->first();
        abort_unless($row && isset(self::KOLOM[$row->jenis]), 404);

        $rules = ['nama' => ['required', 'string', 'max:255'], 'biaya' => ['required', 'array']];
        if ($row->jenis === 'bahan') {
            $ids = $this->produkBahan()->pluck('p.id')->all();
            $rules['produk_id_1s'] = ['nullable', 'integer', Rule::in($ids)];
            $rules['produk_id_2s'] = ['nullable', 'integer', Rule::in($ids), 'different:produk_id_1s'];
        }
        if ($row->jenis === 'tali') {
            $rules['produk_id_tali'] = ['nullable', 'integer', Rule::exists('produks', 'id')];
            $rules['bahan_id_tali'] = ['nullable', 'integer', Rule::in(DB::table('hpp_referensis')->where('jenis', 'bahan')->pluck('id')->all())];
        }
        foreach (self::KOLOM[$row->jenis] as $key => $label) {
            $rules['biaya.'.$key] = [$row->jenis === 'idcard_satuan' ? 'nullable' : 'required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,8'];
        }
        if ($row->jenis === 'tali') {
            $rules['biaya.bagi'] = ['required', 'numeric', 'gt:0', 'max:999999999', 'decimal:0,8'];
            unset($rules['biaya.modal']);
        }
        if ($row->jenis === 'idcard_satuan') {
            $rules['biaya.bagi'] = ['nullable', 'numeric', 'gt:0', 'max:999999999', 'decimal:0,8'];
        }
        $validated = $request->validate($rules);
        $biaya = [];
        foreach (self::KOLOM[$row->jenis] as $key => $label) {
            $biaya[$key] = isset($validated['biaya'][$key]) ? round((float) $validated['biaya'][$key], 8) : null;
        }
        if ($row->jenis === 'tali') {
            $produkId = $request->exists('produk_id_tali') ? (($validated['produk_id_tali'] ?? null) ?: null) : $row->produk_id_tali;
            $biaya['modal'] = $produkId ? $this->hargaBelanjaTerakhir((int) $produkId) : null;
            if ($biaya['modal'] === null) {
                throw \Illuminate\Validation\ValidationException::withMessages(['produk_id_tali' => 'Pilih produk dengan riwayat belanja untuk menghitung Modal.']);
            }
        }
        $changes = ['nama' => $validated['nama'], 'biaya' => json_encode($biaya), 'updated_at' => now()];
        if ($row->jenis === 'tali' && $request->exists('produk_id_tali')) {
            $changes['produk_id_tali'] = ($validated['produk_id_tali'] ?? null) ?: null;
        }
        if ($row->jenis === 'tali' && $request->exists('bahan_id_tali')) {
            $changes['bahan_id_tali'] = ($validated['bahan_id_tali'] ?? null) ?: null;
        }
        if ($row->jenis === 'bahan' && ($request->exists('produk_id_1s') || $request->exists('produk_id_2s'))) {
            $changes['produk_id_1s'] = $validated['produk_id_1s'] ?? null;
            $changes['produk_id_2s'] = $validated['produk_id_2s'] ?? null;
        }
        DB::transaction(function () use ($id, $row, $changes, $biaya) {
            DB::table('hpp_referensis')->where('id', $id)->update($changes);
            if ($row->jenis === 'tali') {
                $produkId = array_key_exists('produk_id_tali', $changes) ? $changes['produk_id_tali'] : $row->produk_id_tali;
                $bahanId = array_key_exists('bahan_id_tali', $changes) ? $changes['bahan_id_tali'] : $row->bahan_id_tali;
                if ($bahanId) {
                    $this->syncSatuanTali((int) $bahanId, $biaya['modal'], $biaya['bagi']);
                }
            }
        });

        return redirect()->route('hpp.index', ['jenis' => $row->jenis === 'tali' ? 'bahan' : (in_array($row->jenis, ['aksesori', 'resin', 'idcard_satuan'], true) ? 'idcard' : $row->jenis)])->with('success', 'Biaya HPP berhasil disimpan.');
    }

    public function terapkan(Request $request, $id)
    {
        $row = DB::table('hpp_referensis')->where('id', $id)->where('jenis', 'bahan')->first();
        abort_unless($row, 404);
        $validated = $request->validate(['sisi' => ['required', 'in:1s,2s']]);
        $produkId = $validated['sisi'] === '1s' ? $row->produk_id_1s : $row->produk_id_2s;
        if (!$produkId || !DB::table('produks')->where('id', $produkId)->exists()) {
            return redirect()->route('hpp.index')->withErrors('Pilih dan simpan SKU produk terlebih dahulu.');
        }

        $biaya = json_decode($row->biaya, true);
        // produks.hpp bertipe integer; pembulatan hanya saat menerapkan ke katalog.
        $hpp = (int) round(array_sum($biaya) - ($validated['sisi'] === '1s' ? $biaya['print2'] : 0));
        DB::table('produks')->where('id', $produkId)->update(['hpp' => $hpp, 'updated_at' => now()]);

        return redirect()->route('hpp.index')->with('success', 'HPP SKU '.$produkId.' diperbarui menjadi Rp'.number_format($hpp, 0, ',', '.').'. Transaksi lama tidak berubah.');
    }

    public function cariProduk(Request $request)
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = trim($request->query('q'));
        $escaped = addcslashes($term, '%_\\');
        $sku = ltrim($term, '#');

        return response()->json($this->produkBahan()
            ->where(function ($query) use ($sku, $escaped) {
                $query->where('p.id', ctype_digit($sku) ? (int) $sku : 0)
                    ->orWhere('p.nama', 'like', '%'.$escaped.'%')
                    ->orWhere('m.nama', 'like', '%'.$escaped.'%')
                    ->orWhere('k.nama', 'like', '%'.$escaped.'%');
            })
            ->select('p.id', 'p.nama as varian', 'p.hpp', 'm.nama as model', 'k.nama as kategori')
            ->orderBy('p.id')->limit(20)->get());
    }

    public function cariProdukTali(Request $request)
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = trim($request->query('q'));
        $escaped = addcslashes($term, '%_\\');
        $sku = ltrim($term, '#');

        return response()->json(DB::table('produks as p')
            ->join('produk_models as m', 'm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as k', 'k.id', '=', 'm.kategori_id')
            ->where(function ($query) use ($sku, $escaped) {
                $query->where('p.id', ctype_digit($sku) ? (int) $sku : 0)
                    ->orWhere('p.nama', 'like', '%'.$escaped.'%')
                    ->orWhere('m.nama', 'like', '%'.$escaped.'%')
                    ->orWhere('k.nama', 'like', '%'.$escaped.'%');
            })
            ->select('p.id', 'p.nama as varian', 'p.hpp', 'm.id as model_id', 'm.nama as model', 'm.harga as harga_jual', 'm.satuan', 'k.id as kategori_id', 'k.nama as kategori')
            ->selectSub(DB::table('belanja_details')->select('harga')->whereColumn('produk_id', 'p.id')->whereNull('deleted_at')->orderByDesc('id')->limit(1), 'harga_belanja_terakhir')
            ->orderBy('p.id')->limit(20)->get());
    }

    private function produkBahan()
    {
        return DB::table('produks as p')
            ->join('produk_models as m', 'm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as k', 'k.id', '=', 'm.kategori_id')
            ->join('produk_kategori_utamas as u', 'u.id', '=', 'k.kategori_utama_id')
            ->where(function ($query) {
                $query->where('u.nama', 'like', '%lanyard%')
                    ->orWhere('u.nama', 'like', '%bahan baku%');
            });
    }

    private function hargaBelanjaTerakhir(int $produkId): ?float
    {
        $harga = DB::table('belanja_details')->where('produk_id', $produkId)
            ->whereNull('deleted_at')->orderByDesc('id')->value('harga');

        return $harga === null ? null : (float) $harga;
    }

    private function refreshModalTali(): void
    {
        foreach (DB::table('hpp_referensis')->where('jenis', 'tali')->whereNotNull('produk_id_tali')->get() as $tali) {
            $modal = $this->hargaBelanjaTerakhir((int) $tali->produk_id_tali);
            $biaya = json_decode($tali->biaya, true);
            if (($biaya['modal'] === null && $modal === null) || ($modal !== null && $biaya['modal'] !== null && (float) $biaya['modal'] == $modal)) {
                continue;
            }
            $biaya['modal'] = $modal;
            DB::transaction(function () use ($tali, $biaya) {
                DB::table('hpp_referensis')->where('id', $tali->id)->update(['biaya' => json_encode($biaya), 'updated_at' => now()]);
                if ($tali->bahan_id_tali && $biaya['modal'] !== null && (float) $biaya['bagi'] > 0) {
                    $this->syncSatuanTali((int) $tali->bahan_id_tali, (float) $biaya['modal'], (float) $biaya['bagi']);
                }
            });
        }
    }

    private function syncSatuanTali(int $bahanId, float $modal, float $bagi): void
    {
        $bahan = DB::table('hpp_referensis')->where('jenis', 'bahan')->where('id', $bahanId)->first();
        if (!$bahan) {
            return;
        }

        $biaya = json_decode($bahan->biaya, true);
        $biaya['tali'] = round($modal / $bagi, 8);
        DB::table('hpp_referensis')->where('id', $bahanId)->update(['biaya' => json_encode($biaya), 'updated_at' => now()]);
    }
}
