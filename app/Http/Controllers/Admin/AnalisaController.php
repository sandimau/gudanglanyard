<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Sistem;
use App\Models\Tunjangan;
use App\Models\Penggajian;
use App\Models\ProdukStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AnalisaController extends Controller
{
    public function beban()
    {
        return view('admin.analisa.beban');
    }

    public function getDataBeban(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $data = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            // Tunjangan
            $tunjangan = Tunjangan::whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->sum('jumlah') ?? 0;

            // Beban Operasional (Belanja non-stok)
            $operasionalQuery = DB::table('produks')
                ->selectRaw('sum(belanja_details.harga*jumlah) as total')
                ->join('produk_models', 'produk_model_id', '=', 'produk_models.id')
                ->join('belanja_details', 'produk_id', '=', 'produks.id')
                ->join('belanjas', 'belanja_details.belanja_id', '=', 'belanjas.id')
                ->whereYear('belanjas.created_at', $tahun)
                ->whereMonth('belanjas.created_at', $bulan)
                ->whereNull('produk_models.stok');
            apply_cabang_constraint($operasionalQuery, 'belanjas.cabang_id');
            $operasional = $operasionalQuery->first()->total ?? 0;

            // Penggajian
            $penggajian = Penggajian::whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->sum('total') ?? 0;

            // Pemakaian Stok (produk_stoks dengan kurang)
            $pemakaianStokQuery = ProdukStok::whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->where('keterangan', 'like', '%pakai%')
                ->selectRaw('SUM(COALESCE(hpp, 0) * COALESCE(kurang, 0)) as total');
            apply_cabang_constraint($pemakaianStokQuery, 'produk_stoks.cabang_id');
            $pemakaianStok = $pemakaianStokQuery->value('total') ?? 0;

            $data[] = [
                'bulan' => $bulan,
                'nama_bulan' => $this->getNamaBulan($bulan),
                'operasional' => (float) $operasional,
                'penggajian' => (float) $penggajian,
                'tunjangan' => (float) $tunjangan,
                'pemakaian_stok' => (float) $pemakaianStok,
                'total' => (float) ($operasional + $penggajian + $tunjangan + $pemakaianStok)
            ];
        }

        return response()->json($data);
    }

    private function getNamaBulan($bulan)
    {
        $namaBulan = [
            1 => 'januari', 2 => 'februari', 3 => 'maret', 4 => 'april',
            5 => 'mei', 6 => 'juni', 7 => 'juli', 8 => 'agustus',
            9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'desember'
        ];
        return $namaBulan[$bulan];
    }

    public function operasional()
    {
        return view('admin.analisa.operasional');
    }

    public function getDataOperasional(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));

        // Ambil semua kategori yang ada terlebih dahulu
        $allKategoriQuery = DB::table('produks')
            ->select('produk_kategoris.nama as kategori', 'kategori_id')
            ->join('produk_models', 'produk_model_id', '=', 'produk_models.id')
            ->join('belanja_details', 'produk_id', '=', 'produks.id')
            ->join('belanjas', 'belanja_details.belanja_id', '=', 'belanjas.id')
            ->join('produk_kategoris', 'produk_kategoris.id', '=', 'kategori_id')
            ->where(function($query) {
                $query->where('produk_models.stok', '!=', 1)
                      ->orWhereNull('produk_models.stok');
            });
        apply_cabang_constraint($allKategoriQuery, 'belanjas.cabang_id');
        $allKategori = $allKategoriQuery
            ->groupBy('kategori_id')
            ->orderBy('kategori_id')
            ->get();

        // Ambil data per bulan dan kategori
        $ambil = DB::table('produks')
            ->selectRaw('MONTH(belanjas.created_at) as bulan_num,
            sum(belanja_details.harga*jumlah) as total,
            produk_kategoris.nama as kategori,kategori_id')
            ->join('produk_models', 'produk_model_id', '=', 'produk_models.id')
            ->join('belanja_details', 'produk_id', '=', 'produks.id')
            ->join('belanjas', 'belanja_details.belanja_id', '=', 'belanjas.id')
            ->join('produk_kategoris', 'produk_kategoris.id', '=', 'kategori_id')
            ->whereYear('belanjas.created_at', $tahun)
            ->where(function($query) {
                $query->where('produk_models.stok', '!=', 1)
                      ->orWhereNull('produk_models.stok');
            });
        apply_cabang_constraint($ambil, 'belanjas.cabang_id');
        $ambil = $ambil
            ->groupBy('bulan_num', 'kategori_id')
            ->orderBy('bulan_num', 'asc')
            ->orderBy('kategori_id');

        $data = $ambil->get();

        // Kelompokkan data berdasarkan bulan
        $groupedData = $data->groupBy('bulan_num');

        // Buat daftar bulan
        $bulans = [
            1 => 'januari', 2 => 'februari', 3 => 'maret', 4 => 'april',
            5 => 'mei', 6 => 'juni', 7 => 'juli', 8 => 'agustus',
            9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'desember'
        ];

        // Jika tahun sekarang, batasi sampai bulan ini
        if ($tahun == date('Y')) {
            $bulanSekarang = date('n');
            $bulans = array_slice($bulans, 0, $bulanSekarang, true);
        }

        // Konversi data untuk format chart
        $chartData = [];

        foreach ($bulans as $bulan_key => $nama_bulan) {
            $bulanData = [
                'bulan' => $bulan_key,
                'nama_bulan' => $nama_bulan
            ];

            // Inisialisasi semua kategori dengan nilai 0
            foreach ($allKategori as $kategori) {
                $fieldName = strtolower(str_replace(' ', '_', $kategori->kategori));
                $bulanData[$fieldName] = 0;
            }

            // Isi data yang ada
            if ($groupedData->has($bulan_key)) {
                foreach ($groupedData[$bulan_key] as $item) {
                    $fieldName = strtolower(str_replace(' ', '_', $item->kategori));
                    $bulanData[$fieldName] = (float) $item->total;
                }
            }

            $chartData[] = $bulanData;
        }

        return response()->json($chartData);
    }

    public function stok()
    {
        $sistems = Sistem::where('nama', 'limit_stok')->first();
        return view('admin.analisa.stok');
    }

    public function getDataStok(Request $request)
    {
        try {
            $kategori = $request->input('kategori', 'all');

            // Hitung jumlah hari dari awal bulan, 1 bulan yang lalu, hingga hari ini
            $awalBulan = Carbon::now()->subMonth()->startOfMonth();
            $hariIni = Carbon::now();
            $total_hari = $awalBulan->diffInDays($hariIni) + 1;

            $sistems = Sistem::where('nama', 'limit_stok')->first();

            // Waktu PO (default 30 hari jika tidak ada config)
            $waktu_po = $sistems->isi ?? 30; // Bisa disesuaikan dengan config

            // Ambil data produk dengan stok = 1 dan jual = 1
            $query = DB::table('produks')
                ->selectRaw('
                    COALESCE(produk_kategoris.nama, \'Tanpa Kategori\') as kategori,
                    COALESCE(produk_kategoris.id, 0) as kategori_id,
                    produk_models.nama as produk,
                    produks.nama as varian,
                    produk_models.id as produk_model_id,
                    produks.id as produk_id,
                    produk_models.stok as is_stok
                ')
                ->join('produk_models', 'produk_model_id', '=', 'produk_models.id')
                ->leftJoin('produk_kategoris', 'produk_kategoris.id', '=', 'produk_models.kategori_id')
                ->where('produk_models.jual', '=', 1)
                ->where('produk_models.stok', '=', 1);

            // Ambil data produk yang memenuhi kriteria
            $produks = $query->get();

            if ($kategori != 'all') {
                $produks = $produks->filter(function($item) use ($kategori) {
                    return $item->kategori_id == $kategori;
                });
            }

        $hasil = [];

        foreach ($produks as $produk) {
            // Hitung totalPakai dari produk_stoks (kurang) dalam periode
            $totalPakaiQuery = DB::table('produk_stoks')
                ->where('produk_id', $produk->produk_id)
                ->where('created_at', '>=', $awalBulan)
                ->where('created_at', '<=', $hariIni)
                ->where('kurang', '>', 0);
            apply_cabang_constraint($totalPakaiQuery, 'produk_stoks.cabang_id');
            $totalPakai = $totalPakaiQuery->sum('kurang') ?? 0;

            // Hitung omzet dari order_details dalam periode yang sama
            $omzetQuery = DB::table('order_details')
                ->join('orders', 'order_details.order_id', '=', 'orders.id')
                ->where('order_details.produk_id', $produk->produk_id)
                ->where('orders.created_at', '>=', $awalBulan)
                ->where('orders.created_at', '<=', $hariIni);
            apply_cabang_constraint($omzetQuery, 'orders.cabang_id');
            $omzet = $omzetQuery->sum('order_details.jumlah') ?? 0;

            // Hitung penjualan harian
            $penjualanHarian = $total_hari > 0 ? round((($totalPakai + $omzet) / $total_hari), 2) : 0;

            $stokTotal = ProdukStok::lastStok($produk->produk_id);

            // Hitung stok minimal
            $stokMin = floor($penjualanHarian * $waktu_po * 1.5);

            $kurang = false;
            $lebih = false;
            $stokHtml = $stokTotal;

            if ($penjualanHarian == 0) {
                $status = 'normal';
            } else {
                if ($stokTotal >= $stokMin) {
                    if ($stokTotal > 2 * $stokMin) {
                        $lebih = true;
                        $stokHtml = '<b><font color="blue">' . $stokTotal . '</font></b>';
                    }
                } else {
                    $lebihan = $stokTotal / $penjualanHarian;
                    $pengali_po = 2;
                    $kekurangan = (($pengali_po * $waktu_po) - $lebihan) * $penjualanHarian;
                    if ($kekurangan > 0) {
                        $kurang = true;
                        $stokHtml = '<b><font color="red">' . $stokTotal . '</font></b> <span style="font-size:10px;">-' . ceil($kekurangan) . '</span>';
                    }
                }
            }

            $hasil[] = [
                'kategori' => $produk->kategori,
                'produk' => $produk->produk,
                'varian' => $produk->varian,
                'penjualan_harian' => $penjualanHarian,
                'stok_total' => $stokTotal,
                'stok_minimal' => $stokMin,
                'stok_html' => $stokHtml,
                'kurang' => $kurang,
                'lebih' => $lebih,
            ];
        }

        // Sort berdasarkan kurang=true agar yang paling awal
        usort($hasil, function($a, $b) {
            if ($a['kurang'] && !$b['kurang']) {
                return -1;
            }
            if (!$a['kurang'] && $b['kurang']) {
                return 1;
            }
            return 0;
        });

            return response()->json($hasil);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getKategoriStok()
    {
        try {
            $kategori = DB::table('produk_kategoris')
                ->select('produk_kategoris.id', 'produk_kategoris.nama')
                ->join('produk_models', 'produk_models.kategori_id', '=', 'produk_kategoris.id')
                ->join('produks', 'produks.produk_model_id', '=', 'produk_models.id')
                ->where('produk_models.stok', 1)
                ->groupBy('produk_kategoris.id', 'produk_kategoris.nama')
                ->orderBy('produk_kategoris.nama')
                ->get();

            return response()->json($kategori);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
