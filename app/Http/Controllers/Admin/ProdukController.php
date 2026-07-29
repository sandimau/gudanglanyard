<?php

namespace App\Http\Controllers\Admin;

use App\Models\Produk;
use App\Models\Kategori;
use App\Models\ProdukStok;
use App\Models\ProdukLastStok;
use App\Services\StokService;
use App\Models\ProdukModel;
use Illuminate\Http\Request;
use App\Models\BelanjaDetail;
use App\Models\ProdukKategori;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $sortable = [
            'sku' => 'produks.id',
            'gambar' => 'produk_models.gambar',
            'kategori' => 'produk_kategoris.nama',
            'nama' => 'produk_models.nama',
            'varian' => 'produks.nama',
            'harga_beli' => 'belanja_details.harga',
            'harga_jual' => 'produk_models.harga',
            'hpp' => 'produks.hpp',
            'stok' => 'pls.saldo',
        ];

        $sort = $request->get('sort', 'kategori');
        $direction = strtolower($request->get('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (!array_key_exists($sort, $sortable)) {
            $sort = 'kategori';
        }

        $query = Produk::select(
            'produks.id as produk_id',
            'produks.nama as varian',
            'produks.hpp as hpp',
            'produk_models.nama as model',
            'produk_models.harga',
            'produk_models.satuan',
            'produk_models.deskripsi',
            'produk_models.jual',
            'produk_models.beli',
            'produk_models.stok',
            'produk_models.id as model_id',
            'produk_models.gambar',
            'produk_models.kategori_id',
            'produk_models.kontak_id',
            'produk_kategoris.nama as kategori',
            'produk_kategori_utamas.nama as kategori_utama',
            'pls.saldo as lastStok',
            'belanja_details.harga as harga_beli'
        )
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->join('produk_kategoris', 'produk_models.kategori_id', '=', 'produk_kategoris.id')
            ->join('produk_kategori_utamas', 'produk_kategoris.kategori_utama_id', '=', 'produk_kategori_utamas.id')
            ->leftJoin(DB::raw(ProdukLastStok::latestPerProdukSubquery() . ' as pls'), 'produks.id', '=', 'pls.produk_id')
            ->leftJoin('belanja_details', function ($join) {
                $join->on('produks.id', '=', 'belanja_details.produk_id')
                    ->whereRaw('belanja_details.id = (SELECT id FROM belanja_details WHERE produk_id = produks.id ORDER BY id DESC LIMIT 1)');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('produks.id', 'like', '%' . $search . '%')
                    ->orWhere('produk_models.nama', 'like', '%' . $search . '%')
                    ->orWhere('produks.nama', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('kategori_id')) {
            $query->where('produk_kategoris.id', $request->kategori_id);
        }

        if ($sort === 'kategori') {
            $query->orderBy('produk_kategori_utamas.nama', $direction)
                ->orderBy('produk_kategoris.nama', $direction)
                ->orderBy('produk_models.nama', 'asc')
                ->orderBy('produks.nama', 'asc');
        } else {
            $query->orderBy($sortable[$sort], $direction)
                ->orderBy('produks.id', 'asc');
        }

        $produks = $query
            ->paginate(50)
            ->withQueryString();

        $kategoris = ProdukKategori::with('kategoriUtama')
            ->orderBy('kategori_utama_id')
            ->orderBy('nama')
            ->get();

        return view('admin.produks.index', compact('produks', 'sort', 'direction', 'kategoris'));
    }

    public function create(Request $request)
    {
        $produkModel = ProdukModel::find($request->produkModel);
        return view('admin.produks.create', compact('produkModel'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'status' => 'required|in:0,1',
            'produk_model_id' => 'required|exists:produk_models,id'
        ]);

        Produk::create([
            'nama' => $request->nama,
            'status' => $request->status,
            'produk_model_id' => $request->produk_model_id,
        ]);

        $produkModel = ProdukModel::find($request->produk_model_id);
        return redirect()->route('produkModel.show', ['produkModel' => $produkModel->id, 'kategori_id' => $produkModel->kategori_id])->with('success', 'Produk berhasil ditambahkan');
    }

    public function edit(Produk $produk)
    {
        $produkModel = ProdukModel::find($produk->produk_model_id);
        return view('admin.produks.edit', compact('produk', 'produkModel'));
    }

    public function update(Request $request, Produk $produk)
    {
        $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $produkModel = ProdukModel::find($produk->produk_model_id);

        $produk->update($request->all());
        return redirect()->route('produks.index')->with('success', 'Produk berhasil diperbarui');
    }

    public function destroy(Produk $produk, ProdukKategori $kategori)
    {
        $produk->delete();
        return redirect()->route('produkModel.index', ['kategori_id' => $kategori->id])->with('success', 'Produk berhasil dihapus');
    }

    public function stok(Produk $produk, Request $request)
    {
        $saldo = app(StokService::class)->saldoTersedia($produk->id);

        $query = ProdukStok::saldoStok(['saldo' => $saldo])
            ->where('produk_stoks.produk_id', $produk->id);

        if ($request->has('search') && $request->search != '') {
            $query->where('produk_stoks.keterangan', 'like', '%' . $request->search . '%');
        }

        $produkStoks = $query->orderBy('produk_stoks.id', 'desc')->get();
        return view('admin.produkStoks.index', compact('produkStoks','produk'));
    }

    public function aset()
    {
        $asets = DB::table(DB::raw(ProdukLastStok::latestPerProdukSubquery() . ' as t'))
            ->join('produks as p', 'p.id', '=', 't.produk_id')
            ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as k', 'k.id', '=', 'pm.kategori_id')
            ->join('produk_kategori_utamas as ku', 'ku.id', '=', 'k.kategori_utama_id')
            ->select(
                'k.id as kategori_id',
                'ku.nama as namaKategoriUtama',
                'k.nama as namaKategori',
                DB::raw('SUM(t.saldo * pm.harga) as nilai_aset')
            )
            ->groupBy('k.id', 'ku.nama', 'k.nama')
            ->orderBy('ku.nama')
            ->orderBy('k.nama')
            ->get();

        return view('admin.produks.aset', compact('asets'));
    }

    public function asetDetail(Kategori $kategori)
    {
        $asets = DB::table(DB::raw(ProdukLastStok::latestPerProdukSubquery() . ' as t'))
            ->join('produks as p', 'p.id', '=', 't.produk_id')
            ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as k', 'k.id', '=', 'pm.kategori_id')
            ->where('pm.kategori_id', $kategori->id)
            ->select(
                DB::raw("CONCAT(k.nama, ' - ', pm.nama) as namaProduk"),
                'p.nama as varian',
                't.saldo as stok',
                'pm.harga',
                DB::raw('t.saldo * pm.harga as nilai_aset')
            )
            ->orderBy('p.nama')
            ->get();

        return view('admin.produks.asetDetail', compact('asets', 'kategori'));
    }

    public function omzet(Request $request)
    {
        $selectedYear = $request->input('year', date('Y'));

        $years = DB::table('orders')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->unionAll(DB::table('project_mps')->selectRaw('DISTINCT YEAR(created_at) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->unique()
            ->values();

        $asets = DB::table(DB::raw(ProdukLastStok::latestPerProdukSubquery() . ' as t'))
            ->join('produks as p', 'p.id', '=', 't.produk_id')
            ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as k', 'k.id', '=', 'pm.kategori_id')
            ->join('produk_kategori_utamas as ku', 'ku.id', '=', 'k.kategori_utama_id')
            ->select(
                'k.id as kategori_id',
                'ku.nama as namaKategoriUtama',
                'k.nama as namaKategori',
                DB::raw('SUM(t.saldo * pm.harga) as nilai_aset')
            )
            ->groupBy('k.id', 'ku.nama', 'k.nama')
            ->orderBy('ku.nama')
            ->orderBy('k.nama')
            ->get();

        $categories = DB::table('produk_kategoris as k')
            ->join('produk_kategori_utamas as ku', 'ku.id', '=', 'k.kategori_utama_id')
            ->select('k.id as kategori_id', 'ku.nama as namaKategoriUtama', 'k.nama as namaKategori')
            ->where('ku.jual', 1)
            ->orderBy('ku.nama')
            ->orderBy('k.nama')
            ->get();

        $batalProduksiId = DB::table('produksis')->where('nama', 'batal')->first()->id ?? null;

        // Gunakan window function untuk menghitung proporsi omzet per kategori per bulan
        // dalam satu query (mengganti N+1 queries sebelumnya)
        $orderBindings = [$selectedYear];
        $orderBatalSql = '';
        if ($batalProduksiId) {
            $orderBatalSql = 'AND od.produksi_id != ?';
            $orderBindings[] = $batalProduksiId;
        }

        $orderOmzetRows = DB::select("
            SELECT kategori_id, namaKategoriUtama, namaKategori, bulan,
                   SUM(omzet_kontribusi) as omzet
            FROM (
                SELECT
                    k.id               AS kategori_id,
                    ku.nama            AS namaKategoriUtama,
                    k.nama             AS namaKategori,
                    MONTH(o.created_at) AS bulan,
                    COALESCE(
                        (od.jumlah * od.harga)
                        / NULLIF(SUM(od.jumlah * od.harga) OVER (PARTITION BY o.id), 0)
                        * o.total,
                        0
                    ) AS omzet_kontribusi
                FROM orders o
                JOIN order_details od ON od.order_id = o.id AND od.deleted_at IS NULL
                JOIN produks p        ON p.id = od.produk_id
                JOIN produk_models pm ON pm.id = p.produk_model_id
                JOIN produk_kategoris k ON k.id = pm.kategori_id
                JOIN produk_kategori_utamas ku ON ku.id = k.kategori_utama_id
                WHERE YEAR(o.created_at) = ?
                  AND o.total > 0
                  AND o.deleted_at IS NULL
                  $orderBatalSql
            ) AS subq
            GROUP BY kategori_id, namaKategoriUtama, namaKategori, bulan
        ", $orderBindings);

        $mpBindings = [$selectedYear];
        $mpBatalSql = '';
        if ($batalProduksiId) {
            $mpBatalSql = 'AND od.produksi_id != ?';
            $mpBindings[] = $batalProduksiId;
        }

        $mpOmzetRows = DB::select("
            SELECT kategori_id, namaKategoriUtama, namaKategori, bulan,
                   SUM(omzet_kontribusi) as omzet
            FROM (
                SELECT
                    k.id               AS kategori_id,
                    ku.nama            AS namaKategoriUtama,
                    k.nama             AS namaKategori,
                    MONTH(o.created_at) AS bulan,
                    COALESCE(
                        (od.jumlah * od.harga)
                        / NULLIF(SUM(od.jumlah * od.harga) OVER (PARTITION BY o.id), 0)
                        * o.total,
                        0
                    ) AS omzet_kontribusi
                FROM project_mps o
                JOIN project_mp_details od ON od.project_id = o.id
                JOIN produks p        ON p.id = od.produk_id
                JOIN produk_models pm ON pm.id = p.produk_model_id
                JOIN produk_kategoris k ON k.id = pm.kategori_id
                JOIN produk_kategori_utamas ku ON ku.id = k.kategori_utama_id
                WHERE YEAR(o.created_at) = ?
                  AND o.total > 0
                  AND (o.retur != 1 OR o.retur IS NULL)
                  $mpBatalSql
            ) AS subq
            GROUP BY kategori_id, namaKategoriUtama, namaKategori, bulan
        ", $mpBindings);

        // Gabungkan hasil kedua sumber ke dalam satu array
        $omzetDataArray = [];
        foreach (array_merge($orderOmzetRows, $mpOmzetRows) as $row) {
            $key = $row->kategori_id . '_' . $row->bulan;
            if (!isset($omzetDataArray[$key])) {
                $omzetDataArray[$key] = (object)[
                    'kategori_id'       => $row->kategori_id,
                    'namaKategoriUtama' => $row->namaKategoriUtama,
                    'namaKategori'      => $row->namaKategori,
                    'bulan'             => $row->bulan,
                    'omzet'             => 0,
                ];
            }
            $omzetDataArray[$key]->omzet += $row->omzet;
        }

        $omzetData = collect(array_values($omzetDataArray));

        $omzet = collect();
        foreach ($categories as $category) {
            for ($month = 1; $month <= 12; $month++) {
                $monthlyData = $omzetData
                    ->where('kategori_id', $category->kategori_id)
                    ->where('bulan', $month)
                    ->first();

                $omzet->push((object)[
                    'kategori_id'       => $category->kategori_id,
                    'namaKategoriUtama' => $category->namaKategoriUtama,
                    'namaKategori'      => $category->namaKategori,
                    'bulan'             => $month,
                    'tahun'             => $selectedYear,
                    'omzet'             => $monthlyData ? $monthlyData->omzet : 0,
                ]);
            }
        }

        return view('admin.produks.omzet', compact('omzet', 'asets', 'years', 'selectedYear'));
    }

    public function omzetDetail(Kategori $kategori, Request $request)
    {
        // Get selected year and month, default to current if not specified
        $selectedYear = $request->input('year', date('Y'));
        $selectedMonth = $request->input('month', date('m'));

        // Get produksi batal ID for exclusion
        $batalProduksiId = DB::table('produksis')->where('nama', 'batal')->first()->id ?? null;

        // Get all available years for the dropdown
        $years = DB::table('orders')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->unionAll(DB::table('project_mps')->selectRaw('DISTINCT YEAR(created_at) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->unique()
            ->values();

        // Get products with their daily sales for the selected month
        $products = DB::table('produks as p')
            ->join('produk_models as pm', 'pm.id', '=', 'p.produk_model_id')
            ->leftJoin(
                DB::raw(ProdukLastStok::latestPerProdukSubquery() . ' as pls'),
                'pls.produk_id', '=', 'p.id'
            )
            ->where('pm.kategori_id', $kategori->id)
            ->select(
                'p.id',
                DB::raw("pm.nama as nama_produk"),
                'p.nama as varian',
                DB::raw('MAX(pls.saldo) as stok')
            )
            ->groupBy('p.id', 'pm.nama', 'p.nama')
            ->get();

        // Calculate days in selected month for average calculation
        $currentYear = date('Y');
        $currentMonth = date('n');
        $daysForAverage = ($selectedYear == $currentYear && $selectedMonth == $currentMonth)
            ? date('j')
            : cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);

        $productIds = $products->pluck('id')->toArray();

        // Bulk query: penjualan harian per produk dari orders (1 query mengganti N queries)
        $orderDailyQuery = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->whereIn('od.produk_id', $productIds)
            ->whereYear('o.created_at', $selectedYear)
            ->whereMonth('o.created_at', $selectedMonth)
            ->whereRaw('o.total > 0')
            ->whereNull('o.deleted_at')
            ->whereNull('od.deleted_at');
        if ($batalProduksiId) {
            $orderDailyQuery->where('od.produksi_id', '!=', $batalProduksiId);
        }
        $orderDailyAll = $orderDailyQuery
            ->select(
                'od.produk_id',
                DB::raw('DAY(o.created_at) as day'),
                DB::raw('SUM(od.jumlah) as total_sales'),
                DB::raw('COUNT(DISTINCT o.id) as order_count')
            )
            ->groupBy('od.produk_id', DB::raw('DAY(o.created_at)'))
            ->get()
            ->groupBy('produk_id');

        // Bulk query: penjualan harian per produk dari project_mps (1 query mengganti N queries)
        $mpDailyQuery = DB::table('project_mp_details as od')
            ->join('project_mps as o', 'o.id', '=', 'od.project_id')
            ->whereIn('od.produk_id', $productIds)
            ->whereYear('o.created_at', $selectedYear)
            ->whereMonth('o.created_at', $selectedMonth)
            ->whereRaw('o.total > 0')
            ->where(fn($q) => $q->where('o.retur', '!=', 1)->orWhereNull('o.retur'));
        if ($batalProduksiId) {
            $mpDailyQuery->where('od.produksi_id', '!=', $batalProduksiId);
        }
        $mpDailyAll = $mpDailyQuery
            ->select(
                'od.produk_id',
                DB::raw('DAY(o.created_at) as day'),
                DB::raw('SUM(od.jumlah) as total_sales'),
                DB::raw('COUNT(DISTINCT o.id) as order_count')
            )
            ->groupBy('od.produk_id', DB::raw('DAY(o.created_at)'))
            ->get()
            ->groupBy('produk_id');

        // Proses di PHP — tidak ada query tambahan di dalam loop ini
        foreach ($products as $product) {
            $orderByDay = ($orderDailyAll->get($product->id) ?? collect())->keyBy('day');
            $mpByDay    = ($mpDailyAll->get($product->id) ?? collect())->keyBy('day');

            $totalSales = 0;
            $totalOrderCount = 0;
            $product->daily_sales = [];
            $product->daily_order_count = [];

            for ($day = 1; $day <= 31; $day++) {
                $fromOrder = $orderByDay->get($day);
                $fromMp    = $mpByDay->get($day);
                $daySales  = ($fromOrder ? $fromOrder->total_sales : 0) + ($fromMp ? $fromMp->total_sales : 0);
                $dayOrders = ($fromOrder ? $fromOrder->order_count : 0) + ($fromMp ? $fromMp->order_count : 0);
                $product->daily_sales[$day]       = $daySales;
                $product->daily_order_count[$day] = $dayOrders;
                $totalSales      += $daySales;
                $totalOrderCount += $dayOrders;
            }

            $product->rata_penjualan      = $daysForAverage > 0 ? round($totalSales / $daysForAverage, 0) : 0;
            $product->rata_order_per_hari = $daysForAverage > 0 ? round($totalOrderCount / $daysForAverage, 2) : 0;
        }

        return view('admin.produks.omzetDetail', compact('products', 'kategori', 'years', 'selectedYear', 'selectedMonth'));
    }

    public function belanja(Produk $produk)
    {
        $belanjas = BelanjaDetail::where('produk_id', $produk->id)->orderBy('id', 'desc')->limit(30)->get();
        return view('admin.produks.belanja', compact('belanjas', 'produk'));
    }
}
