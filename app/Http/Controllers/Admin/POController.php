<?php

namespace App\Http\Controllers\Admin;

use App\Models\Po;
use App\Models\Hutang;
use App\Models\Kontak;
use App\Models\Produk;
use App\Models\Belanja;
use App\Models\PoDetail;
use App\Models\BukuBesar;
use App\Models\AkunDetail;
use App\Services\StokService;
use App\Models\HutangDetail;
use Illuminate\Http\Request;
use App\Models\BelanjaDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class POController extends Controller
{
    public function index()
    {
        $poProses = Po::where('status', 'proses')->get();
        $poSelesai = Po::where('status', 'selesai')->get();

        return view('admin.po.index', compact('poProses', 'poSelesai'));
    }

    public function create()
    {
        return view('admin.po.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kontak_id' => 'required',
            'produk_id' => 'required',
            'jumlah' => 'required',
        ]);

        $po['kontak_id'] = $request->kontak_id;
        $po['status'] = 'proses';
        $po['ket'] = $request->ket;
        $po['tglKedatangan'] = Date('y:m:d', strtotime('+7 days'));

        $dataPo = Po::create($po);

        //insert po detail
        $dataDetail['po_id'] = $dataPo->id;
        $dataDetail['produk_id'] = request()->produk_id;
        $dataDetail['jumlah'] = request()->jumlah;
        PoDetail::create($dataDetail);

        return redirect()->route('po.index')->withSuccess(__('PO created successfully.'));
    }

    public function edit(Po $po)
    {
        return view('admin.po.edit', compact('po'));
    }

    public function update(Request $request, Po $po)
    {
        $po->update($request->all());

        return redirect()->route('po.show', $po->id)->withSuccess(__('PO updated successfully.'));
    }

    public function show($po)
    {
        $po = Po::with(['hutang.details.akun_detail', 'hutang.details.user', 'hutang.akun_detail', 'belanja.relasihutang'])->findOrFail($po);
        return view('admin.po.show', compact('po'));
    }

    public function selesai(Po $po)
    {
        $po->update(['status' => 'selesai']);

        return redirect()->route('po.index')->withSuccess(__('PO selesai successfully.'));
    }

    public function detailEdit($po, $detail)
    {
        $detail = PoDetail::findOrFail($detail);
        return view('admin.po.detail', compact('detail'));
    }

    public function detailUpdate(Request $request, $po, $detail)
    {
        $detail = PoDetail::findOrFail($detail);
        $detail->update($request->all());

        return redirect()->route('po.show', $po)->withSuccess(__('PO detail updated successfully.'));
    }

    public function detailDestroy($po, $detail)
    {
        $poDetail = PoDetail::findOrFail($detail);
        $poDetail->delete();

        return redirect()->route('po.show', $po)->withSuccess(__('PO detail deleted successfully.'));
    }

    public function detailCreate($po)
    {
        return view('admin.po.createDetail', compact('po'));
    }

    public function detailStore(Request $request, $po)
    {
        $request->validate([
            'produk_id' => 'required',
            'jumlah' => 'required',
        ]);

        $dataDetail['po_id'] = $po;
        $dataDetail['produk_id'] = $request->produk_id;
        $dataDetail['jumlah'] = $request->jumlah;
        PoDetail::create($dataDetail);

        return redirect()->route('po.show', $po)->withSuccess(__('PO detail created successfully.'));
    }

    public function deposit($po)
    {
        $kas = AkunDetail::where('akun_kategori_id', 1)->get();
        return view('admin.po.deposit', compact('po', 'kas'));
    }

    public function depositStore(Request $request, $po)
    {
        $request->validate([
            'jumlah' => 'required',
            'akun_detail_id' => 'required',
        ]);

        $po = Po::findOrFail($po);

        DB::transaction(function () use ($request, $po) {
            BukuBesar::create([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'deposit ke ' . $po->kontak->nama,
                'kredit' => $request->jumlah,
                'debet' => 0,
                'kode' => 'dpst',
                'detail_id' => $po->id,
            ]);

            $hutang = Hutang::create([
                'kontak_id' => $po->kontak_id,
                'akun_detail_id' => $request->akun_detail_id,
                'tanggal' => date('Y-m-d'),
                'jumlah' => $request->jumlah,
                'keterangan' => 'deposit ke ' . $po->kontak->nama,
                'jenis' => 'piutang',
            ]);

            DB::table('po_deposit')->insert([
                'po_id' => $po->id,
                'hutang_id' => $hutang->id
            ]);
        });

        return redirect()->route('po.show', $po)->withSuccess(__('Deposit created successfully.'));
    }

    public function belanjaCreate(Po $po)
    {
        $kas = AkunDetail::where('akun_kategori_id', 1)->get();
        // Ambil total hutang deposit
        $totalHutang = DB::table('po_deposit')
            ->join('hutangs', 'po_deposit.hutang_id', '=', 'hutangs.id')
            ->where('po_deposit.po_id', $po->id)
            ->sum('hutangs.jumlah');

        // Ambil total pembayaran yang sudah dilakukan
        $totalPembayaran = DB::table('po_deposit')
            ->join('hutangs', 'po_deposit.hutang_id', '=', 'hutangs.id')
            ->join('hutang_details', 'hutangs.id', '=', 'hutang_details.hutang_id')
            ->where('po_deposit.po_id', $po->id)
            ->sum('hutang_details.jumlah');

                // Hitung sisa deposit yang masih bisa digunakan
        $deposit = $totalHutang - $totalPembayaran;
        return view('admin.po.belanja', compact('po', 'kas', 'deposit'));
    }

    public function belanjaStore(Request $request, $po)
    {
        $request->validate([
            'total' => 'required|numeric|min:0|not_in:0',
            'diskon' => 'lte:total|nullable',
            'pembayaran' => 'nullable|numeric|min:0|lte:total',
            'deposit' => 'nullable|numeric|min:0'
        ]);

        $po = Po::findOrFail($po);
        $deposit_used = 0; // Inisialisasi variabel di luar transaction

        DB::transaction(function () use ($request, $po, &$deposit_used) {
            //insert into belanja table
            $belanja = Belanja::create([
                'nota' => $request->nota ?: rand(1000000, 100),
                'diskon' => $request->diskon ?: 0,
                'total' => $request->total,
                'kontak_id' => $po->kontak_id,
                'akun_detail_id' => $request->akun_detail_id,
                'pembayaran' => $request->pembayaran,
                'tanggal_beli' => $request->tanggal_beli,
            ]);

            // Proses deposit terlebih dahulu jika ada
            $deposit_available = $request->deposit ?? 0; // Deposit yang tersedia
            $deposit_used = 0; // Inisialisasi deposit yang digunakan
            $sisa_deposit = $deposit_available;

            $sisa_belanja = $request->total;

            if($deposit_available > 0){
                $poDeposit = DB::table('po_deposit')
                    ->where('po_id', $po->id)
                    ->get();

                foreach($poDeposit as $item){
                    $hutang = Hutang::find($item->hutang_id);

                    // Skip jika hutang tidak ditemukan
                    if (!$hutang) {
                        continue;
                    }

                    // Hitung sisa hutang yang belum dibayar
                    $total_pembayaran_hutang = $hutang->details()->sum('jumlah');
                    $sisa_hutang = $hutang->jumlah - $total_pembayaran_hutang;

                    // Batasi penggunaan deposit hanya sebesar sisa belanja
                    if ($sisa_hutang > 0 && $sisa_belanja > 0) {
                        $jumlah_bayar = min($sisa_deposit, $sisa_hutang, $sisa_belanja);

                        if($jumlah_bayar > 0) {
                            HutangDetail::create([
                                'hutang_id' => $hutang->id,
                                'akun_detail_id' => $request->akun_detail_id,
                                'tanggal' => $request->tanggal_beli,
                                'jumlah' => $jumlah_bayar,
                                'keterangan' => 'Deposit belanja PO ' . $po->kode
                            ]);

                            $sisa_deposit -= $jumlah_bayar;
                            $deposit_used += $jumlah_bayar;
                            $sisa_belanja -= $jumlah_bayar;
                        }
                    }

                    if($sisa_deposit <= 0 || $sisa_belanja <= 0) {
                        break;
                    }
                }
            }

            // Proses pembayaran tunai jika ada
            if (($request->pembayaran ?? 0) > 0 && $request->akun_detail_id) {
                BukuBesar::create([
                    'akun_detail_id' => $request->akun_detail_id,
                    'ket' => 'pembelian ke ' . $po->kontak->nama,
                    'kredit' => $request->pembayaran,
                    'debet' => 0,
                    'kode' => 'blj',
                    'detail_id' => $belanja->id,
                    'user_id' => auth()->user()->id,
                ]);
            }

            // Hitung total pembayaran (deposit + pembayaran tunai)
            $total_pembayaran = $deposit_used + ($request->pembayaran ?? 0);
            $sisa_hutang = $request->total - $total_pembayaran;

            // Buat hutang jika masih ada sisa yang belum dibayar
            // Contoh: Total 90ribu, deposit 0, pembayaran 0 → Hutang 90ribu
            // Contoh: Total 90ribu, deposit 20ribu, pembayaran 0 → Hutang 70ribu
            // Contoh: Total 90ribu, deposit 20ribu, pembayaran 70ribu → Hutang 0
            if ($sisa_hutang > 0) {
                Hutang::create([
                    'kontak_id' => $po->kontak_id,
                    'tanggal' => $request->tanggal_beli,
                    'jumlah' => $sisa_hutang,
                    'keterangan' => 'pembelian ke ' . $po->kontak->nama,
                    'jenis' => 'belanja',
                    'detail_id' => $belanja->id,
                ]);
            }

            if (count($request->poDetail) > 0) {
                foreach ($request->poDetail as $idx => $poDetailId) {
                    // Ambil PoDetail untuk cek sisa yang belum datang
                    $poDetail = PoDetail::find($poDetailId);
                    $sisaBarang = $poDetail->jumlah - $poDetail->jumlahKedatangan;

                    // Skip jika barang sudah lunas atau tidak ada data valid
                    if (
                        $sisaBarang <= 0 ||
                        !isset($request->produk[$idx]) ||
                        !isset($request->harga[$idx]) ||
                        !isset($request->jumlah[$idx]) ||
                        $request->harga[$idx] <= 0 ||
                        $request->jumlah[$idx] <= 0
                    ) {
                        continue;
                    }

                    // Simpan detail belanja
                    BelanjaDetail::create([
                        'belanja_id' => $belanja->id,
                        'produk_id' => $request->produk[$idx],
                        'harga' => $request->harga[$idx],
                        'jumlah' => $request->jumlah[$idx],
                        'keterangan' => $request->keterangan[$idx] ?? null,
                    ]);

                    // Update harga produk
                    $produk = Produk::find($request->produk[$idx]);
                    $produk->update(['harga' => $request->harga[$idx]]);

                    // Update stok jika produk stok
                    if ($produk->produkModel->stok == 1) {
                        $cabangId = resolve_cabang_id($belanja->cabang_id ?? null);
                        $stokSaatIni = app(StokService::class)->saldoTersedia($produk->id, $cabangId);
                        if ($stokSaatIni > 0) {
                            $hpp = (($stokSaatIni * $produk->hpp) + ($request->harga[$idx] * $request->jumlah[$idx])) / ($request->jumlah[$idx] + $stokSaatIni);
                        } else {
                            $hpp = $request->harga[$idx];
                        }
                        $produk->update(['hpp' => $hpp]);

                        app(StokService::class)->tambah(
                            $request->produk[$idx],
                            $cabangId,
                            $request->jumlah[$idx],
                            'blj',
                            'belanja nota:' . $belanja->nota,
                            $belanja->id,
                            ['user_id' => auth()->user()->id]
                        );
                    }

                    // Update jumlah kedatangan di po detail
                    $total = $poDetail->jumlahKedatangan + $request->jumlah[$idx];
                    $poDetail->update(['jumlahKedatangan' => $total]);
                }
            }

            if ($belanja) {
                //belanja po sync
                $belanja->po()->sync($po->id);
            }
        });

        $message = 'Belanja created successfully.';
        if ($deposit_used > 0) {
            $message .= ' Deposit yang digunakan: Rp ' . number_format($deposit_used, 0, ',', '.');
        }

        return redirect()->route('po.show', $po)->withSuccess($message);
    }
}
