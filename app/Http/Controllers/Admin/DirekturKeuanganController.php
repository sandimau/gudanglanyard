<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunDetail;
use App\Models\Penggajian;
use App\Models\Tunjangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DirekturKeuanganController extends Controller
{
    public function index()
    {
        // Tanggal bisnis dipakai untuk hutang dan pembayaran; 30 hari kalender termasuk hari ini.
        $akhir = today();
        $awal = $akhir->copy()->subDays(29);
        $awalTanggal = $awal->toDateString();
        $akhirTanggal = $akhir->toDateString();

        // Agregasi pembayaran sebelum join agar satu hutang tidak terhitung berulang.
        $pembayaran = DB::table('hutang_details')
            ->where('tanggal', '<=', $akhirTanggal)
            ->select('hutang_id')
            ->selectRaw('SUM(jumlah) as dibayar')
            ->selectRaw('SUM(CASE WHEN tanggal < ? THEN jumlah ELSE 0 END) as sebelum', [$awalTanggal])
            ->selectRaw('SUM(CASE WHEN tanggal >= ? THEN jumlah ELSE 0 END) as periode', [$awalTanggal])
            ->groupBy('hutang_id');

        // Hutang tidak mempunyai cabang_id; ringkasan hutang lintas cabang.
        $hutang = DB::table('hutangs as h')
            ->leftJoinSub($pembayaran, 'bayar', function ($join) {
                $join->on('bayar.hutang_id', '=', 'h.id');
            })
            ->leftJoin('kontaks as k', 'k.id', '=', 'h.kontak_id')
            ->whereIn('h.jenis', ['hutang', 'belanja', 'belanja produksi'])
            ->where('h.tanggal', '<=', $akhirTanggal)
            ->select('h.id', 'h.tanggal', 'h.jenis', 'h.jumlah', 'h.detail_id', 'k.nama as kontak')
            ->selectRaw('COALESCE(bayar.sebelum, 0) as sebelum, COALESCE(bayar.periode, 0) as periode, COALESCE(bayar.dibayar, 0) as dibayar')
            ->get();

        $saldoAwal = 0;
        $hutangBaru = 0;
        $dibayar = 0;
        $dibayarLama = 0;
        $saldoAkhir = 0;
        $tunggakanLama = 0;
        $jumlahTunggakanLama = 0;
        $perJenis = [];
        $perKontak = [];
        $prioritas = [];

        foreach ($hutang as $item) {
            $lama = $item->tanggal < $awalTanggal;
            $sisa = max(0, (float) $item->jumlah - (float) $item->dibayar);
            $saldoAwal += $lama ? (float) $item->jumlah - (float) $item->sebelum : 0;
            $hutangBaru += $lama ? 0 : (float) $item->jumlah;
            $dibayar += (float) $item->periode;
            $dibayarLama += $lama ? (float) $item->periode : 0;
            $saldoAkhir += (float) $item->jumlah - (float) $item->dibayar;

            if ($sisa <= 0) {
                continue;
            }

            if ($lama) {
                $tunggakanLama += $sisa;
                $jumlahTunggakanLama++;
            }

            $perJenis[$item->jenis] = ($perJenis[$item->jenis] ?? 0) + $sisa;
            $nama = $item->kontak ?: 'Tanpa kontak';
            $perKontak[$nama] = ($perKontak[$nama] ?? 0) + $sisa;
            $item->sisa = $sisa;
            $item->umur = Carbon::parse($item->tanggal)->diffInDays($akhir);
            $prioritas[] = $item;
        }

        arsort($perKontak);
        usort($prioritas, function ($a, $b) {
            return $b->umur <=> $a->umur ?: $b->sisa <=> $a->sisa;
        });

        return view('admin.laporan.direktur-keuangan', [
            'awal' => $awal, 'akhir' => $akhir,
            'saldoAwal' => $saldoAwal, 'hutangBaru' => $hutangBaru,
            'dibayar' => $dibayar, 'dibayarLama' => $dibayarLama,
            'saldoAkhir' => $saldoAkhir, 'tunggakanLama' => $tunggakanLama,
            'jumlahTunggakanLama' => $jumlahTunggakanLama,
            'perJenis' => $perJenis, 'perKontak' => array_slice($perKontak, 0, 5, true),
            'prioritas' => array_slice($prioritas, 0, 10),
        ]);
    }

    public function keuangan()
    {
        $akhir = today();
        $awal = $akhir->copy()->subDays(29);
        $dari = $awal->copy()->startOfDay();
        $sampai = $akhir->copy()->addDay()->startOfDay();

        // Sama dengan sumber omzet laba rugi: orders dan project_mps, tanpa order yang dihapus.
        $offline = DB::table('orders')->whereNull('deleted_at')->whereNull('marketplace')
            ->where('created_at', '>=', $dari)->where('created_at', '<', $sampai)->sum('total');
        $onlineOrder = DB::table('orders')->whereNull('deleted_at')->whereNotNull('marketplace')
            ->where('created_at', '>=', $dari)->where('created_at', '<', $sampai)->sum('total');
        $onlineProject = DB::table('project_mps')
            ->where('created_at', '>=', $dari)->where('created_at', '<', $sampai)->sum('total');
        $omzet = $offline + $onlineOrder + $onlineProject;

        // Satu sumber baris untuk total HPP dan daftar anomali agar keduanya selalu cocok.
        $hpp = DB::query()->fromSub($this->hppLines($dari, $sampai), 'jual')->sum('hpp_total');
        $anomaliHpp = DB::query()->fromSub($this->hppLines($dari, $sampai), 'jual')
            ->select('produk_id', 'model', 'varian')
            ->selectRaw('SUM(jumlah) as unit, SUM(jual_total) as nilai_jual, SUM(hpp_total) as nilai_hpp, SUM(CASE WHEN fallback = 1 THEN 1 ELSE 0 END) as jumlah_fallback')
            ->groupBy('produk_id', 'model', 'varian')
            ->havingRaw('SUM(hpp_total) > SUM(jual_total)')
            ->orderByRaw('(SUM(hpp_total) - SUM(jual_total)) DESC')
            ->get();

        $biayaKategori = DB::table('belanja_details as bd')
            ->join('belanjas as b', 'b.id', '=', 'bd.belanja_id')
            ->join('produks as p', 'p.id', '=', 'bd.produk_id')
            ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
            ->leftJoin('produk_kategoris as pk', 'pk.id', '=', 'pm.kategori_id')
            ->whereNull('b.deleted_at')->whereNull('pm.stok')
            ->where('b.created_at', '>=', $dari)->where('b.created_at', '<', $sampai)
            ->selectRaw("COALESCE(pk.nama, 'Tanpa kategori') as kategori, SUM(bd.harga * bd.jumlah) as total")
            ->groupBy('pk.nama')->orderByDesc('total')->get();
        $operasional = $biayaKategori->sum('total');
        $gaji = Penggajian::where('created_at', '>=', $dari)->where('created_at', '<', $sampai)
            ->sum(DB::raw('total + COALESCE(kasbon, 0)'));
        $tunjangan = Tunjangan::where('created_at', '>=', $dari)->where('created_at', '<', $sampai)->sum('jumlah');

        // Piutang terpisah dari order belum lunas; dua sumber ini tidak digabung karena bisa tumpang tindih.
        $pembayaranPiutang = DB::table('hutang_details')->where('tanggal', '<=', $akhir->toDateString())
            ->select('hutang_id')->selectRaw('SUM(jumlah) as dibayar')->groupBy('hutang_id');
        $piutangs = DB::table('hutangs as h')
            ->leftJoinSub($pembayaranPiutang, 'bayar', function ($join) { $join->on('bayar.hutang_id', '=', 'h.id'); })
            ->where('h.jenis', 'piutang')->where('h.tanggal', '<=', $akhir->toDateString())
            ->selectRaw('h.jumlah - COALESCE(bayar.dibayar, 0) as sisa')->get();
        $piutang = $piutangs->sum(fn($row) => max(0, $row->sisa));

        $belumDibayarOrder = DB::table('orders')->whereNull('deleted_at')->whereNull('marketplace')
            ->where('created_at', '<', $sampai)
            ->whereRaw('total > COALESCE(bayar, 0)')
            ->sum(DB::raw('total - COALESCE(bayar, 0)'));

        return view('admin.laporan.ringkasan-keuangan', [
            'awal' => $awal, 'akhir' => $akhir,
            'kas' => AkunDetail::TotalKas(), 'offline' => $offline,
            'onlineOrder' => $onlineOrder, 'onlineProject' => $onlineProject,
            'omzet' => $omzet, 'hpp' => $hpp, 'anomaliHpp' => $anomaliHpp, 'biayaKategori' => $biayaKategori,
            'operasional' => $operasional, 'gaji' => $gaji, 'tunjangan' => $tunjangan,
            'piutang' => $piutang, 'belumDibayarOrder' => $belumDibayarOrder,
        ]);
    }

    public function detail(Request $request, string $scope, string $metric)
    {
        $akhir = today();
        $awal = $akhir->copy()->subDays(29);
        $dari = $awal->copy()->startOfDay();
        $sampai = $akhir->copy()->addDay()->startOfDay();
        $request->validate(['id' => 'nullable|integer|min:1', 'jenis' => 'nullable|string|max:100', 'kontak' => 'nullable|string|max:255', 'kategori' => 'nullable|string|max:255']);
        if ($scope === 'keuangan' && $metric === 'hpp-anomali') {
            $id = $request->integer('id');
            abort_unless($id > 0, 404);
            $produk = DB::table('produks as p')->join('produk_models as m', 'm.id', '=', 'p.produk_model_id')
                ->where('p.id', $id)->select('p.id', 'p.nama as varian', 'm.nama as model', 'p.hpp as hpp_katalog', 'm.satuan')->first();
            abort_unless($produk, 404);
            $transaksi = DB::query()->fromSub($this->hppLines($dari, $sampai), 'jual')
                ->where('produk_id', $id)->orderByDesc('tanggal')->limit(100)->get();

            return view('admin.laporan.partials.anomali-hpp-detail', compact('produk', 'transaksi', 'awal', 'akhir'));
        }
        $rows = collect();
        $subtitle = '30 hari: ' . $awal->format('d/m/Y') . ' – ' . $akhir->format('d/m/Y');

        if ($scope === 'hutang') {
            abort_unless(in_array($metric, ['awal', 'baru', 'bayar', 'akhir', 'lama', 'jenis', 'kontak', 'transaksi'], true), 404);
            $titles = ['awal' => 'Saldo hutang awal', 'baru' => 'Hutang baru', 'bayar' => 'Pembayaran hutang', 'akhir' => 'Saldo hutang akhir', 'lama' => 'Hutang lama belum lunas', 'jenis' => 'Hutang menurut jenis', 'kontak' => 'Hutang per kontak', 'transaksi' => 'Riwayat hutang'];
            $title = $titles[$metric];
            $payments = DB::table('hutang_details')->where('tanggal', '<=', $akhir->toDateString())
                ->select('hutang_id')
                ->selectRaw('SUM(jumlah) as dibayar')
                ->selectRaw('SUM(CASE WHEN tanggal < ? THEN jumlah ELSE 0 END) as sebelum', [$awal->toDateString()])
                ->groupBy('hutang_id');
            $base = DB::table('hutangs as h')->leftJoinSub($payments, 'p', function ($join) { $join->on('p.hutang_id', '=', 'h.id'); })
                ->leftJoin('kontaks as k', 'k.id', '=', 'h.kontak_id')
                ->whereIn('h.jenis', ['hutang', 'belanja', 'belanja produksi'])
                ->where('h.tanggal', '<=', $akhir->toDateString());
            if ($metric === 'bayar') {
                $rows = DB::table('hutang_details as d')->join('hutangs as h', 'h.id', '=', 'd.hutang_id')
                    ->leftJoin('kontaks as k', 'k.id', '=', 'h.kontak_id')
                    ->whereIn('h.jenis', ['hutang', 'belanja', 'belanja produksi'])
                    ->where('h.tanggal', '<=', $akhir->toDateString())
                    ->whereBetween('d.tanggal', [$awal->toDateString(), $akhir->toDateString()])
                    ->select('h.id', 'd.tanggal', 'h.jenis', 'k.nama as nama', 'd.jumlah as nilai')
                    ->orderByDesc('d.tanggal')->limit(100)->get();
            } else {
                if ($metric === 'transaksi') {
                    $base->where('h.id', $request->integer('id'));
                    $subtitle = 'Semua pembayaran yang tercatat sampai hari ini';
                } elseif ($metric === 'awal') {
                    $base->where('h.tanggal', '<', $awal->toDateString())->whereRaw('h.jumlah > COALESCE(p.sebelum, 0)');
                } elseif ($metric === 'baru') {
                    $base->where('h.tanggal', '>=', $awal->toDateString());
                } else {
                    $base->whereRaw('h.jumlah > COALESCE(p.dibayar, 0)');
                    if ($metric === 'lama') $base->where('h.tanggal', '<', $awal->toDateString());
                    if ($metric === 'jenis') $base->where('h.jenis', $request->input('jenis'));
                    if ($metric === 'kontak') {
                        $request->input('kontak') === 'Tanpa kontak'
                            ? $base->whereNull('k.nama')
                            : $base->where('k.nama', $request->input('kontak'));
                    }
                }
                $rows = $base->select('h.id', 'h.tanggal', 'h.jenis', 'k.nama')
                    ->selectRaw('CASE WHEN ? = ? THEN h.jumlah - COALESCE(p.sebelum, 0) WHEN ? = ? THEN h.jumlah ELSE h.jumlah - COALESCE(p.dibayar, 0) END as nilai', [$metric, 'awal', $metric, 'baru'])
                    ->orderByDesc('h.tanggal')->limit(100)->get();
            }
            if ($metric === 'transaksi' && $rows->isNotEmpty()) {
                $title .= ' · ' . ($rows->first()->nama ?: 'Tanpa kontak');
                $history = DB::table('hutang_details')->where('hutang_id', $rows->first()->id)
                    ->where('tanggal', '<=', $akhir->toDateString())->orderByDesc('tanggal')->get(['tanggal', 'jumlah', 'keterangan']);
            }
        } elseif ($scope === 'keuangan') {
            abort_unless(in_array($metric, ['omzet', 'offline', 'online-order', 'online-project', 'hpp', 'margin', 'kas', 'beban', 'operasional', 'kategori', 'gaji', 'tunjangan', 'piutang', 'order-belum'], true), 404);
            $titles = ['omzet' => 'Rincian omzet', 'offline' => 'Order non-marketplace', 'online-order' => 'Order marketplace', 'online-project' => 'Proyek marketplace', 'hpp' => 'HPP produk terjual', 'margin' => 'Perhitungan selisih omzet − HPP', 'kas' => 'Saldo per akun kas/bank', 'beban' => 'Rincian kelompok beban', 'operasional' => 'Belanja operasional', 'kategori' => 'Belanja per kategori', 'gaji' => 'Penggajian', 'tunjangan' => 'Tunjangan', 'piutang' => 'Piutang belum lunas', 'order-belum' => 'Order non-marketplace belum lunas'];
            $title = $titles[$metric];
            if (in_array($metric, ['margin', 'beban'], true)) {
                $data = $this->keuangan()->getData();
                $subtitle = $metric === 'margin' ? 'Omzet dikurangi HPP; belum termasuk beban lain' : $subtitle;
                $items = $metric === 'margin'
                    ? ['Omzet' => $data['omzet'], 'HPP terjual' => -$data['hpp']]
                    : ['Operasional non-stok' => $data['operasional'], 'Penggajian' => $data['gaji'], 'Tunjangan' => $data['tunjangan']];
                $rows = collect($items)->map(fn($nilai, $nama) => (object) compact('nama', 'nilai'))->values();
            } elseif (in_array($metric, ['omzet', 'offline', 'online-order', 'online-project'], true)) {
                if (in_array($metric, ['omzet', 'offline', 'online-order'], true)) {
                    $orders = DB::table('orders')->whereNull('deleted_at')
                        ->where('created_at', '>=', $dari)->where('created_at', '<', $sampai);
                    if ($metric === 'offline') $orders->whereNull('marketplace');
                    if ($metric === 'online-order') $orders->whereNotNull('marketplace');
                    $rows = $orders->select('id', 'created_at as tanggal', 'total as nilai')
                        ->selectRaw("'Order' as nama")->orderByDesc('created_at')->limit(100)->get();
                }
                if (in_array($metric, ['omzet', 'online-project'], true)) {
                    $projects = DB::table('project_mps')->where('created_at', '>=', $dari)->where('created_at', '<', $sampai)
                        ->select('id', 'created_at as tanggal', 'total as nilai')->selectRaw("'Proyek marketplace' as nama")
                        ->orderByDesc('created_at')->limit(100)->get();
                    $rows = $rows->concat($projects)->sortByDesc('tanggal')->take(100)->values();
                }
            } elseif ($metric === 'hpp') {
                $rows = DB::query()->fromSub($this->hppLines($dari, $sampai), 'jual')
                    ->orderByDesc('tanggal')->orderByDesc('transaksi_id')->limit(100)->get();
            } elseif ($metric === 'kas') {
                $subtitle = 'Saldo saat ini, bukan transaksi dalam 30 hari';
                $rows = DB::table('akun_details as a')->join('akun_kategoris as ak', 'ak.id', '=', 'a.akun_kategori_id')
                    ->whereNull('a.deleted_at')->whereNull('ak.deleted_at')->whereIn('a.akun_kategori_id', [1, 8])
                    ->select('a.nama', 'a.saldo as nilai')->orderByDesc('a.saldo')->limit(100)->get();
            } elseif (in_array($metric, ['operasional', 'kategori'], true)) {
                $query = DB::table('belanja_details as d')->join('belanjas as b', 'b.id', '=', 'd.belanja_id')
                    ->join('produks as p', 'p.id', '=', 'd.produk_id')->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
                    ->leftJoin('produk_kategoris as pk', 'pk.id', '=', 'pm.kategori_id')
                    ->whereNull('b.deleted_at')->whereNull('pm.stok')->where('b.created_at', '>=', $dari)->where('b.created_at', '<', $sampai);
                if ($metric === 'kategori') {
                    $request->input('kategori') === 'Tanpa kategori'
                        ? $query->whereNull('pk.nama')
                        : $query->where('pk.nama', $request->input('kategori'));
                }
                $rows = $query->select('b.id', 'b.created_at as tanggal', 'p.nama')
                    ->selectRaw('d.harga * d.jumlah as nilai')->orderByDesc('b.created_at')->limit(100)->get();
            } elseif ($metric === 'gaji') {
                $rows = Penggajian::where('created_at', '>=', $dari)->where('created_at', '<', $sampai)
                    ->select('id', 'created_at as tanggal')->selectRaw("'Penggajian' as nama, total + COALESCE(kasbon, 0) as nilai")
                    ->orderByDesc('created_at')->limit(100)->get();
            } elseif ($metric === 'tunjangan') {
                $rows = Tunjangan::where('created_at', '>=', $dari)->where('created_at', '<', $sampai)
                    ->select('id', 'created_at as tanggal', 'jumlah as nilai')->selectRaw("'Tunjangan' as nama")
                    ->orderByDesc('created_at')->limit(100)->get();
            } elseif ($metric === 'piutang') {
                $subtitle = 'Posisi sampai hari ini';
                $payments = DB::table('hutang_details')->where('tanggal', '<=', $akhir->toDateString())
                    ->select('hutang_id')->selectRaw('SUM(jumlah) as dibayar')->groupBy('hutang_id');
                $rows = DB::table('hutangs as h')->leftJoinSub($payments, 'p', function ($join) { $join->on('p.hutang_id', '=', 'h.id'); })
                    ->leftJoin('kontaks as k', 'k.id', '=', 'h.kontak_id')->where('h.jenis', 'piutang')
                    ->where('h.tanggal', '<=', $akhir->toDateString())->whereRaw('h.jumlah > COALESCE(p.dibayar, 0)')
                    ->select('h.id', 'h.tanggal', 'k.nama')->selectRaw('h.jumlah - COALESCE(p.dibayar, 0) as nilai')
                    ->orderByDesc('h.tanggal')->limit(100)->get();
            } else {
                $subtitle = 'Posisi sampai hari ini';
                $rows = DB::table('orders')->whereNull('deleted_at')->whereNull('marketplace')
                    ->where('created_at', '<', $sampai)->whereRaw('total > COALESCE(bayar, 0)')
                    ->select('id', 'created_at as tanggal')->selectRaw("'Order belum lunas' as nama, total - COALESCE(bayar, 0) as nilai")
                    ->orderByDesc('created_at')->limit(100)->get();
            }
        } else {
            abort(404);
        }

        return view('admin.laporan.partials.ringkasan-detail', compact('scope', 'metric', 'rows', 'title', 'subtitle') + ['history' => $history ?? collect()]);
    }

    private function hppLines(Carbon $dari, Carbon $sampai)
    {
        $orders = DB::table('order_details as d')->join('orders as o', 'o.id', '=', 'd.order_id')
            ->join('produks as p', 'p.id', '=', 'd.produk_id')->join('produk_models as m', 'm.id', '=', 'p.produk_model_id')
            ->whereNull('o.deleted_at')->whereNull('d.deleted_at')->where('d.harga', '>', 0)
            ->where(function ($q) { $q->whereNull('d.produksi_id')->orWhere('d.produksi_id', '<>', 4); })
            ->where('o.created_at', '>=', $dari)->where('o.created_at', '<', $sampai)
            ->select('d.produk_id', 'm.nama as model', 'p.nama as varian', 'o.created_at as tanggal', 'o.id as transaksi_id', 'd.jumlah', 'd.harga as harga_satuan', 'd.hpp as hpp_tercatat')
            ->selectRaw("'order' as sumber, d.jumlah * d.harga as jual_total, ABS(COALESCE(NULLIF(d.hpp, 0), p.hpp, 0)) as hpp_satuan, d.jumlah * ABS(COALESCE(NULLIF(d.hpp, 0), p.hpp, 0)) as hpp_total, CASE WHEN d.hpp IS NULL OR d.hpp = 0 THEN 1 ELSE 0 END as fallback");
        $projects = DB::table('project_mp_details as d')->join('project_mps as o', 'o.id', '=', 'd.project_id')
            ->join('produks as p', 'p.id', '=', 'd.produk_id')->join('produk_models as m', 'm.id', '=', 'p.produk_model_id')
            ->where('d.harga', '>', 0)
            ->where(function ($q) { $q->whereNull('d.produksi_id')->orWhere('d.produksi_id', '<>', 4); })
            ->where('o.created_at', '>=', $dari)->where('o.created_at', '<', $sampai)
            ->select('d.produk_id', 'm.nama as model', 'p.nama as varian', 'o.created_at as tanggal', 'o.id as transaksi_id', 'd.jumlah', 'd.harga as harga_satuan', 'd.hpp as hpp_tercatat')
            ->selectRaw("'marketplace' as sumber, d.jumlah * d.harga as jual_total, ABS(COALESCE(NULLIF(d.hpp, 0), p.hpp, 0)) as hpp_satuan, d.jumlah * ABS(COALESCE(NULLIF(d.hpp, 0), p.hpp, 0)) as hpp_total, CASE WHEN d.hpp IS NULL OR d.hpp = 0 THEN 1 ELSE 0 END as fallback");

        return $orders->unionAll($projects);
    }
}
