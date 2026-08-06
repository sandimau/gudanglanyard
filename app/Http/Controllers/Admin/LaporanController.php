<?php

namespace App\Http\Controllers\Admin;

use DateTime;
use App\Models\Order;
use App\Models\Belanja;
use App\Models\Hutang;
use App\Models\Produk;
use App\Models\ProjectMp;
use App\Models\Tunjangan;
use App\Models\AkunDetail;
use App\Models\Penggajian;
use App\Models\ProdukStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use SebastianBergmann\CodeCoverage\Report\Xml\Project;

class LaporanController extends Controller
{
    public function neraca()
    {
        $kas = AkunDetail::TotalKas();
        $modal = AkunDetail::modal();

        $piutang = Hutang::with('details')->where('jenis', '=', 'piutang')->get();
        $hutang = Hutang::with('details')->whereIn('jenis', ['hutang', 'belanja'])->get();

        $total_piutang = 0;
        $total_hutang = 0;
        $total_order = 0;
        foreach ($piutang as $item) {
            $total_piutang += $item->sisa;
        }

        foreach ($hutang as $item) {
            $total_hutang += $item->sisa;
        }

        $order = Order::whereNull('marketplace')->get();
        $total_order = 0;
        foreach ($order as $item) {
            $total_order += $item->kekurangan;
        }

        $orderMP = Order::whereNotNull('marketplace')
            ->where('bayar', '=', 0)
            ->whereHas('orderDetail', function ($query) {
                $query->where('produksi_id', '<>', 4);
            })
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->get();
        $total_orderMP = 0;
        foreach ($orderMP as $item) {
            $total_orderMP += $item->kekurangan;
        }

        $produk = Produk::all();

        $stok = 0;
        foreach ($produk as $item) {
            $stok += ProdukStok::lastStok($item->id) * $item->produkModel->harga;
        }

        return view('admin.laporan.neraca', compact('kas', 'modal', 'total_piutang', 'total_hutang', 'stok', 'total_order', 'total_orderMP'));
    }

