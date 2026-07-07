<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Produk;
use App\Models\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [];

        // === OMZET OFFLINE PEKANAN (7 hari terakhir) ===
        $data['omzetOffline'] = $this->getOmzetOfflinePekanan();

        // === OMZET ONLINE PEKANAN (7 hari terakhir) ===
        $data['omzetOnline'] = $this->getOmzetOnlinePekanan();

        // === ORDER TERBESAR OFFLINE PEKANAN ===
        $data['orderTerbesarOffline'] = Order::select('id', 'total', 'created_at', 'kontak_id')
            ->with('kontak:id,nama')
            ->whereNull('marketplace')
            ->where('total', '>', 0)
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        // === PENJUALAN TERBAIK PEKANAN ===
        $data['produkTerlaris'] = $this->getProdukTerlarisPekanan();

        // === ORDER TERBESAR HARI INI ===
        $data['orderTerbesarHariIni'] = $this->getOrderTerbesarHariIni();

        // === Marketplace List untuk chart ===
        $data['marketplaces'] = Marketplace::pluck('nama', 'id');
        return view('admin.dashboard.index', $data);
    }

    /**
     * Ambil omzet offline per hari (7 hari terakhir)
     */
    private function getOmzetOfflinePekanan()
    {
        $results = DB::select("
            SELECT
                DATE(created_at) as date,
                SUM(total) as total_omzet
            FROM orders
            WHERE marketplace IS NULL
            AND deleted_at IS NULL
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)
        ");

        // Format data untuk chart
        $dateRange = collect(range(0, 6))->map(function ($day) {
            return now()->subDays(6 - $day)->format('Y-m-d');
        });

        $data = collect();
        foreach ($dateRange as $date) {
            $found = collect($results)->firstWhere('date', $date);
            $data[$date] = (object)[
                'date' => $date,
                'offline' => ($found && $found->total_omzet > 0) ? $found->total_omzet : 0
            ];
        }

        return $data;
    }

    /**
     * Ambil omzet online per marketplace per hari (7 hari terakhir)
     * Gabungkan project_mps (API Shopee) + orders (upload CSV / marketplace lain)
     */
    private function getOmzetOnlinePekanan()
    {
        $resultsProjectMp = DB::select("
            SELECT
                m.id as marketplace_id,
                m.nama as marketplace_nama,
                DATE(o.created_at) as date,
                COALESCE(SUM(o.total), 0) as total_omzet
            FROM marketplaces m
            LEFT JOIN project_mps o ON m.id = o.marketplace_id
                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY m.id, m.nama, DATE(o.created_at)
            ORDER BY m.id, DATE(o.created_at)
        ");

        $resultsOrders = DB::select("
            SELECT
                m.id as marketplace_id,
                m.nama as marketplace_nama,
                DATE(ord.created_at) as date,
                COALESCE(SUM(ord.total), 0) as total_omzet
            FROM marketplaces m
            LEFT JOIN orders ord ON m.kontak_id = ord.kontak_id
                AND ord.marketplace = 1
                AND ord.deleted_at IS NULL
                AND ord.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY m.id, m.nama, DATE(ord.created_at)
            ORDER BY m.id, DATE(ord.created_at)
        ");

        $dateRange = collect(range(0, 6))->map(function ($day) {
            return now()->subDays(6 - $day)->format('Y-m-d');
        });

        $data = collect();
        foreach ($dateRange as $date) {
            $data[$date] = (object) ['date' => $date];
        }

        foreach (array_merge($resultsProjectMp, $resultsOrders) as $result) {
            if ($result->date && isset($data[$result->date])) {
                $columnName = 'mp_' . $result->marketplace_id;
                if (!isset($data[$result->date]->$columnName)) {
                    $data[$result->date]->$columnName = 0;
                }
                $data[$result->date]->$columnName += $result->total_omzet;
            }
        }

        return $data;
    }

    /**
     * Ambil produk terlaris pekanan (project_mps + orders marketplace)
     */
    private function getProdukTerlarisPekanan()
    {
        $results = DB::select("
            SELECT
                produk_id,
                nama_produk,
                model_nama,
                kategori_nama,
                SUM(total) as total,
                SUM(omzet) as omzet
            FROM (
                SELECT
                    p.id as produk_id,
                    p.nama as nama_produk,
                    pm.nama as model_nama,
                    pk.nama as kategori_nama,
                    pmd.jumlah as total,
                    pmd.jumlah * pmd.harga as omzet
                FROM project_mp_details pmd
                INNER JOIN project_mps o ON pmd.project_id = o.id
                INNER JOIN produks p ON pmd.produk_id = p.id
                INNER JOIN produk_models pm ON p.produk_model_id = pm.id
                INNER JOIN produk_kategoris pk ON pm.kategori_id = pk.id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

                UNION ALL

                SELECT
                    p.id as produk_id,
                    p.nama as nama_produk,
                    pm.nama as model_nama,
                    pk.nama as kategori_nama,
                    od.jumlah as total,
                    od.jumlah * od.harga as omzet
                FROM order_details od
                INNER JOIN orders o ON od.order_id = o.id
                INNER JOIN produks p ON od.produk_id = p.id
                INNER JOIN produk_models pm ON p.produk_model_id = pm.id
                INNER JOIN produk_kategoris pk ON pm.kategori_id = pk.id
                WHERE o.marketplace = 1
                AND o.deleted_at IS NULL
                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ) combined
            GROUP BY produk_id, nama_produk, model_nama, kategori_nama
            ORDER BY omzet DESC
            LIMIT 10
        ");

        return collect($results)->map(function ($item) {
            $nama = ($item->model_nama ?? '') . (!empty($item->nama_produk) ? ' (' . $item->nama_produk . ')' : '');

            return [
                'nama_produk' => $nama,
                'total' => (int)$item->total,
                'omzet' => (int)($item->omzet ?? 0)
            ];
        });
    }

    /**
     * Ambil order terbesar hari ini (project_mps + orders marketplace)
     */
    private function getOrderTerbesarHariIni()
    {
        $results = DB::select("
            SELECT
                produk_id,
                nama_produk,
                model_nama,
                kategori_nama,
                SUM(total) as total,
                SUM(omzet) as omzet
            FROM (
                SELECT
                    p.id as produk_id,
                    p.nama as nama_produk,
                    pm.nama as model_nama,
                    pk.nama as kategori_nama,
                    pmd.jumlah as total,
                    pmd.jumlah * pmd.harga as omzet
                FROM project_mp_details pmd
                INNER JOIN project_mps o ON pmd.project_id = o.id
                INNER JOIN produks p ON pmd.produk_id = p.id
                INNER JOIN produk_models pm ON p.produk_model_id = pm.id
                INNER JOIN produk_kategoris pk ON pm.kategori_id = pk.id
                WHERE DATE(o.created_at) = CURDATE()

                UNION ALL

                SELECT
                    p.id as produk_id,
                    p.nama as nama_produk,
                    pm.nama as model_nama,
                    pk.nama as kategori_nama,
                    od.jumlah as total,
                    od.jumlah * od.harga as omzet
                FROM order_details od
                INNER JOIN orders o ON od.order_id = o.id
                INNER JOIN produks p ON od.produk_id = p.id
                INNER JOIN produk_models pm ON p.produk_model_id = pm.id
                INNER JOIN produk_kategoris pk ON pm.kategori_id = pk.id
                WHERE o.marketplace = 1
                AND o.deleted_at IS NULL
                AND DATE(o.created_at) = CURDATE()
            ) combined
            GROUP BY produk_id, nama_produk, model_nama, kategori_nama
            ORDER BY omzet DESC
            LIMIT 10
        ");

        return collect($results)->map(function ($item) {
            $nama = ($item->model_nama ?? '') . (!empty($item->nama_produk) ? ' (' . $item->nama_produk . ')' : '');

            return [
                'nama_produk' => $nama,
                'total' => (int)$item->total,
                'omzet' => (int)($item->omzet ?? 0)
            ];
        });
    }
}

