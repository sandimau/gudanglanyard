<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunDetail;
use App\Models\Hutang;
use App\Models\Produk;
use App\Models\ProdukAset;
use App\Models\ProdukAsetDetail;
use App\Services\Accounting\AsetAkunService;
use App\Services\Accounting\AsetJualPostingService;
use App\Services\Accounting\AsetPostingService;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AsetController extends Controller
{
    public function __construct(
        private AsetPostingService $postingService,
        private AsetJualPostingService $jualPostingService,
        private AsetAkunService $akunService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $q = trim((string) $request->get('q', ''));

        $asets = ProdukAset::with(['produk.produkModel.kategori', 'kontak'])
            ->aktif()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->whereHas('produk', function ($produk) use ($q) {
                        $produk->where('nama', 'like', '%' . $q . '%')
                            ->orWhereHas('produkModel', function ($model) use ($q) {
                                $model->where('nama', 'like', '%' . $q . '%');
                            });
                    })->orWhereHas('kontak', function ($kontak) use ($q) {
                        $kontak->where('nama', 'like', '%' . $q . '%');
                    });
                });
            })
            ->orderByDesc('tanggal_beli')
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($request->query());

        $totalSaldo = ProdukAset::aktif()->sum('saldo');
        $totalAset = ProdukAset::aktif()->count();

        return view('admin.asets.index', compact('asets', 'totalSaldo', 'totalAset', 'q'));
    }

    public function habis(Request $request)
    {
        $this->authorizeAccess();

        $asets = ProdukAset::with(['produk.produkModel.kategori', 'kontak', 'details'])
            ->habis()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($request->query());

        return view('admin.asets.habis', compact('asets'));
    }

    public function create()
    {
        $this->authorizeManage();

        $kas = $this->daftarKas();

        return view('admin.asets.create', compact('kas'));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'kontak_id' => 'required|exists:kontaks,id',
            'harga' => 'required|integer|min:1|max:2147483647',
            'jumlah' => 'required|integer|min:1|max:1000000',
            'waktuPenyusutan' => 'required|integer|min:1|max:100',
            'pembayaran' => 'required|in:tunai,hutang',
            'akun_detail_id' => 'nullable|required_if:pembayaran,tunai|exists:akun_details,id',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $nilai = (int) $data['harga'] * (int) $data['jumlah'];

        if ($nilai <= 0 || $nilai > 2147483647) {
            throw ValidationException::withMessages([
                'harga' => 'Nilai awal aset (harga x jumlah) harus di antara Rp1 dan Rp2.147.483.647.',
            ]);
        }

        DB::transaction(function () use ($data, $nilai) {
            $aset = ProdukAset::create([
                'produk_id' => $data['produk_id'],
                'kontak_id' => $data['kontak_id'],
                'tanggal_beli' => now()->toDateString(),
                'waktuPenyusutan' => $data['waktuPenyusutan'],
                'harga' => $data['harga'],
                'jumlah' => $data['jumlah'],
                'saldo' => $nilai,
                'keterangan' => $data['keterangan'] ?? null,
                'akun_detail_id' => $data['pembayaran'] === 'tunai' ? $data['akun_detail_id'] : null,
            ]);

            ProdukAsetDetail::create([
                'aset_id' => $aset->id,
                'tanggal' => now()->toDateString(),
                'kode' => 'sld',
                'keterangan' => 'Saldo awal',
                'debet' => $nilai,
                'kredit' => 0,
            ]);

            if ($data['pembayaran'] === 'hutang') {
                $akunHutang = $this->akunService->hutangUsaha();

                Hutang::create([
                    'kontak_id' => $data['kontak_id'],
                    'tanggal' => now()->toDateString(),
                    'jumlah' => $nilai,
                    'keterangan' => 'Pembelian aset ' . $aset->nama_produk,
                    'jenis' => 'aset',
                    'akun_detail_id' => $akunHutang->id,
                    'detail_id' => $aset->id,
                ]);
            }

            $this->postingService->postPembelian($aset);
        });

        return redirect()->route('aset.index')->with('success', 'Aset berhasil ditambahkan.');
    }

    public function riwayat(ProdukAset $aset)
    {
        $this->authorizeAccess();

        $aset->load(['produk.produkModel.kategori', 'kontak', 'akunDetail']);

        $saldo = 0;
        $details = $aset->details()
            ->with('jurnal')
            ->orderBy('id')
            ->get()
            ->map(function (ProdukAsetDetail $detail) use (&$saldo) {
                $saldo += (int) $detail->debet - (int) $detail->kredit;
                $detail->saldo_berjalan = $saldo;

                return $detail;
            });

        return view('admin.asets.riwayat', compact('aset', 'details'));
    }

    public function edit(ProdukAset $aset)
    {
        $this->authorizeManage();

        $aset->load(['produk.produkModel.kategori', 'kontak']);

        return view('admin.asets.edit', compact('aset'));
    }

    public function update(Request $request, ProdukAset $aset)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'jenis_penyesuaian' => 'required|in:tambah,kurang',
            'nominal' => 'required|integer|min:1|max:2147483647',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $saldo = $aset->saldoDetail();

        if ($data['jenis_penyesuaian'] === 'kurang' && (int) $data['nominal'] > $saldo) {
            throw ValidationException::withMessages([
                'nominal' => 'Nilai pengurangan tidak boleh melebihi saldo aset saat ini (Rp ' . number_format($saldo, 0, ',', '.') . ').',
            ]);
        }

        DB::transaction(function () use ($aset, $data) {
            $tambah = $data['jenis_penyesuaian'] === 'tambah';

            ProdukAsetDetail::create([
                'aset_id' => $aset->id,
                'tanggal' => now()->toDateString(),
                'kode' => $tambah ? 'pls' : 'sst',
                'keterangan' => ($data['keterangan'] ?? null) ?: ($tambah ? 'Penyesuaian penambahan nilai' : 'Penyesuaian pengurangan nilai'),
                'debet' => $tambah ? (int) $data['nominal'] : 0,
                'kredit' => $tambah ? 0 : (int) $data['nominal'],
            ]);
        });

        return redirect()->route('aset.riwayat', $aset)
            ->with('success', 'Penyesuaian aset berhasil disimpan.');
    }

    public function jual(ProdukAset $aset)
    {
        $this->authorizeManage();

        $aset->load(['produk.produkModel.kategori', 'kontak']);

        if ($aset->saldoDetail() <= 0) {
            return redirect()->route('aset.index')->with('error', 'Aset ini sudah tidak memiliki nilai buku.');
        }

        $kas = $this->daftarKas();
        $nilaiBuku = $aset->saldoDetail();
        $totalPenyusutan = (int) $aset->details()->where('kode', 'sst')->sum('kredit');

        return view('admin.asets.jual', compact('aset', 'kas', 'nilaiBuku', 'totalPenyusutan'));
    }

    public function jualStore(Request $request, ProdukAset $aset)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'harga_jual' => 'required|integer|min:0|max:2147483647',
            'akun_detail_id' => 'required|exists:akun_details,id',
            'tanggal' => 'nullable|date',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $akunKasValid = AkunDetail::query()
            ->whereKey($data['akun_detail_id'])
            ->whereHas('akun_kategori', function ($query) {
                $query->whereIn('id', [1, 8]);
            })
            ->exists();

        if (! $akunKasValid) {
            throw ValidationException::withMessages([
                'akun_detail_id' => 'Akun penerima harus akun kas.',
            ]);
        }

        $nilaiBuku = $aset->saldoDetail();

        if ($nilaiBuku <= 0) {
            throw ValidationException::withMessages([
                'harga_jual' => 'Aset ini sudah tidak memiliki nilai buku.',
            ]);
        }

        $hargaJual = (int) $data['harga_jual'];
        $hargaPerolehan = (int) $aset->harga * (int) $aset->jumlah;
        $totalPenyusutan = (int) $aset->details()->where('kode', 'sst')->sum('kredit');
        $totalPenambahan = (int) $aset->details()->where('kode', 'pls')->sum('debet');
        $nilaiAset = $hargaPerolehan + $totalPenambahan;
        $selisih = $hargaJual - $nilaiBuku;

        DB::transaction(function () use ($aset, $data, $hargaJual, $totalPenyusutan, $nilaiAset, $nilaiBuku) {
            $detail = ProdukAsetDetail::create([
                'aset_id' => $aset->id,
                'tanggal' => $data['tanggal'] ?? now()->toDateString(),
                'kode' => 'jual',
                'keterangan' => ($data['keterangan'] ?? null) ?: 'Penjualan aset',
                'debet' => 0,
                'kredit' => $nilaiBuku,
            ]);

            $this->jualPostingService->post(
                $aset,
                $detail,
                $hargaJual,
                (int) $data['akun_detail_id'],
                $totalPenyusutan,
                $nilaiAset,
                $nilaiBuku
            );
        });

        $pesan = $selisih >= 0
            ? 'Aset terjual dengan keuntungan Rp ' . number_format($selisih, 0, ',', '.') . '.'
            : 'Aset terjual dengan kerugian Rp ' . number_format(abs($selisih), 0, ',', '.') . '.';

        return redirect()->route('aset.index')->with('success', $pesan);
    }

    public function apiProduk(Request $request)
    {
        $this->authorizeAccess();

        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $produk = Produk::select(
            'produk_models.nama',
            'produk_models.satuan',
            'produks.nama as varian',
            'produks.id',
            'produk_kategoris.nama as kategori',
            DB::raw("COALESCE((SELECT harga FROM belanja_details WHERE produk_id = produks.id AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 1), produk_models.harga) as harga")
        )
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->join('produk_kategoris', 'produk_models.kategori_id', '=', 'produk_kategoris.id')
            ->where('produks.status', 1)
            ->where(function ($query) use ($q) {
                $query->where('produks.nama', 'like', '%' . $q . '%')
                    ->orWhere('produk_models.nama', 'like', '%' . $q . '%')
                    ->orWhere('produk_kategoris.nama', 'like', '%' . $q . '%');
            })
            ->limit(20)
            ->get();

        return response()->json($produk);
    }

    public function apiKontak(Request $request)
    {
        $this->authorizeAccess();

        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $kontak = \App\Models\Kontak::select('id', 'nama', 'perusahaan')
            ->where('supplier', 1)
            ->where('nama', 'like', '%' . $q . '%')
            ->limit(20)
            ->get();

        return response()->json($kontak);
    }

    private function daftarKas()
    {
        return AkunDetail::query()
            ->whereHas('akun_kategori', function ($query) {
                $query->whereIn('id', [1, 8]);
            })
            ->orderBy('nama')
            ->get();
    }

    private function authorizeAccess(): void
    {
        abort_if(
            Gate::denies('aset_access') && ! auth()->user()?->can('keuangan'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );
    }

    private function authorizeManage(): void
    {
        abort_if(
            Gate::denies('aset_manage') && ! auth()->user()?->can('keuangan'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );
    }
}