    public function labarugi()
    {
        $bulan = request('bulan') ?? date('Y-m');
        $pilihan_parts = explode('-', $bulan);
        $thn = $pilihan_parts[0];
        $bln = $pilihan_parts[1];

        // Omzet dari Order (total sudah include ongkir-diskon, exclude produksi batal)
        $total_omzet = Order::whereYear('created_at', $thn)
            ->whereMonth('created_at', $bln)
            ->sum('total');

        // Omzet dari ProjectMP (total sudah include ongkir-diskon, exclude produksi batal)
        $total_omzetMp = ProjectMp::whereYear('created_at', $thn)
            ->whereMonth('created_at', $bln)
            ->sum('total');

        // HPP tetap dari order_details (produksi_id <> 4)
        $total_hpp = DB::table('order_details')
            ->selectRaw('sum(ABS(COALESCE(NULLIF(order_details.hpp, 0), produks.hpp)) * order_details.jumlah) as total_hpp')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('produks', 'order_details.produk_id', '=', 'produks.id')
            ->where('order_details.harga', '>', 0)
            ->where(function ($q) {
                $q->where('order_details.produksi_id', '<>', 4)
                  ->orWhereNull('order_details.produksi_id');
            })
            ->whereYear('orders.created_at', $thn)
            ->whereMonth('orders.created_at', $bln)
            ->value('total_hpp') ?? 0;

        // HPP tetap dari project_mp_details (produksi_id <> 4)
        $total_hppMp = DB::table('project_mp_details')
            ->selectRaw('sum(ABS(COALESCE(NULLIF(project_mp_details.hpp, 0), produks.hpp)) * project_mp_details.jumlah) as total_hpp')
            ->join('project_mps', 'project_mp_details.project_id', '=', 'project_mps.id')
            ->join('produks', 'project_mp_details.produk_id', '=', 'produks.id')
            ->where('project_mp_details.harga', '>', 0)
            ->where(function ($q) {
                $q->where('project_mp_details.produksi_id', '<>', 4)
                  ->orWhereNull('project_mp_details.produksi_id');
            })
            ->whereYear('project_mps.created_at', $thn)
            ->whereMonth('project_mps.created_at', $bln)
            ->value('total_hpp') ?? 0;

        $opname = ProdukStok::selectRaw('sum(hpp * COALESCE(tambah,0) - hpp * COALESCE(kurang,0)) as total_opname')
            ->where('kode', 'opn')
            ->whereYear('created_at', $thn)
            ->whereMonth('created_at', $bln)
            ->first();

        $opname = abs( $opname->total_opname );

        $beban = DB::table('produks')
            ->selectRaw('sum(belanja_details.harga*jumlah) as total,
            produk_kategoris.nama as kategori,kategori_id')
            ->join('produk_models', 'produk_model_id', '=', 'produk_models.id')
            ->join('belanja_details', 'produk_id', '=', 'produks.id')
            ->join('belanjas', 'belanja_details.belanja_id', '=', 'belanjas.id')
            ->join('produk_kategoris', 'produk_kategoris.id', '=', 'kategori_id')
            ->join('produk_kategori_utamas', 'produk_kategori_utamas.id', '=', 'kategori_utama_id')
            ->whereYear('belanjas.created_at', $thn)
            ->whereMonth('belanjas.created_at', $bln)
            ->whereNull('produk_models.stok')
            ->first()->total;

        $potongan = Order::select('id', 'total', 'bayar', DB::raw('(total - bayar) AS sisa_pembayaran'))
            ->whereRaw('(total - bayar) > 0')
            ->whereNotNull('marketplace')
            ->where('bayar', '>', 0)
            ->whereYear('created_at', $thn)
            ->whereMonth('created_at', $bln)
            ->get();

        $potonganMp = ProjectMp::select('id', 'total', 'bersih', DB::raw('(total - bersih) AS sisa_pembayaran'))
            ->whereRaw('(total - bersih) > 0')
            ->where('bersih', '>', 0)
            ->whereYear('created_at', $thn)
            ->whereMonth('created_at', $bln)
            ->get();

        $total_potonganMP = 0;
        $total_potonganMP = $potongan->sum('sisa_pembayaran') + $potonganMp->sum('sisa_pembayaran');

        $gaji = Penggajian::selectRaw('sum(total+kasbon) as total_gaji')
            ->whereYear('created_at', $thn)
            ->whereMonth('created_at', $bln)
            ->first()->total_gaji;

        $tunjangan = Tunjangan::selectRaw('sum(jumlah) as total_tunjangan')
            ->whereYear('created_at', $thn)
            ->whereMonth('created_at', $bln)
            ->first()->total_tunjangan;

        $omzet = $total_omzet + $total_omzetMp;
        $hpp = $total_hpp + $total_hppMp;

        $bulan = [];
        $tahun_skr = date('Y');
        $bulan_skr = date('n');

        // Tambahkan bulan-bulan dari 2 tahun sebelumnya, urutkan paling awal di atas
        $bulan_tmp = [];
        for ($tahun = $tahun_skr - 1; $tahun <= $tahun_skr; $tahun++) {
            $bulan_akhir = ($tahun == $tahun_skr) ? $bulan_skr : 12;
            for ($i = 1; $i <= $bulan_akhir; $i++) {
                $bulan_tmp[$tahun . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)] = date('F', mktime(0, 0, 0, $i, 1)) . ' ' . $tahun;
            }
        }
        // Reverse supaya bulan paling awal ada di atas
        $bulan = array_reverse($bulan_tmp, true);

        return view('admin.laporan.labarugi', compact('omzet', 'hpp', 'opname', 'beban', 'gaji', 'tunjangan', 'bulan', 'total_potonganMP'));
    }

