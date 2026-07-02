<?php

namespace App\Http\Controllers\Admin;

use Gate;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Produk;
use App\Models\Belanja;
use App\Models\Produksi;
use App\Models\BukuBesar;
use App\Models\AkunDetail;
use App\Models\Pembayaran;
use App\Services\StokService;
use App\Services\ShopeeStockSyncService;
use App\Models\Marketplace;
use App\Models\ProdukModel;
use App\Models\ProdukKategori;
use Illuminate\Http\Request;
use App\Models\BelanjaDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ShopeeApi;
use Symfony\Component\HttpFoundation\Response;

class MarketplaceController extends Controller
{
    use ShopeeApi;

    public function index()
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $marketplaces = Marketplace::with('kontak', 'kas')->get();

        return view('admin.marketplaces.index', compact('marketplaces'));
    }

    public function show(Marketplace $marketplace)
    {
        $kasMarketplace = AkunDetail::with('akun_kategori')
            ->whereHas('akun_kategori', function ($q) {
                $q->where('nama', 'marketplace');
            })
            ->get();
        $kasPenarikan = AkunDetail::with('akun_kategori')
            ->whereHas('akun_kategori', function ($q) {
                $q->where('nama', '!=', 'marketplace');
            })
            ->get();
        return view('admin.marketplaces.show', compact('marketplace', 'kasMarketplace', 'kasPenarikan'));
    }

    public function create()
    {
        abort_if(Gate::denies('marketplace_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $kasMarketplace = AkunDetail::with('akun_kategori')
            ->whereHas('akun_kategori', function ($q) {
                $q->where('nama', 'marketplace');
            })
            ->get();
        $kasPenarikan = AkunDetail::with('akun_kategori')
            ->whereHas('akun_kategori', function ($q) {
                $q->where('nama', '!=', 'marketplace');
            })
            ->get();
        return view('admin.marketplaces.create', compact('kasMarketplace', 'kasPenarikan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'marketplace' => 'required',
            'kas_id' => 'required',
            'penarikan_id' => 'required',
            'kontak_id' => 'required',
            'warna' => 'nullable|max:100',
        ]);

        $data = $request->all();
        $data['warna'] = $this->normalizeWarna($request->warna);
        $data['auto_sync_stok'] = $request->boolean('auto_sync_stok', true);
        Marketplace::create($data);

        return redirect()->route('marketplaces.index')->withSuccess(__('Toko created berhasil'));
    }

    public function edit(Marketplace $marketplace)
    {
        abort_if(Gate::denies('marketplace_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $marketplace->load('produk.produkModel.kategori');
        $kasMarketplace = AkunDetail::with('akun_kategori')
            ->whereHas('akun_kategori', function ($q) {
                $q->where('nama', 'marketplace');
            })
            ->get();
        $kasPenarikan = AkunDetail::with('akun_kategori')
            ->whereHas('akun_kategori', function ($q) {
                $q->where('nama', '!=', 'marketplace');
            })
            ->get();
        return view('admin.marketplaces.edit', compact('marketplace', 'kasMarketplace', 'kasPenarikan'));
    }

    public function update(Request $request, Marketplace $marketplace)
    {
        $request->validate([
            'warna' => 'nullable|max:100',
        ]);

        $data = $request->all();
        $data['warna'] = $this->normalizeWarna($request->warna);
        $data['auto_sync_stok'] = $request->boolean('auto_sync_stok');
        $marketplace->update($data);

        return redirect()->route('marketplaces.index')->withSuccess(__('Toko updated berhasil'));
    }

    private function normalizeWarna(?string $warna): ?string
    {
        if (empty($warna)) {
            return null;
        }

        return '#' . ltrim($warna, '#');
    }

    public function destroy(Marketplace $marketplace)
    {
        abort_if(Gate::denies('marketplace_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $marketplace->delete();
        return back();
    }

    public function uploadKeuangan(Request $request, Marketplace $id)
    {
        $request->validate([
            'keuangan' => 'required|file|extensions:csv|mimes:csv,txt',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $file_excel = fopen(request()->keuangan, "r");
                $i = 0;
                $config = $id;
                $marketplace = DB::table('marketplace_formats')->where('jenis', 'keuangan')->where('marketplace', $config->marketplace)->first();

                $header = $marketplace->barisHeader ?? 1;

                // Get existing orders for this marketplace contact
                $existingOrders = DB::table('orders')
                    ->where('kontak_id', $config->kontak_id)
                    ->get();
                $orders = $existingOrders->keyBy('nota');

                $keuangan = $order = $iklan = [];
                $input = false;
                if ($config->baruKeuangan == 1)
                    $input = true;
                else
                    //////ambil yg terakhir terinput
                    $terakhir = BukuBesar::where('akun_detail_id', $config->kas_id)->latest()->first();

                while (($baris = fgetcsv($file_excel, 1000, ",")) !== false) {

                    $i++;
                    array_unshift($baris, $i);

                    if ($i < $header)
                        continue;
                    else if ($i == $header) {
                        if ($baris[1] != $marketplace->kolom1 or $baris[2] != $marketplace->kolom2 or $baris[3] != $marketplace->kolom3)
                            throw new \Exception('file excel tidak sesuai dengan template');
                        continue;
                    }

                    $pattern = '/\.0$/';
                    $pattern2 = '/\.00$/';
                    $saldo = $baris[$marketplace->saldo];
                    $saldo = preg_replace($pattern, '', $saldo);
                    $saldo = preg_replace($pattern2, '', $saldo);
                    $saldo = str_replace(",", "", $saldo);
                    $saldo = $baris[$marketplace->saldo] = str_replace(".", "", $saldo);

                    $tanggal = $baris[$marketplace->tanggal];
                    $tanggal = $baris[$marketplace->tanggal] = Carbon::createFromFormat($marketplace->formatTanggal, $tanggal)->toDateTimeString();

                    $tema = $baris[$marketplace->tema];
                    $harga = $baris[$marketplace->harga];
                    $harga = preg_replace($pattern, '', $harga);
                    $harga = preg_replace($pattern2, '', $harga);
                    $harga = str_replace(",", "", $harga);
                    $harga = $baris[$marketplace->harga] = str_replace(".", "", $harga);

                    if ($i == $header + 1) {
                        /////////ambil tanggal dan saldo terakhir di excel yg diupload
                        $tanggal_terakhir = $tanggal;
                        $saldo_terakhir = $saldo;
                        $ket_terakhir = $tema;
                        $dana_terakhir = $harga;
                    }

                    ////////jika ketemu dengan tanggal terakhir yg terupload sebelumnya, start mulai input

                    if (!$input and $tanggal == $terakhir->created_at and $saldo == $terakhir->saldo) {
                        $input = true;
                        break;
                    }

                    if ($harga < 0 and strpos($baris[$marketplace->tema], $marketplace->batal) !== false) {
                        $keuangan[] = $baris;
                    }

                    if (strpos($baris[$marketplace->tema], 'Isi Ulang Saldo Iklan/Koin Penjual') !== false) {
                        $iklan[] = $baris;
                    }

                    if (strlen($baris[4]) > 8) {
                        if (isset($orders[$baris[4]])) {
                            $order[] = $baris;
                        }
                    }
                }

                if ($input) {

                    foreach (array_reverse($iklan) as $baris) {
                        $belanja = Belanja::create([
                            'nota' => $request->nota ? $request->nota : rand(1000000, 100),
                            'total' => abs($baris[6]),
                            'kontak_id' => $config->kontak_id,
                            'akun_detail_id' => $config->kas_id,
                            'pembayaran' => abs($baris[6]),
                            'created_at' => $baris[1],
                        ]);

                        BelanjaDetail::create([
                            'belanja_id' => $belanja->id,
                            'produk_id' => $config->iklan,
                            'harga' => abs($baris[6]),
                            'jumlah' => 1,
                            'keterangan' => $baris[3],
                        ]);
                    }

                    //proses update order sudah dibayar
                    foreach ($order as $baris) {
                        Order::where('nota', $baris[4])->update([
                            'bayar' => $baris[6]
                        ]);
                        Pembayaran::create([
                            'order_id' => Order::where('nota', $baris[4])->first()->id,
                            'jumlah' => $baris[6],
                            'created_at' => $baris[$marketplace->tanggal],
                            'status' => 'lunas',
                            'akun_detail_id' => $config->penarikan_id,
                            'ket' => 'upload keuangan',
                        ]);
                    }
                    //////////////////////proses masukin dana yg ditarik
                    foreach (array_reverse($keuangan) as $baris) {

                        $harga = $baris[$marketplace->harga];
                        $kredit = abs($harga);

                        $tanggal = $baris[$marketplace->tanggal];

                        $sudahAda = BukuBesar::where('akun_detail_id', $config->penarikan_id)
                            ->where('created_at', $tanggal)
                            ->where('debet', $kredit)
                            ->where('kode', 'trf')
                            ->exists();

                        if (!$sudahAda) {
                            BukuBesar::create([
                                'akun_detail_id' => $config->penarikan_id,
                                'kode' => 'trf',
                                'created_at' => $tanggal,
                                'detail_id' => 123,
                                'ket' => 'penarikan dari ' . $config->nama,
                                'debet' => $kredit
                            ]);
                        }
                    }


                    $kredit = $debet = 0;
                    if ($dana_terakhir < 0)
                        $kredit = abs($dana_terakhir);
                    else
                        $debet = $dana_terakhir;

                    DB::table('buku_besars')->where('akun_detail_id', $config->kas_id)->delete();

                    DB::table('buku_besars')->insert([
                        'akun_detail_id' => $config->kas_id,
                        'kode' => 'byr',
                        'created_at' => $tanggal_terakhir,
                        'detail_id' => 123,
                        'ket' => $ket_terakhir,
                        'debet' => $debet,
                        'kredit' => $kredit,
                        'saldo' => $saldo_terakhir
                    ]);

                    DB::table('akun_details')->where('id', $config->kas_id)->update(['saldo' => $saldo_terakhir]);

                    if ($config->baruKeuangan == 1) {
                        $config->update(['baruKeuangan' => 0]);
                    }


                    $config->update(['tglUploadKeuangan' => now()]);
                } else
                    throw new \Exception('tanggal pengambilan rentangnya kurang panjang');
            });
            return redirect()->route('marketplaces.show', $id->id)->withSuccess(__('Upload keuangan berhasil'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Upload keuangan gagal: ' . $e->getMessage()]);
        }
    }

    public function uploadKeuanganTiktok(Request $request, Marketplace $id)
    {
        $request->validate([
            'keuangan' => 'required|file|extensions:csv|mimes:csv,txt',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $file_excel = fopen(request()->keuangan, "r");
                $i = 0;
                $config = $id;
                $marketplace = DB::table('marketplace_formats')->where('jenis', 'keuangan')->where('marketplace', $config->marketplace)->first();

                $header = $marketplace->barisHeader ?? 1;

                // Get existing orders for this marketplace contact
                $existingOrders = DB::table('orders')
                    ->where('kontak_id', $config->kontak_id)
                    ->get();
                $orders = $existingOrders->keyBy('nota');

                $keuangan = $order = $iklan = [];
                $input = false;
                if ($config->baruKeuangan == 1)
                    $input = true;
                else {
                    //////ambil yg terakhir terinput
                    $terakhir = BukuBesar::where('akun_detail_id', $config->kas_id)->latest()->first();
                    if (!$terakhir) {
                        $input = true;
                    }
                }

                // Gunakan length 0 agar baris CSV panjang (kolom banyak) tidak terpotong.
                while (($baris = fgetcsv($file_excel, 0, ",")) !== false) {

                    $i++;
                    array_unshift($baris, $i);

                    if ($i < $header)
                        continue;
                    else if ($i == $header) {
                        if (!$this->isCsvHeaderMatch($baris, $marketplace))
                            throw new \Exception('file excel tidak sesuai dengan template');
                        continue;
                    }

                    $pattern = '/\.0$/';
                    $pattern2 = '/\.00$/';
                    $saldo = $baris[$marketplace->saldo];
                    $saldo = preg_replace($pattern, '', $saldo);
                    $saldo = preg_replace($pattern2, '', $saldo);
                    $saldo = str_replace(",", "", $saldo);
                    $saldo = $baris[$marketplace->saldo] = str_replace(".", "", $saldo);

                    $tanggal = $baris[$marketplace->tanggal] ?? '';
                    $tanggal = $baris[$marketplace->tanggal] = $this->normalizeMarketplaceDate((string) $tanggal, $marketplace->formatTanggal ?? null);

                    $tema = $baris[$marketplace->tema] ?? '';
                    $harga = $baris[$marketplace->harga];
                    $harga = preg_replace($pattern, '', $harga);
                    $harga = preg_replace($pattern2, '', $harga);
                    $harga = str_replace(",", "", $harga);
                    $harga = $baris[$marketplace->harga] = str_replace(".", "", $harga);

                    if ($i == $header + 1) {
                        /////////ambil tanggal dan saldo terakhir di excel yg diupload
                        $tanggal_terakhir = $tanggal;
                        $saldo_terakhir = $saldo;
                        $ket_terakhir = $tema;
                        $dana_terakhir = $harga;
                    }

                    ////////jika ketemu dengan tanggal terakhir yg terupload sebelumnya, start mulai input
                    if (
                        !$input
                        and $terakhir
                        // Patokan data terakhir harus sama persis tanggal dan saldonya.
                        and $this->isSameDateValue((string) $tanggal, (string) $terakhir->created_at)
                        and ((float) $saldo === (float) $terakhir->saldo)
                    ) {
                        $input = true;
                        break;
                    }

                    if (strpos($baris[$marketplace->tema], $marketplace->batal) !== false) {
                        $keuangan[] = $baris;
                    }

                    if (strpos($baris[$marketplace->tema], 'Isi Ulang Saldo Iklan/Koin Penjual') !== false) {
                        $iklan[] = $baris;
                    }

                    if (strpos($baris[2], 'Order') !== false) {
                        if (isset($orders[$baris[1]])) {
                            $order[] = $baris;
                        }
                    }
                }

                if ($input) {

                    foreach (array_reverse($iklan) as $baris) {
                        $belanja = Belanja::create([
                            'nota' => $request->nota ? $request->nota : rand(1000000, 100),
                            'total' => abs($baris[6]),
                            'kontak_id' => $config->kontak_id,
                            'akun_detail_id' => $config->kas_id,
                            'pembayaran' => abs($baris[6]),
                            'created_at' => $baris[1],
                        ]);

                        BelanjaDetail::create([
                            'belanja_id' => $belanja->id,
                            'produk_id' => $config->iklan,
                            'harga' => abs($baris[6]),
                            'jumlah' => 1,
                            'keterangan' => $baris[3],
                        ]);
                    }

                    //proses update order sudah dibayar
                    foreach ($order as $baris) {
                        Order::where('nota', $baris[1])->update([
                            'bayar' => $baris[6]
                        ]);
                    }
                    //////////////////////proses masukin dana yg ditarik
                    foreach (array_reverse($keuangan) as $baris) {

                        $harga = $baris[$marketplace->harga];
                        $kredit = abs($harga);

                        $tanggal = $baris[$marketplace->tanggal];

                        $sudahAda = BukuBesar::where('akun_detail_id', $config->penarikan_id)
                            ->where('created_at', $tanggal)
                            ->where('debet', $kredit)
                            ->where('kode', 'trf')
                            ->exists();

                        if (!$sudahAda) {
                            BukuBesar::create([
                                'akun_detail_id' => $config->penarikan_id,
                                'kode' => 'trf',
                                'created_at' => $tanggal,
                                'detail_id' => 123,
                                'ket' => 'penarikan dari ' . $config->nama,
                                'debet' => $kredit
                            ]);
                        }
                    }


                    $kredit = $debet = 0;
                    if ($dana_terakhir < 0)
                        $kredit = abs($dana_terakhir);
                    else
                        $debet = $dana_terakhir;

                    DB::table('buku_besars')->where('akun_detail_id', $config->kas_id)->delete();

                    DB::table('buku_besars')->insert([
                        'akun_detail_id' => $config->kas_id,
                        'kode' => 'byr',
                        'created_at' => $tanggal_terakhir,
                        'detail_id' => 123,
                        'ket' => $ket_terakhir,
                        'debet' => $debet,
                        'kredit' => $kredit,
                        'saldo' => $saldo_terakhir
                    ]);

                    DB::table('akun_details')->where('id', $config->kas_id)->update(['saldo' => $saldo_terakhir]);

                    if ($config->baruKeuangan == 1) {
                        $config->update(['baruKeuangan' => 0]);
                    }


                    $config->update(['tglUploadKeuangan' => now()]);
                } else
                    throw new \Exception('tanggal pengambilan rentangnya kurang panjang');
            });
            return redirect()->route('marketplaces.show', $id->id)->withSuccess(__('Upload keuangan berhasil'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Upload keuangan gagal: ' . $e->getMessage()]);
        }
    }

    public function uploadKeuanganTiktokBaru(Request $request, Marketplace $id)
    {
        $request->validate([
            'keuangan' => 'required|file|extensions:csv|mimes:csv,txt',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $file = fopen(request()->keuangan, 'r');
                if ($file === false) {
                    throw new \Exception('file tidak bisa dibaca');
                }

                $config = $id;

                $input = false;
                $terakhir = null;
                if ((int) $config->baruKeuangan === 1) {
                    $input = true;
                } else {
                    $terakhir = BukuBesar::where('akun_detail_id', $config->kas_id)->latest()->first();
                    if (!$terakhir) {
                        $input = true;
                    }
                }

                $i = 0;
                $mutasi = [];

                // Hardcode: header selalu baris pertama
                $header = fgetcsv($file, 0, ',');
                $i++;
                if ($header === false) {
                    throw new \Exception('file kosong');
                }
                array_unshift($header, $i);
                $colMap = $this->buildKeuanganBaruColumnMap($header);
                if (!$colMap || !isset($colMap['type'])) {
                    throw new \Exception('file excel tidak sesuai dengan template terbaru (kolom Type wajib ada)');
                }

                while (($baris = fgetcsv($file, 0, ',')) !== false) {
                    $i++;
                    array_unshift($baris, $i);

                    $requestTime = (string) ($baris[$colMap['request_time']] ?? '');
                    $successTime = (string) ($baris[$colMap['success_time']] ?? '');
                    $tanggal = $successTime !== '' ? $successTime : $requestTime;
                    $tanggal = $this->normalizeMarketplaceDate($tanggal, null);

                    $amountRaw = (string) ($baris[$colMap['amount']] ?? '0');
                    $amount = $this->parseCsvNumber($amountRaw);

                    $type = isset($colMap['type']) ? (string) ($baris[$colMap['type']] ?? '') : '';
                    $referenceId = isset($colMap['reference_id']) ? (string) ($baris[$colMap['reference_id']] ?? '') : '';
                    $status = (string) ($baris[$colMap['status']] ?? '');
                    $bank = (string) ($baris[$colMap['bank_account']] ?? '');

                    // Hardcode: hanya ambil Withdrawal (format terbaru)
                    if (mb_strtolower(trim($type)) !== 'withdrawal') {
                        continue;
                    }

                    $ketParts = ['Keuangan TikTok'];
                    if (trim($type) !== '') {
                        $ketParts[] = trim($type);
                    }
                    if (trim($status) !== '') {
                        $ketParts[] = trim($status);
                    }
                    if (trim($referenceId) !== '') {
                        $ketParts[] = 'ref ' . trim($referenceId);
                    }
                    if (trim($bank) !== '') {
                        $ketParts[] = trim($bank);
                    }
                    $ket = implode(' - ', $ketParts);

                    // Withdrawal harusnya minus, tapi tetap guard
                    if ((float) $amount >= 0) {
                        continue;
                    }

                    if (
                        !$input
                        && $terakhir
                        && $this->isSameDateValue($tanggal, (string) $terakhir->created_at)
                        // "saldo terakhir" dipakai sebagai patokan nilai transaksi terakhir di kas (kredit)
                        && ((float) abs($amount) === (float) ($terakhir->kredit ?? 0))
                    ) {
                        $input = true;
                        break;
                    }

                    $mutasi[] = [
                        'tanggal' => $tanggal,
                        'amount' => $amount,
                        'ket' => $ket,
                        'status' => $status,
                        'bank' => $bank,
                    ];
                }

                if (!$input) {
                    throw new \Exception('tanggal pengambilan rentangnya kurang panjang');
                }

                $akunKas = DB::table('akun_details')->where('id', $config->kas_id)->first();
                $saldo = (float) ($akunKas->saldo ?? 0);

                $latestKasRow = null;
                foreach (array_reverse($mutasi) as $row) {
                    $amount = (float) $row['amount'];
                    $debet = $amount >= 0 ? $amount : 0;
                    $kredit = $amount < 0 ? abs($amount) : 0;

                    $saldo = $saldo + $debet - $kredit;

                    // kas_id TikTok: hanya boleh 1 data di BukuBesar (yang paling baru)
                    if ($kredit > 0) {
                        if (
                            !$latestKasRow
                            || Carbon::parse($row['tanggal'])->greaterThan(Carbon::parse($latestKasRow['tanggal']))
                        ) {
                            $latestKasRow = [
                                'tanggal' => $row['tanggal'],
                                'ket' => $row['ket'],
                                'kredit' => $kredit,
                            ];
                        }
                    }

                    // jika nominal minus, anggap penarikan (transfer keluar)
                    if ($amount < 0 && !empty($config->penarikan_id)) {
                        $kreditPenarikan = abs($amount);
                        $sudahAdaTarik = BukuBesar::where('akun_detail_id', $config->penarikan_id)
                            ->where('created_at', $row['tanggal'])
                            ->where('debet', $kreditPenarikan)
                            ->where('kode', 'trf')
                            ->exists();

                        if (!$sudahAdaTarik) {
                            BukuBesar::create([
                                'akun_detail_id' => $config->penarikan_id,
                                'kode' => 'trf',
                                'created_at' => $row['tanggal'],
                                'detail_id' => 123,
                                'ket' => 'penarikan dari ' . $config->nama . (!empty($row['bank']) ? ' - ' . $row['bank'] : ''),
                                'debet' => $kreditPenarikan,
                            ]);
                        }
                    }
                }

                // enforce kas_id hanya 1 record: hapus semua lalu insert yang paling baru
                if ($latestKasRow) {
                    DB::table('buku_besars')->where('akun_detail_id', $config->kas_id)->delete();
                    DB::table('buku_besars')->insert([
                        'akun_detail_id' => $config->kas_id,
                        'kode' => 'byr',
                        'created_at' => $latestKasRow['tanggal'],
                        'detail_id' => 123,
                        'ket' => $latestKasRow['ket'],
                        'debet' => 0,
                        'kredit' => $latestKasRow['kredit'],
                        'saldo' => 0,
                    ]);

                    AkunDetail::where('id', $config->kas_id)->update(['saldo' => 0]);
                }

                if ((int) $config->baruKeuangan === 1) {
                    $config->update(['baruKeuangan' => 0]);
                }

                $config->update(['tglUploadKeuangan' => now()]);
            });

            return redirect()->route('marketplaces.show', $id->id)->withSuccess(__('Upload keuangan berhasil'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Upload keuangan gagal: ' . $e->getMessage()]);
        }
    }

    public function uploadOrder(Request $request, Marketplace $id)
    {
        $request->validate([
            'order' => 'required|file|extensions:csv|mimes:csv,txt',
        ]);
        try {
            DB::transaction(function () use ($request, $id) {

                $file_excel = fopen(request()->order, "r");

                $no_baris = 0;
                $input = false;

                $config = $id;
                $marketplace = DB::table('marketplace_formats')->where('jenis', 'order')->where('marketplace', $config->marketplace)->first();

                $toko = $config->cabang_id;
                $id_shopee = $config->kontak_id;

                ////ambil data semua produk di company
                $ambil = DB::table('produks')->select('produks.id', 'hpp', 'stok', 'harga')->where('produks.status', 1)
                    ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
                    ->get();

                ////bikin array data produk dengan key id dan id_produk(id project yg lama)
                $produks = $ambil->keyBy('id');

                //////posisi header di baris brapa
                $header = $marketplace->barisHeader ?? 1;

                $order = $orderdetil = $stok = $inputStok = $inputBatal =  $batal = [];
                $orderSaldoByNota = $orderLineTotalByNota = [];
                $input = $notaTerakhir = false;
                $awal = true;
                $nota_skr = 0;
                $deathline = null;
                //////jika marketplace baru, langsung input, ga usah dicek dulu
                if ($config->baruOrder == 1) {
                    $input = true;
                    $notaTerakhir = true;
                }

                /////ambil ida project_flow
                $batal_id = Produksi::ambilFlow('batal');
                $finish_id = Produksi::ambilFlow('finish');
                $awal_id = Produksi::ambilFlow('Persiapan');

                //////ambil nota terakhir yg udah terinput
                $terakhir = Order::where('kontak_id', $id_shopee)->latest('id')->whereNotNull('nota')->first();

                while (($baris = fgetcsv($file_excel, 1000, ",")) !== false) {

                    $no_baris++;
                    /////tambahin 1 kolom didepan
                    array_unshift($baris, $no_baris);

                    //////cari posisi header
                    if ($no_baris < $header)
                        continue;
                    else if ($no_baris == $header) {
                        if ($baris[1] != $marketplace->kolom1 or $baris[2] != $marketplace->kolom2 or $baris[3] != $marketplace->kolom3)
                            throw new \Exception('excel salah');

                        continue;
                    }
                    if ($no_baris == $header)
                        continue;

                    $nota = $baris[$marketplace->nota];
                    $status = $baris[$marketplace->status];
                    $barang = $baris[$marketplace->sku_anak];
                    if (empty($barang))
                        $barang = $baris[$marketplace->sku];

                    //////pengecekan order yg udah terinput sebelumnya
                    if (!$input) {


                        ////////jika statusnya batal, masukin ke array batal
                        if ($status == $marketplace->batal and strpos($barang, 'CUSTOM_') !== false)
                            $batal[$nota] = 1;

                        /////jika ketemu dgn nota terakhir, set nota terakhir true
                        if ($terakhir && $nota == $terakhir->nota) {
                            $notaTerakhir = true;
                            continue;
                        }
                        /////////jika nota terakhir udah selesai, dan ketemu nota baru, baru bisa mulai input
                        else if ($notaTerakhir && $terakhir && $nota != $terakhir->nota)
                            $input = true;
                    }


                    if ($input) {

                        $tanggal = $baris[$marketplace->tanggal];
                        $tanggal = Carbon::createFromFormat($marketplace->formatTanggal, $tanggal)->toDateTimeString();
                        $nama = $baris[$marketplace->nama];
                        $tema = $baris[$marketplace->tema];
                        $totalSaldo = str_replace("Rp ", "", $baris[$marketplace->saldo] ?? '');
                        $totalSaldo = str_replace(".", "", $totalSaldo);

                        $jumlah = $baris[$marketplace->jumlah];
                        $harga = str_replace("Rp ", "", $baris[$marketplace->harga]);
                        $harga = str_replace(".", "", $harga);

                        if ($status == $marketplace->batal) {
                            $produksi_id = $batal_id;
                        } else {
                            $produksi_id = $finish_id;
                            if ($totalSaldo !== '' && is_numeric($totalSaldo) && (float) $totalSaldo > 0) {
                                $orderSaldoByNota[$nota] = (float) $totalSaldo;
                            }
                        }

                        $deathline = $this->parseDeathline(
                            $this->getCsvColumnValue($baris, $marketplace->deathline),
                            $tanggal,
                            $marketplace->formatTanggal
                        );

                        //jika ganti nota
                        if ($nota != $nota_skr) {

                            if ($awal) {  //////simpen nota yg diinput pertama kali
                                $nota_awal = $nota;
                                $awal = false;
                            }

                            $ongkir = str_replace(".", "", $baris[$marketplace->ongkir]);

                            $order[] = array(
                                'kontak_id' => $id_shopee,
                                'total' => 0,
                                'nota' => $nota,
                                'created_at' => $tanggal,
                                'konsumen_detail' => $nama,
                                'deathline' => $deathline,
                                'marketplace' => 1,
                                'ongkir' => $ongkir
                            );
                        }
                        ////jika sku NON_PRODUK, skip penginputan
                        if ($barang == "NON_PRODUK")
                            continue;

                        $custom = '';
                        $orderCustom = false;

                        //////jika sku depannya ada CUSTOM_ , hapus tulisan itu, sisain sku nya
                        if (strpos($barang, 'CUSTOM_') !== false) {
                            $barang = str_replace('CUSTOM_', "", $barang);
                            $orderCustom = true;
                            $custom = $tema;

                            if ($status == $marketplace->batal) {
                                $produksi_id = $batal_id;
                            } else {
                                $produksi_id = $awal_id;
                            }
                        }

                        $paket = 1;
                        if (strpos($barang, '_') !== false) {
                            $skuParts = explode('_', $barang);
                            $barang = $skuParts[0]; // Mengambil bagian pertama dari SKU
                            if (isset($skuParts[1]) && is_numeric($skuParts[1])) {
                                $paket = (int) $skuParts[1]; // bagian kedua dari SKU = jumlah paket
                                $jumlah = (int) $jumlah * $paket;
                            }
                        }

                        /////////////////cek, apakah sku udah sesuai dgn produk_id
                        $produk = $produks[$barang] ?? false;
                        if (!$produk)
                            throw new \Exception('sku: ' . $barang . ', nama: ' . $baris[$marketplace->produk] . ', tidak ada di sistem');

                        $hpp = Produk::find($produk->id);

                        /////mulai input orderdetil ke array
                        $orderdetil[] = array(
                            'produk_id' => $produk->id,
                            'jumlah' => $baris[$marketplace->jumlah],
                            'tema' => $custom,
                            'harga' => $harga,
                            'hpp' => $hpp->hpp,
                            'produksi_id' => $produksi_id,
                            'nota' => $nota,
                            'created_at' => $tanggal,
                            'deathline' => $deathline
                        );

                        // kolom saldo di CSV Shopee sering kosong pada baris non-custom, hitung dari harga x jumlah
                        if ($status != $marketplace->batal && !$orderCustom) {
                            $orderLineTotalByNota[$nota] = ($orderLineTotalByNota[$nota] ?? 0) + ((float) $harga * (int) $jumlah);
                        }

                        ///////////////////kalo ordernya ga batal, dan produknya ada stoknya, input brapa yg terjual
                        if ($status != $marketplace->batal and $produk->stok == 1 and !$orderCustom)
                            $stok[] = array(
                                'produk_id' => $produk->id,
                                'jumlah' => $jumlah,
                                'keterangan' => 'dibeli oleh ' . $nama
                            );
                    }
                    $nota_skr = $nota;
                }

                if (!$notaTerakhir)
                    throw new \Exception('rentang tgl kurang panjang');

                ////////order yg udah terinput, tp cek apakah ada yg berubah dl batal
                if ($batal) {

                    $batal = array_keys($batal);

                    ////////////////cari di db, yg di excel nya batal, tp di table order_details msh blm batal
                    $batalx = DB::table('order_details')->whereIn('nota', $batal)->where('produksi_id', $finish_id)->get();

                    $diubahBatal = $produkBatal = $projectBatal = [];
                    //////kalo ada order_details yg blm dirubah ke batal, maka proses utk rubah
                    foreach ($batalx as $yy) {
                        ///project_detail yg blm dirubah ke batal
                        $diubahBatal[] = $yy->id;
                        ////project yg blm dirubah ke batal
                        $projectBatal[$yy->order_id] = 1;
                        //////jumlah produk yg batal dibeli
                        $produk = $produks[$yy->produk_id];
                        if ($produk->stok == 1)
                            $produkBatal[$yy->produk_id] = $yy->jumlah + ($produkBatal[$yy->produk_id] ?? 0);
                    }

                    ////proses perubahan ke db
                    if ($diubahBatal) {
                        DB::table('order_details')->whereIn('id', $diubahBatal)->update(['produksi_id' => $batal_id]);

                        DB::table('orders')->whereIn('id', array_keys($projectBatal))->update(['total' => 0]);
                    }

                    /////jika ada produk yg dikembalikan
                    if ($produkBatal) {
                        foreach ($produkBatal as $produk_id => $stokx) {

                            $produk = $produks[$produk_id];

                            app(StokService::class)->tambah(
                                $produk_id,
                                $stokx,
                                'batal',
                                'upload ' . $config->nama
                            );
                        }
                    }
                }

                //////////////jika ada order baru/////////////////////////////////////////////////////////////
                if ($input) {
                    foreach ($order as $i => $orderRow) {
                        $notaKey = $orderRow['nota'];
                        if (isset($orderSaldoByNota[$notaKey]) && $orderSaldoByNota[$notaKey] > 0) {
                            $order[$i]['total'] = $orderSaldoByNota[$notaKey];
                        } elseif (isset($orderLineTotalByNota[$notaKey])) {
                            $order[$i]['total'] = $orderLineTotalByNota[$notaKey];
                        }
                    }

                    DB::table('orders')->insert($order);
                    DB::table('order_details')->insert($orderdetil);

                    ////ambil orderdetil yg pertama akan diinput
                    $orderdetil_awal = DB::table('order_details')->where('nota', $nota_awal)->orderBy('id', 'desc')->first()->id;


                    //////update order_id ke table order_details (pas pertama input msh kosong)
                    DB::statement("UPDATE order_details
                        SET order_id = (
                            SELECT id
                            FROM orders
                            WHERE orders.nota=order_details.nota
                                and kontak_id=" . $id_shopee . "
                                limit 1
                        ) where id>=" . $orderdetil_awal . " and order_details.nota is not Null");


                    //////ngurangi stok yg terjual/////////////////////////////////////////////////////////
                    if ($stok) {
                        foreach ($stok as $value) {
                            app(StokService::class)->kurang(
                                $value['produk_id'],
                                $value['jumlah'],
                                'jual',
                                $value['keterangan'],
                                null,
                                [],
                                false
                            );
                        }
                    }
                }


                if ($config->baruOrder == 1)
                    $config->update(['baruOrder' => 0]);

                $config->update(['tglUploadOrder' => now()]);
            });
            return redirect()->route('marketplaces.show', $id->id)->withSuccess(__('Upload Order berhasil'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Upload Order gagal: ' . $e->getMessage()]);
        }
    }

    public function uploadStok(Request $request, Marketplace $id)
    {
        $request->validate([
            'stok' => 'required|file|extensions:csv|mimes:csv,txt',
        ]);

        // try {
            $file_excel = fopen(request()->stok, "r");

            $i = 0;


            $config = $id;
            $marketplace = DB::table('marketplace_formats')->where('jenis', 'stok')->where('marketplace', $config->marketplace)->first();

            $header = $marketplace->barisHeader ?? 1;


            $table = '<table border="1" cellpadding="0" cellspacing="0" width=100% class=table>';


            /////////////ambil semua produk, masukin ke array
            $ambil = DB::table('produks')->select('produks.id',  'stok', 'harga')->where('produks.status', 1)
                ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
                ->get();
            $produks = $ambil->keyBy('id');


            while (($baris = fgetcsv($file_excel, 1000, ",")) !== false) {

                $i++;
                array_unshift($baris, $i);

                if ($i < $header)
                    continue;
                else if ($i == $header) {
                    if ($baris[1] != $marketplace->kolom1 or $baris[2] != $marketplace->kolom2 or $baris[3] != $marketplace->kolom3)
                        throw new \Exception('excel salah');

                    continue;
                } else if ($i < ($header + $marketplace->status))
                    continue;

                // Tambahkan filter agar hanya baris[1] yang berupa numerik diterima (misal: 28324459105)
                if (!is_numeric($baris[1])) {
                    continue;
                }

                $varian = $baris[$marketplace->tema] ?? '';
                $produk = $baris[$marketplace->produk];
                $sku_induk = $baris[$marketplace->sku];
                $sku_anak = $baris[$marketplace->sku_anak] ?? '';
                $harga = $baris[$marketplace->harga];
                $stok = $baris[$marketplace->saldo] ?? 0;

                $custom = false;


                $sku = !empty($sku_anak) ? $sku_anak : $sku_induk;


                $table .= "<tr ><td>" . $i . '<td>' . $produk . '<td>' . $varian . "<td>" . $sku_induk . '<td>' . $sku_anak . '<td>' . $harga;
                if (empty($sku)) {
                    $table .= "<td colspan=4><h2><font color=red>error!! sku yg di shopee blm diisi";
                    break;
                }

                if ($sku != 'NON_PRODUK') {
                    if (strpos($sku, 'CUSTOM_') !== false) {
                        $custom = true;
                        $sku = str_replace('CUSTOM_', "", $sku);
                    }
                    if (strpos($sku, '_') !== false) {
                        $skuParts = explode('_', $sku);
                        $paket = $skuParts[1];
                        $sku = $skuParts[0]; // Mengambil bagian pertama dari SKU
                    } else {
                        $paket = 1;
                    }

                    // //////// sampe sini hapusnya


                    $produk = $produks[$sku] ?? false;

                    //////// sampe sini hapusnya


                    if ($produk) {

                        if ($produk->stok == 1) {

                            $stok = app(StokService::class)->saldoTersedia($produk->id);

                            if ($stok < 0)
                                $stok = 0;
                            if ($paket) {
                                $stok = floor($stok / $paket);
                            }
                        } else
                            $stok = 10000;



                        if (!$custom)
                            $harga_baru = $produk->harga;
                        else
                            $harga_baru = (float)$harga;


                        if (empty($harga_baru)) {
                            $table .= "<td colspan=4><h2><font color=red>error!! harga di project masih kosong";
                            break;
                        }
                        $harga_baru = floor($harga_baru * (100 + $config->harga) / 100);

                        if ((float)$harga == 0) {
                            $table .= "<td colspan=4><h2><font color=red>error!! harga tidak boleh 0";
                            break;
                        }

                        $perbedaan_persen = abs((float)$harga - $harga_baru) / (float)$harga * 100;

                        if ($perbedaan_persen > 20)
                            $harga = "<h4><font color=red>" . $harga_baru;
                        else if ((float)$harga != $harga_baru)
                            $harga = "<h4><font color=green>" . $harga_baru;
                        else
                            $harga = $harga_baru;
                    } else {
                        $table .= "<td colspan=4><h2><font color=red>error!! sku tidak ada di project";
                        break;
                    }
                }

                $table .= '<td>'

                    //////// kalo semua migrasi udah beres, ini hapus
                    . ($produk->id ?? '') . '<td>'
                    //////// sampe sini hapusnya

                    . $harga . '<td>' . $stok;
            }

            $table .= '</table>';

            $config->update(['tglUploadStok' => now()]);

            echo $table;
        // } catch (\Exception $e) {
        //     return redirect()->back()->withErrors(['error' => 'Upload Stok gagal: ' . $e->getMessage()]);
        // }
    }

    /**
     * Halaman daftar produk marketplace (submenu Produk).
     * Ditampilkan PER PRODUK MODEL (bukan per varian): kategori utama, kategori, produk,
     * hpp, harga jual, margin, stok min, dan jumlah varian (klik = popup daftar varian).
     * Mendukung pemilihan toko, filter kategori, dan pagination.
     */
    public function produk(Request $request)
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Daftar toko Shopee untuk dipilih
        $tokos = Marketplace::where('marketplace', 'shopee')->orderBy('nama')->get();

        // Toko yang sedang dipilih (default toko pertama)
        $marketplaceId = $request->input('marketplace_id', optional($tokos->first())->id);
        $config = $marketplaceId ? Marketplace::find($marketplaceId) : null;

        // Daftar kategori untuk filter (hanya di bawah kategori utama jual & stok)
        $kategoris = ProdukKategori::whereHas('kategoriUtama', function ($q) {
            $q->where('jual', 1)->where('stok', 1);
        })->orderBy('nama')->get();
        $kategoriId = $request->input('kategori_id');

        $items = null;
        $varianPerModel = collect();

        if ($config) {
            $query = DB::table('produk_marketplaces as pm')
                ->join('produks', 'pm.produk_id', '=', 'produks.id')
                ->join('produk_models as m', 'produks.produk_model_id', '=', 'm.id')
                ->join('produk_kategoris as k', 'm.kategori_id', '=', 'k.id')
                ->join('produk_kategori_utamas as ku', 'k.kategori_utama_id', '=', 'ku.id')
                ->where('pm.marketplace_id', $config->id)
                ->where('ku.jual', 1)
                ->where('ku.stok', 1)
                ->when(!empty($kategoriId), fn ($q) => $q->where('m.kategori_id', $kategoriId))
                ->groupBy('m.id', 'm.nama', 'm.harga', 'm.stok_min_mp', 'k.id', 'k.nama', 'ku.id', 'ku.nama')
                ->select(
                    'm.id as produk_model_id',
                    'm.nama as namaProduk',
                    'm.harga as harga',
                    'm.stok_min_mp',
                    'k.id as kategori_id',
                    'k.nama as kategori',
                    'ku.id as kategori_utama_id',
                    'ku.nama as namaUtama',
                    DB::raw('MAX(produks.hpp) as hpp'),
                    DB::raw('COUNT(pm.id) as total_varian')
                )
                ->orderBy('ku.nama')
                ->orderBy('k.nama')
                ->orderBy('m.nama');

            $items = $query->paginate(25)->withQueryString();

            // Hitung margin tiap baris
            foreach ($items as $row) {
                $hpp = (float) $row->hpp;
                $harga = (float) $row->harga;
                $row->margin = $hpp > 0 ? (int) floor(($harga - $hpp) / $hpp * 100) : null;
            }

            // Ambil semua varian (produk_marketplaces) untuk model di halaman ini → popup
            $modelIds = collect($items->items())->pluck('produk_model_id')->all();
            if (!empty($modelIds)) {
                $varianPerModel = DB::table('produk_marketplaces as pm')
                    ->join('produks', 'pm.produk_id', '=', 'produks.id')
                    ->join('produk_models as m', 'produks.produk_model_id', '=', 'm.id')
                    ->where('pm.marketplace_id', $config->id)
                    ->whereIn('produks.produk_model_id', $modelIds)
                    ->select(
                        'pm.id as pm_id',
                        'pm.item_id',
                        'pm.model_id',
                        'pm.paket',
                        'pm.harga as harga_varian',
                        'pm.harga_mp',
                        'pm.nama',
                        'pm.varian',
                        'pm.update_harga_terakhir',
                        'm.id as produk_model_id',
                        'm.harga as harga_lokal'
                    )
                    ->orderBy('pm.nama')
                    ->orderBy('pm.varian')
                    ->get();

                foreach ($varianPerModel as $v) {
                    $paket = max((int) $v->paket, 1);
                    // Harga jual per varian: pakai pm.harga bila sudah diisi, fallback ke harga model
                    $hargaJual = (int) $v->harga_varian > 0 ? (int) $v->harga_varian : (int) $v->harga_lokal;
                    $v->harga_jual = $hargaJual;
                    $v->harga_baru = $hargaJual > 0
                        ? (int) floor($hargaJual * $paket * (100 + $config->harga) / 100)
                        : 0;
                    $v->berubah = $v->harga_baru > 0 && (int) $v->harga_mp !== $v->harga_baru;
                }

                $varianPerModel = $varianPerModel->groupBy('produk_model_id');
            }
        }

        return view('admin.marketplaces.produk', compact('tokos', 'config', 'kategoris', 'kategoriId', 'items', 'varianPerModel'));
    }

    /**
     * Edit harga jual produk (produk_models.harga) dari popup "harga jual".
     */
    public function updateHargaModel(Request $request)
    {
        abort_if(Gate::denies('marketplace_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'produk_model_id' => 'required|integer',
            'harga' => 'required|integer|min:0',
        ]);

        ProdukModel::where('id', $request->produk_model_id)->update(['harga' => (int) $request->harga]);

        return redirect()->back()->withSuccess('Harga jual berhasil diperbarui.');
    }

    /**
     * Edit margin (%) produk dari kolom margin. Harga jual dihitung ulang dari hpp:
     * harga = hpp * (1 + margin/100), lalu disimpan ke produk_models.harga.
     */
    public function updateMargin(Request $request)
    {
        abort_if(Gate::denies('marketplace_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'produk_model_id' => 'required|integer',
            'margin' => 'required|numeric',
        ]);

        $hpp = (float) DB::table('produks')
            ->where('produk_model_id', $request->produk_model_id)
            ->max('hpp');

        if ($hpp <= 0) {
            return redirect()->back()->withErrors(['error' => 'HPP produk masih 0, margin tidak bisa dihitung.']);
        }

        $harga = (int) floor($hpp * (100 + (float) $request->margin) / 100);

        ProdukModel::where('id', $request->produk_model_id)->update(['harga' => $harga]);

        return redirect()->back()->withSuccess('Margin diperbarui, harga jual jadi Rp ' . number_format($harga, 0, ',', '.'));
    }

    /**
     * Edit massal stok minimal marketplace (produk_models.stok_min_mp) untuk produk
     * model terpilih. Nilai kosong = hapus (NULL).
     */
    public function bulkStokMin(Request $request)
    {
        abort_if(Gate::denies('marketplace_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'stok_min_mp' => 'nullable|integer|min:0',
        ]);

        $nilai = $request->filled('stok_min_mp') ? (int) $request->stok_min_mp : null;

        $jumlah = ProdukModel::whereIn('id', $request->ids)->update(['stok_min_mp' => $nilai]);

        return redirect()->back()->withSuccess($jumlah . ' produk diperbarui stok minimalnya.');
    }

    /**
     * Simpan harga jual per varian (produk_marketplaces.harga) dari popup total varian.
     */
    public function updateHargaVarian(Request $request)
    {
        abort_if(Gate::denies('marketplace_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'pm_id' => 'required|integer',
            'harga' => 'required|integer|min:0',
        ]);

        $updated = DB::table('produk_marketplaces')->where('id', $request->pm_id)->update([
            'harga' => (int) $request->harga,
            'updated_at' => now(),
        ]);

        if (!$updated) {
            return redirect()->back()->withErrors(['error' => 'Varian tidak ditemukan.']);
        }

        return redirect()->back()->withSuccess('Harga jual varian berhasil disimpan.');
    }

    /**
     * Update harga SATU produk dari aplikasi ke Shopee via Open API (product/update_price).
     *
     * Diproses per produk (berdasarkan id baris produk_marketplaces) sesuai permintaan,
     * bukan sekaligus semua produk.
     */
    public function updateHarga(Request $request, Marketplace $id)
    {
        abort_if(Gate::denies('marketplace_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'pm_id' => 'required',
            'harga' => 'nullable|integer|min:0',
        ]);

        $config = $id;

        if ($config->marketplace !== 'shopee') {
            return redirect()->back()->withErrors(['error' => 'Update harga otomatis hanya tersedia untuk Shopee']);
        }

        if (empty($config->shop_id) || empty($config->access_token)) {
            return redirect()->back()->withErrors(['error' => 'Toko belum tersinkron dengan Shopee. Silakan sinkronkan terlebih dahulu.']);
        }

        $row = DB::table('produk_marketplaces as pm')
            ->join('produks', 'pm.produk_id', '=', 'produks.id')
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->where('pm.id', $request->pm_id)
            ->where('pm.marketplace_id', $config->id)
            ->select(
                'pm.id as pm_id',
                'pm.item_id',
                'pm.model_id',
                'pm.paket',
                'pm.harga as harga_varian',
                'pm.harga_mp',
                'pm.nama',
                'pm.varian',
                'produk_models.harga as harga_lokal'
            )
            ->first();

        if (!$row) {
            return redirect()->back()->withErrors(['error' => 'Produk tidak ditemukan di toko ini.']);
        }

        $paket = max((int) $row->paket, 1);

        // Harga jual: dari form, atau pm.harga, atau fallback harga model
        if ($request->filled('harga')) {
            $hargaJual = (int) $request->harga;
            DB::table('produk_marketplaces')->where('id', $row->pm_id)->update([
                'harga' => $hargaJual,
                'updated_at' => now(),
            ]);
        } else {
            $hargaJual = (int) $row->harga_varian > 0 ? (int) $row->harga_varian : (int) $row->harga_lokal;
        }

        if ($hargaJual <= 0) {
            return redirect()->back()->withErrors(['error' => 'Harga jual varian masih kosong/0.']);
        }

        $hargaBaru = (int) floor($hargaJual * $paket * (100 + $config->harga) / 100);

        if ($hargaBaru <= 0) {
            return redirect()->back()->withErrors(['error' => 'Harga hasil perhitungan tidak valid (0).']);
        }

        $priceEntry = ['original_price' => $hargaBaru];
        // model_id 0 = produk tanpa variasi, tidak perlu dikirim
        if ((int) $row->model_id > 0) {
            $priceEntry['model_id'] = (int) $row->model_id;
        }

        $body = [
            'item_id' => (int) $row->item_id,
            'price_list' => [$priceEntry],
        ];

        $resp = $this->kirimApi($config, 'product/update_price', $body);

        $namaProduk = trim($row->nama . ($row->varian ? ' - ' . $row->varian : ''));

        if (is_array($resp) && empty($resp['error'])) {
            $response = $resp['response'] ?? [];

            if (!empty($response['failure_list'])) {
                $f = $response['failure_list'][0];
                return redirect()->back()
                    ->withErrors(['error' => 'Gagal update ' . $namaProduk . ': ' . ($f['failed_reason'] ?? json_encode($f))]);
            }

            DB::table('produk_marketplaces')->where('id', $row->pm_id)->update([
                'harga_mp' => $hargaBaru,
                'update_harga_terakhir' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()
                ->withSuccess('Harga ' . $namaProduk . ' berhasil diupdate ke Rp ' . number_format($hargaBaru, 0, ',', '.'));
        }

        return redirect()->back()
            ->withErrors(['error' => 'Gagal update ' . $namaProduk . ': ' . ($resp['error'] ?? $resp['message'] ?? 'tidak diketahui')]);
    }

    public function syncStokStatus(ShopeeStockSyncService $service)
    {
        abort_if(Gate::denies('marketplace_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $pending = $service->getPendingSyncs(100);
        $marketplaces = Marketplace::where('marketplace', 'shopee')
            ->whereNotNull('shop_id')
            ->where('shop_id', '!=', 0)
            ->orderBy('nama')
            ->get();

        $recentErrors = DB::table('marketplace_logs')
            ->where('jenis', 'sync stok')
            ->orderByDesc('tanggal')
            ->limit(20)
            ->get();

        return view('admin.marketplaces.sync-stok', [
            'pending' => $pending,
            'marketplaces' => $marketplaces,
            'recentErrors' => $recentErrors,
            'cronUrl' => rtrim(config('app.url'), '/') . '/shopee/sync-stok',
        ]);
    }

    public function analisa(Request $request)
    {
        $bulanParam = $request->input('bulan', date('Y-m'));
        [$thn, $bln] = array_pad(explode('-', $bulanParam), 2, date('m'));
        $thn = (int) $thn;
        $bln = (int) $bln;

        $marketplaces = Marketplace::with('kontak')->get()->filter(fn ($mp) => $mp->kontak);
        $bulanData = $this->buildMarketplaceMonthData($marketplaces, $thn, $bln);
        $listBulan = $this->getMarketplaceBulanList();

        $tanggalAwal = sprintf('%04d-%02d-01', $thn, $bln);
        $tanggalAkhir = date('Y-m-t', strtotime($tanggalAwal));

        $rows = $marketplaces->map(function ($mp) use ($bulanData, $tanggalAwal, $tanggalAkhir) {
            $omzet = (float) ($bulanData['omzet'][$mp->id] ?? 0);
            $hpp = (float) ($bulanData['hpp'][$mp->id] ?? 0);
            $feeMp = (float) (($bulanData['total'][$mp->id] ?? 0) - ($bulanData['bayar'][$mp->id] ?? 0));
            $iklan = (float) ($bulanData['iklan'][$mp->id] ?? 0);
            $totalBiaya = $hpp + $feeMp + $iklan;
            $keuntungan = $omzet - $totalBiaya;

            return (object) [
                'marketplace' => $mp,
                'pendapatan_mp' => $omzet,
                'hpp' => $hpp,
                'fee_mp' => $feeMp,
                'iklan' => $iklan,
                'total_biaya' => $totalBiaya,
                'biaya_persen' => $omzet > 0 ? round(($totalBiaya / $omzet) * 100) : 0,
                'keuntungan' => $keuntungan,
                'margin_persen' => $omzet > 0 ? round(($keuntungan / $omzet) * 100) : 0,
                'tanggal_awal' => $tanggalAwal,
                'tanggal_akhir' => $tanggalAkhir,
            ];
        })->values();

        $totals = (object) [
            'pendapatan_mp' => $rows->sum('pendapatan_mp'),
            'hpp' => $rows->sum('hpp'),
            'fee_mp' => $rows->sum('fee_mp'),
            'iklan' => $rows->sum('iklan'),
            'total_biaya' => $rows->sum('total_biaya'),
            'keuntungan' => $rows->sum('keuntungan'),
            'biaya_persen' => $rows->sum('pendapatan_mp') > 0
                ? round(($rows->sum('total_biaya') / $rows->sum('pendapatan_mp')) * 100)
                : 0,
            'margin_persen' => $rows->sum('pendapatan_mp') > 0
                ? round(($rows->sum('keuntungan') / $rows->sum('pendapatan_mp')) * 100)
                : 0,
        ];

        return view('admin.marketplaces.analisa', compact('rows', 'totals', 'listBulan', 'bulanParam'));
    }

    public function omzetBulan(Request $request)
    {
        abort_if(Gate::denies('omzet_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tahunSekarang = date('Y');
        $listTahun = $this->getMarketplaceTahunList();
        $tahun_skr = $request->input('tahun', $tahunSekarang);
        $marketplaces = Marketplace::with('kontak')->get()->filter(fn ($mp) => $mp->kontak);
        $data = $this->buildMarketplaceBulanData($marketplaces, $tahun_skr);

        $labels = collect($data)->map(fn ($bulanData) => $bulanData['nama'])->values();

        $chartSeries = $marketplaces->map(function ($mp) use ($data) {
            return [
                'name' => $mp->nama,
                'data' => collect($data)->map(fn ($bulanData) => (float) ($bulanData['omzet'][$mp->id] ?? 0))->values()->all(),
            ];
        })->values();

        $totalsPerBulan = collect($data)->map(function ($bulanData) use ($marketplaces) {
            return $marketplaces->sum(fn ($mp) => $bulanData['omzet'][$mp->id] ?? 0);
        })->values();

        $totalsPerMarketplace = $marketplaces->mapWithKeys(function ($mp) use ($data) {
            return [$mp->id => collect($data)->sum(fn ($bulanData) => $bulanData['omzet'][$mp->id] ?? 0)];
        });

        return view('admin.marketplaces.omzetBulan', compact(
            'marketplaces',
            'data',
            'listTahun',
            'tahun_skr',
            'labels',
            'chartSeries',
            'totalsPerBulan',
            'totalsPerMarketplace'
        ));
    }

    private function getMarketplaceTahunList(): array
    {
        $tahunDariOrders = DB::table('orders')->min(DB::raw('YEAR(created_at)'));
        $tahunDariProjectMp = DB::table('project_mps')->min(DB::raw('YEAR(created_at)'));
        $tahunArr = array_filter([$tahunDariOrders, $tahunDariProjectMp]);
        $tahunPertama = !empty($tahunArr) ? min($tahunArr) : date('Y');
        $tahunSekarang = date('Y');

        $listTahun = [];
        for ($y = $tahunSekarang; $y >= $tahunPertama; $y--) {
            $listTahun[] = $y;
        }

        return $listTahun;
    }

    private function getMarketplaceBulanList(): array
    {
        $tahunSekarang = (int) date('Y');
        $bulanSekarang = (int) date('n');
        $tahunPertama = min($this->getMarketplaceTahunList());
        $bulanList = [];

        for ($tahun = $tahunPertama; $tahun <= $tahunSekarang; $tahun++) {
            $bulanAkhir = ($tahun === $tahunSekarang) ? $bulanSekarang : 12;
            for ($i = 1; $i <= $bulanAkhir; $i++) {
                $key = $tahun . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $bulanList[$key] = strtolower(date('F', mktime(0, 0, 0, $i, 1))) . ' ' . $tahun;
            }
        }

        return array_reverse($bulanList, true);
    }

    private function buildMarketplaceBulanData($marketplaces, int $tahun_skr): array
    {
        $tahunSekarang = date('Y');
        $bulan_skr = ($tahun_skr == $tahunSekarang) ? (int) date('n') : 12;
        $data = [];

        for ($i = 1; $i <= $bulan_skr; $i++) {
            $bulan = str_pad($i, 2, '0', STR_PAD_LEFT);
            $data[$bulan] = $this->buildMarketplaceMonthData($marketplaces, $tahun_skr, $i);
        }

        return $data;
    }

    private function buildMarketplaceMonthData($marketplaces, int $tahun_skr, int $bulanNum): array
    {
        $shopeeMarketplaceIds = Marketplace::where('marketplace', 'shopee')->pluck('id')->toArray();

        $produkIklan = DB::table('produks')
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->where('produks.status', 1)
            ->where(function ($q) {
                $q->where('produk_models.nama', 'like', '%Biaya Iklan%')
                    ->orWhere('produk_models.nama', 'like', '%iklan%');
            })
            ->pluck('produks.id');

        $omzetByMpId = [];
        $bayarByMpId = [];
        $totalByMpId = [];
        $hppByMpId = [];

        if (!empty($shopeeMarketplaceIds)) {
            $omzetShopeeMp = DB::table('project_mps')
                ->selectRaw('sum(project_mps.total) as omzet, project_mps.marketplace_id')
                ->whereIn('project_mps.marketplace_id', $shopeeMarketplaceIds)
                ->whereYear('project_mps.created_at', $tahun_skr)
                ->whereMonth('project_mps.created_at', $bulanNum)
                ->groupBy('project_mps.marketplace_id')
                ->get()
                ->pluck('omzet', 'marketplace_id');

            $bayarShopeeMp = DB::table('project_mps')
                ->selectRaw('sum(project_mps.bersih) as bayar, project_mps.marketplace_id')
                ->whereIn('project_mps.marketplace_id', $shopeeMarketplaceIds)
                ->whereYear('project_mps.created_at', $tahun_skr)
                ->whereMonth('project_mps.created_at', $bulanNum)
                ->groupBy('project_mps.marketplace_id')
                ->get()
                ->pluck('bayar', 'marketplace_id');

            $hppShopeeMp = DB::table('project_mps')
                ->join('project_mp_details', 'project_mps.id', '=', 'project_mp_details.project_id')
                ->selectRaw('sum(project_mp_details.hpp * project_mp_details.jumlah) as hpp, project_mps.marketplace_id')
                ->whereIn('project_mps.marketplace_id', $shopeeMarketplaceIds)
                ->whereYear('project_mps.created_at', $tahun_skr)
                ->whereMonth('project_mps.created_at', $bulanNum)
                ->groupBy('project_mps.marketplace_id')
                ->get()
                ->pluck('hpp', 'marketplace_id');

            foreach ($shopeeMarketplaceIds as $mpId) {
                $omzetByMpId[$mpId] = $omzetShopeeMp[$mpId] ?? 0;
                $bayarByMpId[$mpId] = $bayarShopeeMp[$mpId] ?? 0;
                $totalByMpId[$mpId] = $omzetShopeeMp[$mpId] ?? 0;
                $hppByMpId[$mpId] = $hppShopeeMp[$mpId] ?? 0;
            }
        }

        $kontakIds = $marketplaces->pluck('kontak_id')->filter()->unique()->values()->all();

        $omzetOrders = collect();
        $bayarOrders = collect();
        $totalOrders = collect();
        $hppOrders = collect();

        if (!empty($kontakIds)) {
            $omzetOrders = DB::table('orders')
                ->selectRaw('sum(total) as omzet, kontak_id')
                ->whereNull('deleted_at')
                ->where('marketplace', 1)
                ->whereIn('kontak_id', $kontakIds)
                ->whereYear('created_at', $tahun_skr)
                ->whereMonth('created_at', $bulanNum)
                ->groupBy('kontak_id')
                ->get()
                ->pluck('omzet', 'kontak_id');

            $bayarResultOrders = DB::table('orders')
                ->selectRaw('sum(total) as total, sum(bayar) as bayar, kontak_id')
                ->whereNull('deleted_at')
                ->where('marketplace', 1)
                ->whereIn('kontak_id', $kontakIds)
                ->whereYear('created_at', $tahun_skr)
                ->whereMonth('created_at', $bulanNum)
                ->where('bayar', '>', 0)
                ->groupBy('kontak_id')
                ->get();
            $totalOrders = $bayarResultOrders->pluck('total', 'kontak_id');
            $bayarOrders = $bayarResultOrders->pluck('bayar', 'kontak_id');

            $hppOrders = DB::table('orders')
                ->join('order_details', 'orders.id', '=', 'order_details.order_id')
                ->selectRaw('sum(order_details.hpp * order_details.jumlah) as hpp, orders.kontak_id')
                ->whereNull('orders.deleted_at')
                ->where('orders.marketplace', 1)
                ->whereIn('orders.kontak_id', $kontakIds)
                ->whereYear('orders.created_at', $tahun_skr)
                ->whereMonth('orders.created_at', $bulanNum)
                ->where('orders.bayar', '>', 0)
                ->groupBy('orders.kontak_id')
                ->get()
                ->pluck('hpp', 'kontak_id');
        }

        $omzet = collect();
        $bayar = collect();
        $total = collect();
        $hpp = collect();

        foreach ($marketplaces as $mp) {
            $mpId = $mp->id;
            $kontakId = $mp->kontak_id;
            $isShopee = strtolower($mp->marketplace ?? '') === 'shopee';
            $orderOmzet = (float) ($omzetOrders[$kontakId] ?? 0);
            $orderBayar = (float) ($bayarOrders[$kontakId] ?? 0);
            $orderTotal = (float) ($totalOrders[$kontakId] ?? 0);
            $orderHpp = (float) ($hppOrders[$kontakId] ?? 0);

            if ($isShopee) {
                $omzet[$mpId] = ($omzetByMpId[$mpId] ?? 0) + $orderOmzet;
                $bayar[$mpId] = ($bayarByMpId[$mpId] ?? 0) + $orderBayar;
                $total[$mpId] = ($totalByMpId[$mpId] ?? 0) + $orderTotal;
                $hpp[$mpId] = ($hppByMpId[$mpId] ?? 0) + $orderHpp;
            } else {
                $omzet[$mpId] = $orderOmzet;
                $bayar[$mpId] = $orderBayar;
                $total[$mpId] = $orderTotal;
                $hpp[$mpId] = $orderHpp;
            }
        }

        $iklanByKontak = DB::table('belanjas')
            ->selectRaw('sum(belanja_details.harga * belanja_details.jumlah) as potongan, belanjas.kontak_id as kontak_id')
            ->join('belanja_details', 'belanjas.id', '=', 'belanja_details.belanja_id')
            ->whereYear('belanjas.created_at', $tahun_skr)
            ->whereMonth('belanjas.created_at', $bulanNum)
            ->whereIn('belanja_details.produk_id', $produkIklan)
            ->groupBy('belanjas.kontak_id')
            ->get()
            ->pluck('potongan', 'kontak_id');

        $iklan = collect();
        foreach ($marketplaces as $mp) {
            $iklan[$mp->id] = $iklanByKontak[$mp->kontak_id] ?? 0;
        }

        return [
            'nama' => date('F', mktime(0, 0, 0, $bulanNum, 1)),
            'bulan' => $bulanNum,
            'omzet' => $omzet,
            'bayar' => $bayar,
            'total' => $total,
            'hpp' => $hpp,
            'iklan' => $iklan,
        ];
    }

    public function uploadOrderTiktok(Request $request, Marketplace $id)
    {
        $request->validate([
            'order' => 'required|file|extensions:csv|mimes:csv,txt',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {

                $config = $id;
                $marketplace = DB::table('marketplace_formats')->where('jenis', 'order')->where('marketplace', $config->marketplace)->first();

                $toko = $config->cabang_id;
                $id_shopee = $config->kontak_id;

                ////ambil data semua produk di company
                $ambil = DB::table('produks')->select('produks.id', 'hpp', 'stok', 'harga')->where('produks.status', 1)
                    ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
                    ->get();

                ////bikin array data produk dengan key id dan id_produk(id project yg lama)
                $produks = $ambil->keyBy('id');

                //////posisi header di baris brapa
                $header = $marketplace->barisHeader ?? 1;

                // Baca seluruh file ke array terlebih dahulu
                $file_path = request()->order->getRealPath();

                // Deteksi encoding dan baca file
                $content = file_get_contents($file_path);

                // Hapus BOM jika ada (UTF-8 BOM)
                $content = str_replace("\xEF\xBB\xBF", '', $content);

                // Coba deteksi delimiter (koma atau semicolon)
                $delimiter = ',';
                if (substr_count($content, ';') > substr_count($content, ',')) {
                    $delimiter = ';';
                }

                // Parse CSV dengan delimiter yang terdeteksi
                $lines = explode("\n", $content);
                $all_rows = [];
                foreach ($lines as $line) {
                    if (trim($line) !== '') {
                        $baris = str_getcsv($line, $delimiter, '"');
                        $all_rows[] = $baris;
                    }
                }

                // Validasi header file terlebih dahulu
                if (count($all_rows) < $header) {
                    throw new \Exception('File Excel tidak memiliki header yang valid');
                }

                $header_row = $all_rows[$header - 1]; // array index dimulai dari 0

                // Validasi header
                if ($header_row[0] != $marketplace->kolom1 or $header_row[1] != $marketplace->kolom2 or $header_row[2] != $marketplace->kolom3) {
                    throw new \Exception('Format header Excel salah. Expected: ' . $marketplace->kolom1 . ', ' . $marketplace->kolom2 . ', ' . $marketplace->kolom3);
                }

                // Skip baris index 1 (baris deskripsi kolom) dengan menghapusnya dari array
                if (isset($all_rows[1])) {
                    unset($all_rows[1]);
                    $all_rows = array_values($all_rows); // reindex array
                }

                $order = $orderdetil = $stok = $batal = [];
                $input = false;
                $notaTerakhir = false;
                $nota_skr = 0;
                $deathline = null;
                $sudahKetemuNotaTerakhir = false;

                //////jika marketplace baru, langsung input, ga usah dicek dulu
                if ($config->baruOrder == 1) {
                    $input = true;
                    $notaTerakhir = true;
                }

                /////ambil ida project_flow
                $batal_id = Produksi::ambilFlow('batal');
                $finish_id = Produksi::ambilFlow('finish');
                $awal_id = Produksi::ambilFlow('Persiapan');

                //////ambil nota terakhir yg udah terinput
                $terakhir = Order::where('kontak_id', $id_shopee)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Proses data dari bawah ke atas (reverse array), skip header
                $data_rows = array_slice($all_rows, $header); // ambil data setelah header
                $data_rows = array_reverse($data_rows); // balik urutan dari bawah ke atas

                $total_rows = count($all_rows);

                foreach ($data_rows as $index => $baris) {

                    // Skip baris yang kosong atau tidak lengkap
                    if (empty($baris) || count($baris) < 10) {
                        continue;
                    }

                    // Hitung nomor baris yang sebenarnya (dari bawah ke atas)
                    $no_baris = $total_rows - $index;

                    /////tambahin 1 kolom didepan dengan nomor baris
                    array_unshift($baris, $no_baris);

                    // Validasi index yang dibutuhkan ada di array
                    $required_indexes = [
                        $marketplace->nota,
                        $marketplace->status,
                        $marketplace->sku_anak,
                        $marketplace->sku,
                        $marketplace->tanggal,
                        $marketplace->nama,
                        $marketplace->tema,
                        $marketplace->saldo,
                        $marketplace->jumlah,
                        $marketplace->harga,
                        $marketplace->ongkir
                    ];

                    $max_index = max($required_indexes);
                    if (count($baris) <= $max_index) {
                        // Baris tidak lengkap, skip
                        continue;
                    }

                    $nota = $baris[$marketplace->nota];
                    $status = $baris[$marketplace->status];
                    $barang = $baris[$marketplace->sku_anak];
                    if (empty($barang))
                        $barang = $baris[$marketplace->sku];

                    //////pengecekan order yg udah terinput sebelumnya
                    if (!$input) {

                        ////////jika statusnya batal, masukin ke array batal
                        if ($status == $marketplace->batal and strpos($barang, 'CUSTOM_') !== false)
                            $batal[$nota] = 1;

                        /////karena baca dari bawah ke atas, jika ketemu nota yang belum ada di database, mulai input
                        if (!$terakhir) {
                            // Jika belum ada order sama sekali, langsung mulai input
                            $input = true;
                            $notaTerakhir = true;
                        } else {
                            // Jika belum ketemu nota yang sama dengan terakhir
                            if (!$sudahKetemuNotaTerakhir) {
                                if ($terakhir->nota == $nota) {
                                    // Ketemu nota yang sama dengan terakhir, set flag dan skip baris ini
                                    // Looping selanjutnya (nota berbeda) baru akan dimasukkan
                                    $sudahKetemuNotaTerakhir = true;
                                    continue;
                                } else {
                                    // Belum ketemu nota yang sama, skip baris ini (karena lebih lama dari yang terakhir)
                                    continue;
                                }
                            } else {
                                // Sudah ketemu nota yang sama sebelumnya, mulai input
                                $input = true;
                                $notaTerakhir = true;
                            }
                        }
                    }


                    if ($input) {

                        $tanggal = $baris[$marketplace->tanggal];
                        $tanggal = Carbon::createFromFormat($marketplace->formatTanggal, $tanggal)->toDateTimeString();
                        $nama = $baris[$marketplace->nama];
                        $tema = $baris[$marketplace->tema];
                        $total = $baris[$marketplace->saldo];
                        $total = str_replace(".", "", $total);

                        $jumlah = $baris[$marketplace->jumlah];
                        $harga = str_replace("Rp ", "", $baris[$marketplace->harga]);
                        $harga = str_replace(".", "", $harga);

                        if ($status == $marketplace->batal) {
                            $produksi_id = $batal_id;
                            $total = 0;
                        } else
                            $produksi_id = $finish_id;

                        // Tambah 7 hari dari $tanggal jika deathline kosong/gagal di-parse
                        $deathline = $this->parseDeathline(
                            $this->getCsvColumnValue($baris, $marketplace->deathline),
                            $tanggal,
                            $marketplace->formatTanggal,
                            7
                        );

                        //jika ganti nota
                        if ($nota != $nota_skr) {

                            // Karena baca dari bawah ke atas, nota_awal akan selalu diupdate dengan nota terbaru
                            $nota_awal = $nota;

                            $ongkir = str_replace(".", "", $baris[$marketplace->ongkir]);

                            $order[] = array(
                                'kontak_id' => $id_shopee,
                                'total' => $total,
                                'nota' => $nota,
                                'created_at' => $tanggal,
                                'konsumen_detail' => $nama,
                                'deathline' => $deathline,
                                'marketplace' => 1,
                                'ongkir' => $ongkir
                            );
                        }
                        ////jika sku NON_PRODUK, skip penginputan
                        if ($barang == "NON_PRODUK")
                            continue;

                        $custom = '';
                        $orderCustom = false;

                        //////jika sku depannya ada CUSTOM_ , hapus tulisan itu, sisain sku nya
                        if (strpos($barang, 'CUSTOM_') !== false) {
                            $barang = str_replace('CUSTOM_', "", $barang);
                            $orderCustom = true;
                            $custom = $tema;

                            if ($status == $marketplace->batal) {
                                $produksi_id = $batal_id;
                            } else {
                                $produksi_id = $awal_id;
                            }
                        }

                        $paket = 1;
                        if (strpos($barang, '_') !== false) {
                            $skuParts = explode('_', $barang);
                            $barang = $skuParts[0]; // Mengambil bagian pertama dari SKU
                            if (isset($skuParts[1]) && is_numeric($skuParts[1])) {
                                $paket = (int) $skuParts[1]; // bagian kedua dari SKU = jumlah paket
                                $jumlah = (int) $jumlah * $paket;
                                $harga = $harga / $jumlah;
                            }
                        }

                        /////////////////cek, apakah sku udah sesuai dgn produk_id
                        $produk = $produks[$barang] ?? false;
                        if (!$produk)
                            throw new \Exception('sku: ' . $barang . ', nama: ' . $baris[$marketplace->produk] . ', tidak ada di sistem');

                        $hpp = Produk::find($produk->id);

                        /////mulai input orderdetil ke array
                        $orderdetil[] = array(
                            'produk_id' => $produk->id,
                            'jumlah' => $jumlah,
                            'tema' => $custom,
                            'harga' => $harga,
                            'hpp' => $hpp->hpp,
                            'produksi_id' => $produksi_id,
                            'nota' => $nota,
                            'created_at' => $tanggal,
                            'deathline' => $deathline
                        );

                        ///////////////////kalo ordernya ga batal, dan produknya ada stoknya, input brapa yg terjual
                        if ($status != $marketplace->batal and $produk->stok == 1 and !$orderCustom)
                            $stok[] = array(
                                'produk_id' => $produk->id,
                                'jumlah' => $jumlah,
                                'keterangan' => 'dibeli oleh ' . $nama,
                                'detail' => $nota
                            );
                    }
                    $nota_skr = $nota;
                }

                if (!$notaTerakhir)
                    throw new \Exception('rentang tgl kurang panjang');

                ////////order yg udah terinput, tp cek apakah ada yg berubah dl batal
                if ($batal) {

                    $batal = array_keys($batal);

                    ////////////////cari di db, yg di excel nya batal, tp di table order_details msh blm batal
                    $batalx = DB::table('order_details')->whereIn('nota', $batal)->where('produksi_id', $finish_id)->get();

                    $diubahBatal = $produkBatal = $projectBatal = [];
                    //////kalo ada order_details yg blm dirubah ke batal, maka proses utk rubah
                    foreach ($batalx as $yy) {
                        ///project_detail yg blm dirubah ke batal
                        $diubahBatal[] = $yy->id;
                        ////project yg blm dirubah ke batal
                        $projectBatal[$yy->order_id] = 1;
                        //////jumlah produk yg batal dibeli
                        $produk = $produks[$yy->produk_id];
                        if ($produk->stok == 1)
                            $produkBatal[$yy->produk_id] = $yy->jumlah + ($produkBatal[$yy->produk_id] ?? 0);
                    }

                    ////proses perubahan ke db
                    if ($diubahBatal) {
                        DB::table('order_details')->whereIn('id', $diubahBatal)->update(['produksi_id' => $batal_id]);

                        DB::table('orders')->whereIn('id', array_keys($projectBatal))->update(['total' => 0]);
                    }

                    /////jika ada produk yg dikembalikan
                    if ($produkBatal) {
                        foreach ($produkBatal as $produk_id => $stokx) {

                            $produk = $produks[$produk_id];

                            app(StokService::class)->tambah(
                                $produk_id,
                                $stokx,
                                'batal',
                                'upload ' . $config->nama
                            );
                        }
                    }
                }

                //////////////jika ada order baru/////////////////////////////////////////////////////////////
                if ($input) {
                    // Karena data dibaca dari bawah ke atas, perlu membalik urutan sebelum insert
                    // agar urutan data di database tetap sesuai dengan urutan asli file
                    $order = array_reverse($order);
                    $orderdetil = array_reverse($orderdetil);

                    // Filter order yang belum ada di database untuk menghindari duplikat
                    $order_filtered = [];
                    $orderdetil_filtered = [];

                    foreach ($order as $order_item) {
                        // Cek apakah order dengan nota dan kontak_id ini sudah ada
                        $existing_order = DB::table('orders')
                            ->where('nota', $order_item['nota'])
                            ->where('kontak_id', $order_item['kontak_id'])
                            ->exists();

                        if (!$existing_order) {
                            $order_filtered[] = $order_item;

                            // Ambil order detail yang sesuai dengan nota ini
                            foreach ($orderdetil as $detail_item) {
                                if ($detail_item['nota'] == $order_item['nota']) {
                                    $orderdetil_filtered[] = $detail_item;
                                }
                            }
                        }
                    }

                    // Insert hanya order yang belum ada di database
                    if (!empty($order_filtered)) {
                        DB::table('orders')->insert($order_filtered);
                    }

                    if (!empty($orderdetil_filtered)) {
                        DB::table('order_details')->insert($orderdetil_filtered);
                    }

                    // Update order_id ke table order_details hanya jika ada order detail yang baru diinsert
                    if (!empty($orderdetil_filtered)) {
                        ////ambil orderdetil yg pertama akan diinput (sekarang adalah yang terakhir dalam array)
                        $orderdetil_awal = DB::table('order_details')->where('nota', $nota_awal)->orderBy('id', 'desc')->first();

                        if ($orderdetil_awal) {
                            //////update order_id ke table order_details (pas pertama input msh kosong)
                            DB::statement("UPDATE order_details
                                SET order_id = (
                                    SELECT id
                                    FROM orders
                                    WHERE orders.nota=order_details.nota
                                        and kontak_id=" . $id_shopee . "
                                        limit 1
                                ) where id>=" . $orderdetil_awal->id . " and order_details.nota is not Null");
                        }
                    }


                    //////ngurangi stok yg terjual/////////////////////////////////////////////////////////
                    if ($stok) {
                        foreach ($stok as $value) {
                            app(StokService::class)->kurang(
                                $value['produk_id'],
                                $value['jumlah'],
                                'jual',
                                $value['keterangan'],
                                $value['detail'],
                                [],
                                false
                            );
                        }
                    }
                }


                if ($config->baruOrder == 1)
                    $config->update(['baruOrder' => 0]);

                $config->update(['tglUploadOrder' => now()]);
            });
            return redirect()->route('marketplaces.show', $id->id)->withSuccess(__('Upload Order berhasil'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Upload Order gagal: ' . $e->getMessage()]);
        }
    }

    private function isCsvHeaderMatch(array $baris, object $marketplace): bool
    {
        $configuredHeaders = [];
        foreach (get_object_vars($marketplace) as $key => $value) {
            if (preg_match('/^kolom(\d+)$/', (string) $key, $match)) {
                if ($value !== null && trim((string) $value) !== '') {
                    $configuredHeaders[(int) $match[1]] = $this->normalizeCsvHeader((string) $value);
                }
            }
        }

        if (empty($configuredHeaders)) {
            return true;
        }

        ksort($configuredHeaders);

        foreach ($configuredHeaders as $index => $expectedHeader) {
            $actualHeader = $this->normalizeCsvHeader((string) ($baris[$index] ?? ''));
            if ($actualHeader !== $expectedHeader) {
                return false;
            }
        }

        return true;
    }

    private function normalizeCsvHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;
        $value = str_replace("\xC2\xA0", ' ', $value);
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }

    /**
     * Ambil nilai kolom CSV berdasarkan index format marketplace.
     * Index null (marketplace tanpa kolom deathline) diperlakukan sebagai kosong.
     */
    private function getCsvColumnValue(array $baris, $columnIndex): string
    {
        if ($columnIndex === null || $columnIndex === '') {
            return '';
        }

        return trim((string) ($baris[(int) $columnIndex] ?? ''));
    }

    /**
     * Ubah nilai deathline mentah dari CSV menjadi tanggal Y-m-d yang valid.
     * Jika kosong/gagal di-parse, fallback ke $tanggal + $fallbackDays hari.
     * Return null bila tidak ada acuan tanggal (hindari simpan 0000-00-00).
     */
    private function parseDeathline($value, ?string $tanggal = null, ?string $formatTanggal = null, int $fallbackDays = 7): ?string
    {
        $value = trim((string) $value);

        if ($value !== '' && $value !== '-') {
            $parsed = $this->tryParseMarketplaceDate($value, $formatTanggal);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (!empty($tanggal)) {
            try {
                return Carbon::parse($tanggal)->addDays($fallbackDays)->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private function tryParseMarketplaceDate(string $value, ?string $formatTanggal = null): ?string
    {
        $formats = array_values(array_unique(array_filter([
            $formatTanggal,
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
        ])));

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($this->isValidDeathlineDate($parsed)) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable $e) {
                // coba format berikutnya
            }
        }

        try {
            $parsed = Carbon::parse($value);
            if ($this->isValidDeathlineDate($parsed)) {
                return $parsed->toDateString();
            }
        } catch (\Throwable $e) {
            // gagal parse
        }

        return null;
    }

    private function isValidDeathlineDate(Carbon $date): bool
    {
        return $date->year >= 2000 && $date->year <= 2100;
    }

    private function normalizeMarketplaceDate(string $value, ?string $formatTanggal = null): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }

        try {
            if (!empty($formatTanggal)) {
                return Carbon::createFromFormat($formatTanggal, $value)->toDateTimeString();
            }
        } catch (\Throwable $e) {
            // fallback ke parser umum di bawah jika format spesifik gagal
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function isSameDateValue(string $left, string $right): bool
    {
        try {
            return Carbon::parse($left)->format('Y-m-d H:i:s') === Carbon::parse($right)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return trim($left) === trim($right);
        }
    }

    private function buildKeuanganBaruColumnMap(array $baris): ?array
    {
        // $baris sudah di-unshift dengan nomor baris di index 0
        $normalized = [];
        foreach ($baris as $idx => $val) {
            $normalized[$idx] = $this->normalizeCsvHeader((string) $val);
        }

        // minimal wajib (format lama & format terbaru sama-sama punya ini)
        $need = [
            'request time' => 'request_time',
            'amount' => 'amount',
            'status' => 'status',
            'success time' => 'success_time',
            'bank account' => 'bank_account',
        ];

        $map = [];
        foreach ($need as $header => $key) {
            $pos = array_search($header, $normalized, true);
            if ($pos === false) {
                return null;
            }
            $map[$key] = (int) $pos;
        }

        // optional untuk format terbaru
        $optional = [
            'type' => 'type',
            'reference id' => 'reference_id',
        ];
        foreach ($optional as $header => $key) {
            $pos = array_search($header, $normalized, true);
            if ($pos !== false) {
                $map[$key] = (int) $pos;
            }
        }

        return $map;
    }

    private function parseCsvNumber(string $value): float
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        // hapus pemisah ribuan umum, jaga minus
        $value = str_replace([',', ' '], '', $value);
        // beberapa export pakai titik sebagai ribuan
        $value = preg_replace('/\.(?=\d{3}(\D|$))/', '', $value) ?? $value;
        // buang trailing .0 / .00
        $value = preg_replace('/\.0+$/', '', $value) ?? $value;

        return (float) $value;
    }
}