    public function hutangBelanja(Request $request)
    {
        $bulanParam = $request->bulan ?? date('Y-m');
        $pilihan_parts = explode('-', $bulanParam);
        $thn = $pilihan_parts[0];
        $bln = $pilihan_parts[1];

        $belanjas = Belanja::with('kontak')
            ->whereYear('tanggal_beli', $thn)
            ->whereMonth('tanggal_beli', $bln)
            ->orderByDesc('tanggal_beli')
            ->get();

        $totalBelanja = $belanjas->sum('total');
        $totalHutang = $belanjas->sum('sisa_hutang');
        $totalDibayar = $totalBelanja - $totalHutang;

        $tahun_skr = date('Y');
        $bulan_skr = date('n');

        $bulan_tmp = [];
        for ($tahun = $tahun_skr - 1; $tahun <= $tahun_skr; $tahun++) {
            $bulan_akhir = ($tahun == $tahun_skr) ? $bulan_skr : 12;
            for ($i = 1; $i <= $bulan_akhir; $i++) {
                $bulan_tmp[$tahun . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)] = date('F', mktime(0, 0, 0, $i, 1)) . ' ' . $tahun;
            }
        }
        $bulan = array_reverse($bulan_tmp, true);

        return view('admin.laporan.hutangbelanja', compact('belanjas', 'totalBelanja', 'totalDibayar', 'totalHutang', 'bulan', 'bulanParam'));
    }

    public function labaKotor(Request $request)
    {
        $bulan = $request->bulan ?? date('Y-m');
        $pilihan_parts = explode('-', $bulan);
        $thn = $pilihan_parts[0];
        $bln = $pilihan_parts[1];
        $view_type = $request->view_type ?? 'kategori';

        if ($view_type == 'kategori') {
            // Get data per kategori - gabung Order (order_details) + ProjectMp (project_mp_details) seperti labarugi
            $orderQuery = DB::table('order_details as od')
                ->join('orders as o', 'o.id', '=', 'od.order_id')
                ->join('produks as p', 'p.id', '=', 'od.produk_id')
                ->join('produk_models as pmo', 'pmo.id', '=', 'p.produk_model_id')
                ->join('produk_kategoris as pk', 'pk.id', '=', 'pmo.kategori_id')
                ->join('produk_kategori_utamas as pku', 'pku.id', '=', 'pk.kategori_utama_id')
                ->where('od.harga', '>', 0)
                ->where('od.produksi_id', '<>', 4)
                ->whereYear('o.created_at', $thn)
                ->whereMonth('o.created_at', $bln)
                ->select('pku.nama as kategori_utama', 'pk.nama as kategori', 'pk.id as kategori_id', 'od.jumlah', 'od.harga', 'od.hpp');

            $mpQuery = DB::table('project_mp_details as pmd')
                ->join('project_mps as pmp', 'pmp.id', '=', 'pmd.project_id')
                ->join('produks as p', 'p.id', '=', 'pmd.produk_id')
                ->join('produk_models as pmo', 'pmo.id', '=', 'p.produk_model_id')
                ->join('produk_kategoris as pk', 'pk.id', '=', 'pmo.kategori_id')
                ->join('produk_kategori_utamas as pku', 'pku.id', '=', 'pk.kategori_utama_id')
                ->where('pmd.harga', '>', 0)
                ->where('pmd.produksi_id', '<>', 4)
                ->whereYear('pmp.created_at', $thn)
                ->whereMonth('pmp.created_at', $bln)
                ->select('pku.nama as kategori_utama', 'pk.nama as kategori', 'pk.id as kategori_id', 'pmd.jumlah', 'pmd.harga', 'pmd.hpp');

            $unionQuery = $orderQuery->unionAll($mpQuery);
            $data = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
                ->mergeBindings($unionQuery)
                ->select(
                    'kategori_utama',
                    'kategori',
                    'kategori_id',
                    DB::raw('SUM(jumlah * harga) as omzet'),
                    DB::raw('SUM(hpp * jumlah) as hpp'),
                    DB::raw('COALESCE((
                        SELECT SUM(ps.hpp * COALESCE(ps.tambah,0) - ps.hpp * COALESCE(ps.kurang,0))
                        FROM produk_stoks ps
                        JOIN produks p2 ON p2.id = ps.produk_id
                        JOIN produk_models pm2 ON pm2.id = p2.produk_model_id
                        JOIN produk_kategoris pk2 ON pk2.id = pm2.kategori_id
                        WHERE ps.kode = "opn"
                        AND pk2.id = combined.kategori_id
                        AND YEAR(ps.created_at) = ' . (int) $thn . '
                        AND MONTH(ps.created_at) = ' . (int) $bln . '
                    ), 0) as opname'),
                    DB::raw('(
                        SUM(jumlah * harga) -
                        SUM(hpp * jumlah) +
                        COALESCE((
                            SELECT SUM(ps.hpp * COALESCE(ps.tambah,0) - ps.hpp * COALESCE(ps.kurang,0))
                            FROM produk_stoks ps
                            JOIN produks p2 ON p2.id = ps.produk_id
                            JOIN produk_models pm2 ON pm2.id = p2.produk_model_id
                            JOIN produk_kategoris pk2 ON pk2.id = pm2.kategori_id
                            WHERE ps.kode = "opn"
                            AND pk2.id = combined.kategori_id
                            AND YEAR(ps.created_at) = ' . (int) $thn . '
                            AND MONTH(ps.created_at) = ' . (int) $bln . '
                        ), 0)
                    ) as laba_kotor'),
                    DB::raw('CASE
                        WHEN SUM(jumlah * harga) > 0
                        THEN ((SUM(jumlah * harga) - SUM(hpp * jumlah)) / SUM(jumlah * harga)) * 100
                        ELSE 0
                    END as persen')
                )
                ->groupBy('kategori_utama', 'kategori', 'kategori_id')
                ->orderBy('kategori_utama')
                ->orderBy('kategori')
                ->get();
        } else {
            // Get data per produk - gabung Order + ProjectMp seperti labarugi
            $orderQuery = DB::table('order_details as od')
                ->join('orders as o', 'o.id', '=', 'od.order_id')
                ->join('produks as p', 'p.id', '=', 'od.produk_id')
                ->join('produk_models as pmo', 'pmo.id', '=', 'p.produk_model_id')
                ->join('produk_kategoris as pk', 'pk.id', '=', 'pmo.kategori_id')
                ->join('produk_kategori_utamas as pku', 'pku.id', '=', 'pk.kategori_utama_id')
                ->where('od.harga', '>', 0)
                ->where('od.produksi_id', '<>', 4)
                ->whereYear('o.created_at', $thn)
                ->whereMonth('o.created_at', $bln)
                ->select('pku.nama as kategori_utama', 'pk.nama as kategori', 'p.nama as produk', 'p.id as produk_id', 'od.jumlah', 'od.harga', 'od.hpp');

            $mpQuery = DB::table('project_mp_details as pmd')
                ->join('project_mps as pmp', 'pmp.id', '=', 'pmd.project_id')
                ->join('produks as p', 'p.id', '=', 'pmd.produk_id')
                ->join('produk_models as pmo', 'pmo.id', '=', 'p.produk_model_id')
                ->join('produk_kategoris as pk', 'pk.id', '=', 'pmo.kategori_id')
                ->join('produk_kategori_utamas as pku', 'pku.id', '=', 'pk.kategori_utama_id')
                ->where('pmd.harga', '>', 0)
                ->where('pmd.produksi_id', '<>', 4)
                ->whereYear('pmp.created_at', $thn)
                ->whereMonth('pmp.created_at', $bln)
                ->select('pku.nama as kategori_utama', 'pk.nama as kategori', 'p.nama as produk', 'p.id as produk_id', 'pmd.jumlah', 'pmd.harga', 'pmd.hpp');

            $unionQuery = $orderQuery->unionAll($mpQuery);
            $data = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
                ->mergeBindings($unionQuery)
                ->select(
                    'kategori_utama',
                    'kategori',
                    'produk',
                    'produk_id',
                    DB::raw('SUM(jumlah * harga) as omzet'),
                    DB::raw('SUM(hpp * jumlah) as hpp'),
                    DB::raw('COALESCE((
                        SELECT sum(hpp * COALESCE(tambah,0) - hpp * COALESCE(kurang,0))
                        FROM produk_stoks ps
                        WHERE ps.kode = "opn"
                        AND ps.produk_id = combined.produk_id
                        AND YEAR(ps.created_at) = ' . (int) $thn . '
                        AND MONTH(ps.created_at) = ' . (int) $bln . '
                    ), 0) as opname'),
                    DB::raw('(
                        SUM(jumlah * harga) -
                        SUM(hpp * jumlah) +
                        COALESCE((
                            SELECT sum(hpp * COALESCE(tambah,0) - hpp * COALESCE(kurang,0))
                            FROM produk_stoks ps
                            WHERE ps.kode = "opn"
                            AND ps.produk_id = combined.produk_id
                            AND YEAR(ps.created_at) = ' . (int) $thn . '
                            AND MONTH(ps.created_at) = ' . (int) $bln . '
                        ), 0)
                    ) as laba_kotor'),
                    DB::raw('CASE
                        WHEN SUM(jumlah * harga) > 0
                        THEN ((SUM(jumlah * harga) - SUM(hpp * jumlah)) / SUM(jumlah * harga)) * 100
                        ELSE 0
                    END as persen')
                )
                ->groupBy('kategori_utama', 'kategori', 'produk', 'produk_id')
                ->orderBy('kategori_utama')
                ->orderBy('kategori')
                ->orderBy('produk')
                ->get();
        }

        // Samakan total omzet dengan labarugi: alokasikan (ongkir-diskon) proporsional
        $total_omzet_labarugi = Order::whereYear('created_at', $thn)->whereMonth('created_at', $bln)->sum('total')
            + ProjectMp::whereYear('created_at', $thn)->whereMonth('created_at', $bln)->sum('total');
        $total_item_omzet = $data->sum('omzet');
        $penyesuaian = $total_omzet_labarugi - $total_item_omzet;

        if ($total_item_omzet > 0 && abs($penyesuaian) > 0.01) {
            foreach ($data as $item) {
                $alokasi = $penyesuaian * ($item->omzet / $total_item_omzet);
                $item->omzet = $item->omzet + $alokasi;
                $item->laba_kotor = $item->laba_kotor + $alokasi;
                $item->persen = $item->omzet > 0 ? (($item->laba_kotor / $item->omzet) * 100) : 0;
            }
        }

        // Generate months for dropdown
        $bulanList = [];
        $start_date = now()->startOfYear();
        $end_date = now();

        while ($start_date <= $end_date) {
            $key = $start_date->format('Y-m');
            $bulanList[$key] = $start_date->format('F Y');
            $start_date->addMonth();
        }

        return view('admin.laporan.labakotor', [
            'data' => $data,
            'bulan' => $bulanList,
            'view_type' => $view_type
        ]);
    }

    public function labakotordetail(Request $request)
    {
        $bulan = $request->bulan ?? date('Y-m');
        $pilihan_parts = explode('-', $bulan);
        $thn = $pilihan_parts[0];
        $bln = $pilihan_parts[1];
        $kategori_id = $request->kategori;

        // Validate kategori_id is present
        if (!$kategori_id) {
            return redirect()->route('laporan.labakotor')
                ->with('error', 'Kategori harus dipilih');
        }

        // Base query - gabung Order + ProjectMp seperti labarugi, filter produk by kategori
        $orderQuery = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->join('produks as p', 'p.id', '=', 'od.produk_id')
            ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as pk', 'pk.id', '=', 'pm.kategori_id')
            ->join('produk_kategori_utamas as pku', 'pku.id', '=', 'pk.kategori_utama_id')
            ->where('od.harga', '>', 0)
            ->where('od.produksi_id', '<>', 4)
            ->where('pk.id', $kategori_id)
            ->whereYear('o.created_at', $thn)
            ->whereMonth('o.created_at', $bln)
            ->select(
                DB::raw('CONCAT(COALESCE(pm.nama, ""), " ", COALESCE(p.nama, "")) as produk'),
                'p.id as produk_id',
                'od.jumlah',
                'od.harga',
                'od.hpp'
            );

        $mpQuery = DB::table('project_mp_details as pmd')
            ->join('project_mps as pmp', 'pmp.id', '=', 'pmd.project_id')
            ->join('produks as p', 'p.id', '=', 'pmd.produk_id')
            ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as pk', 'pk.id', '=', 'pm.kategori_id')
            ->join('produk_kategori_utamas as pku', 'pku.id', '=', 'pk.kategori_utama_id')
            ->where('pmd.harga', '>', 0)
            ->where('pmd.produksi_id', '<>', 4)
            ->where('pk.id', $kategori_id)
            ->whereYear('pmp.created_at', $thn)
            ->whereMonth('pmp.created_at', $bln)
            ->select(
                DB::raw('CONCAT(COALESCE(pm.nama, ""), " ", COALESCE(p.nama, "")) as produk'),
                'p.id as produk_id',
                'pmd.jumlah',
                'pmd.harga',
                'pmd.hpp'
            );

        $unionQuery = $orderQuery->unionAll($mpQuery);
        $data = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery)
            ->select(
                'produk',
                'produk_id',
                DB::raw('SUM(jumlah * harga) as omzet'),
                DB::raw('SUM(hpp * jumlah) as hpp'),
                DB::raw('COALESCE((
                    SELECT sum(hpp * COALESCE(tambah,0) - hpp * COALESCE(kurang,0))
                    FROM produk_stoks ps
                    WHERE ps.kode = "opn"
                    AND ps.produk_id = combined.produk_id
                    AND YEAR(ps.created_at) = ' . (int) $thn . '
                    AND MONTH(ps.created_at) = ' . (int) $bln . '
                ), 0) as opname'),
                DB::raw('(
                    SUM(jumlah * harga) -
                    SUM(hpp * jumlah) +
                    COALESCE((
                        SELECT sum(hpp * COALESCE(tambah,0) - hpp * COALESCE(kurang,0))
                        FROM produk_stoks ps
                        WHERE ps.kode = "opn"
                        AND ps.produk_id = combined.produk_id
                        AND YEAR(ps.created_at) = ' . (int) $thn . '
                        AND MONTH(ps.created_at) = ' . (int) $bln . '
                    ), 0)
                ) as laba_kotor'),
                DB::raw('CASE
                    WHEN SUM(jumlah * harga) > 0
                    THEN ((SUM(jumlah * harga) - SUM(hpp * jumlah)) / SUM(jumlah * harga)) * 100
                    ELSE 0
                END as persen')
            )
            ->groupBy('produk', 'produk_id')
            ->orderBy('produk')
            ->get();

        // Samakan total omzet dengan labarugi: alokasikan (ongkir-diskon) proporsional per kategori
        $total_omzet_labarugi = Order::whereYear('created_at', $thn)->whereMonth('created_at', $bln)->sum('total')
            + ProjectMp::whereYear('created_at', $thn)->whereMonth('created_at', $bln)->sum('total');
        $order_item_omzet = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->join('produksis as pr', 'pr.id', '=', 'od.produksi_id')
            ->where('od.harga', '>', 0)
            ->where('pr.id', '<>', 4)
            ->whereYear('o.created_at', $thn)
            ->whereMonth('o.created_at', $bln)
            ->selectRaw('COALESCE(SUM(od.jumlah * od.harga), 0) as tot')
            ->value('tot') ?? 0;
        $mp_item_omzet = DB::table('project_mp_details as pmd')
            ->join('project_mps as pmp', 'pmp.id', '=', 'pmd.project_id')
            ->join('produksis as pr', 'pr.id', '=', 'pmd.produksi_id')
            ->where('pmd.harga', '>', 0)
            ->where('pr.id', '<>', 4)
            ->whereYear('pmp.created_at', $thn)
            ->whereMonth('pmp.created_at', $bln)
            ->selectRaw('COALESCE(SUM(pmd.jumlah * pmd.harga), 0) as tot')
            ->value('tot') ?? 0;
        $total_item_omzet_all = $order_item_omzet + $mp_item_omzet;
        $kategori_item_omzet = $data->sum('omzet');
        $penyesuaian_total = $total_omzet_labarugi - $total_item_omzet_all;
        $penyesuaian_kategori = $total_item_omzet_all > 0 ? $penyesuaian_total * ($kategori_item_omzet / $total_item_omzet_all) : 0;

        if ($kategori_item_omzet > 0 && abs($penyesuaian_kategori) > 0.01) {
            foreach ($data as $item) {
                $alokasi = $penyesuaian_kategori * ($item->omzet / $kategori_item_omzet);
                $item->omzet = $item->omzet + $alokasi;
                $item->laba_kotor = $item->laba_kotor + $alokasi;
                $item->persen = $item->omzet > 0 ? (($item->laba_kotor / $item->omzet) * 100) : 0;
            }
        }

        // Generate months for dropdown
        $bulanList = [];
        $start_date = now()->startOfYear();
        $end_date = now();

        while ($start_date <= $end_date) {
            $key = $start_date->format('Y-m');
            $bulanList[$key] = $start_date->format('F Y');
            $start_date->addMonth();
        }

        return view('admin.laporan.labakotordetail', [
            'data' => $data,
            'bulan' => $bulanList,
            'view_type' => 'produk',
            'selected_kategori' => $kategori_id,
            'selected_bulan' => $bulan
        ]);
    }

    public function tunjangan(Request $request)
    {
        $dari = null;
        $sampai = null;

        if ($request->bulan) {
            $dari = $request->bulan . '-01';
            $sampai = date('Y-m-t', strtotime($request->bulan));
            $tunjangans = Tunjangan::query()
                ->when($dari && $sampai, function ($query) use ($dari, $sampai) {
                    $query->whereBetween('created_at', [$dari, $sampai]);
                })
                ->orderBy('id', 'desc')
                ->paginate(10)
                ->appends(['dari' => $request->dari, 'sampai' => $request->sampai]);
        } else {
            $tunjangans = Tunjangan::orderBy('id', 'desc')->paginate(10);
        }

        return view('admin.laporan.tunjangan', compact('tunjangans', 'dari', 'sampai'));
    }

    public function penggajian(Request $request)
    {
        $dari = null;
        $sampai = null;

        if ($request->bulan) {
            $dari = $request->bulan . '-01';
            $sampai = date('Y-m-t', strtotime($request->bulan));
            $penggajians = Penggajian::query()
                ->when($dari && $sampai, function ($query) use ($dari, $sampai) {
                    $query->whereBetween('created_at', [$dari, $sampai]);
                })
                ->orderBy('id', 'desc')
                ->paginate(10)
                ->appends(['dari' => $request->dari, 'sampai' => $request->sampai]);
        } else {
            $penggajians = Penggajian::orderBy('id', 'desc')->paginate(10);
        }

        return view('admin.laporan.penggajian', compact('penggajians', 'dari', 'sampai'));
    }

    public function operasional(Request $request)
    {
        $bulan = $request->bulan ?? date('Y-m');
        $pilihan_parts = explode('-', $bulan);
        $thn = $pilihan_parts[0];
        $bln = $pilihan_parts[1];
        $view_type = $request->view_type ?? 'kategori';

        if ($view_type == 'kategori') {
            // Get data per kategori
            $data = DB::table('belanja_details as bd')
                ->join('belanjas as b', 'b.id', '=', 'bd.belanja_id')
                ->join('produks as p', 'p.id', '=', 'bd.produk_id')
                ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
                ->join('produk_kategoris as pk', 'pk.id', '=', 'pm.kategori_id')
                ->join('produk_kategori_utamas as pku', 'pku.id', '=', 'pk.kategori_utama_id')
                ->whereYear('b.created_at', $thn)
                ->whereMonth('b.created_at', $bln)
                ->whereNull('pm.stok')
                ->select(
                    'pku.nama as kategori_utama',
                    'pk.nama as kategori',
                    'pk.id as kategori_id',
                    DB::raw('SUM(bd.jumlah * bd.harga) as total_belanja')
                )
                ->groupBy('pku.nama', 'pk.nama', 'pk.id')
                ->orderBy('pku.nama')
                ->orderBy('pk.nama')
                ->get();
        } else {
            // Get data per produk
            $data = DB::table('belanja_details as bd')
                ->join('belanjas as b', 'b.id', '=', 'bd.belanja_id')
                ->join('produks as p', 'p.id', '=', 'bd.produk_id')
                ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
                ->join('produk_kategoris as pk', 'pk.id', '=', 'pm.kategori_id')
                ->join('produk_kategori_utamas as pku', 'pku.id', '=', 'pk.kategori_utama_id')
                ->whereYear('b.created_at', $thn)
                ->whereMonth('b.created_at', $bln)
                ->whereNull('pm.stok')
                ->select(
                    'pku.nama as kategori_utama',
                    'pk.nama as kategori',
                    'p.nama as produk',
                    'p.id as produk_id',
                    DB::raw('SUM(bd.jumlah * bd.harga) as total_belanja')
                )
                ->groupBy('pku.nama', 'pk.nama', 'p.nama', 'p.id')
                ->orderBy('pku.nama')
                ->orderBy('pk.nama')
                ->orderBy('p.nama')
                ->get();
        }

        // Generate months for dropdown
        $bulanList = [];
        $start_date = now()->startOfYear();
        $end_date = now();

        while ($start_date <= $end_date) {
            $key = $start_date->format('Y-m');
            $bulanList[$key] = $start_date->format('F Y');
            $start_date->addMonth();
        }

        return view('admin.laporan.operasional', [
            'data' => $data,
            'bulan' => $bulanList,
            'view_type' => $view_type
        ]);
    }

    public function operasionaldetail(Request $request)
    {
        $bulan = $request->bulan ?? date('Y-m');
        $pilihan_parts = explode('-', $bulan);
        $thn = $pilihan_parts[0];
        $bln = $pilihan_parts[1];
        $kategori_id = $request->kategori;

        // Validate kategori_id is present
        if (!$kategori_id) {
            return redirect()->route('laporan.operasional')
                ->with('error', 'Kategori harus dipilih');
        }

        // Base query starting from products to show all products
        $query = DB::table('produks as p')
            ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as pk', 'pk.id', '=', 'pm.kategori_id')
            ->join('produk_kategori_utamas as pku', 'pku.id', '=', 'pk.kategori_utama_id')
            ->leftJoin('belanja_details as bd', 'bd.produk_id', '=', 'p.id')
            ->leftJoin('belanjas as b', function ($join) use ($thn, $bln) {
                $join->on('b.id', '=', 'bd.belanja_id')
                    ->whereYear('b.created_at', '=', $thn)
                    ->whereMonth('b.created_at', '=', $bln);
            })
            ->where('pk.id', $kategori_id);

        $data = $query->select(
            'pku.nama as kategori_utama',
            'pk.nama as kategori',
            DB::raw('CONCAT(COALESCE(pm.nama, ""), " ", COALESCE(p.nama, "")) as produk'),
            'p.id as produk_id',
            DB::raw('COALESCE(SUM(CASE WHEN b.id IS NOT NULL THEN bd.jumlah * bd.harga ELSE 0 END), 0) as total_belanja')
        )
            ->groupBy('pku.nama', 'pk.nama', 'p.nama', 'p.id', 'pm.nama')
            ->having('total_belanja', '>', 0)
            ->orderBy('pku.nama')
            ->orderBy('pk.nama')
            ->orderBy('p.nama')
            ->get();

        // Generate months for dropdown
        $bulanList = [];
        $start_date = now()->startOfYear();
        $end_date = now();

        while ($start_date <= $end_date) {
            $key = $start_date->format('Y-m');
            $bulanList[$key] = $start_date->format('F Y');
            $start_date->addMonth();
        }

        return view('admin.laporan.operasionaldetail', [
            'data' => $data,
            'bulan' => $bulanList,
            'selected_kategori' => $kategori_id,
            'selected_bulan' => $bulan
        ]);
    }
}
