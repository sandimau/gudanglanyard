<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\Marketplace;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [];

        $data['omzetOffline'] = $this->getOmzetOfflinePekanan();
        $data['omzetOnline'] = $this->getOmzetOnlinePekanan();

        $data['orderTerbesarOffline'] = Order::select('id', 'total', 'created_at', 'kontak_id')
            ->with('kontak:id,nama')
            ->whereNull('marketplace')
            ->where('total', '>', 0)
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $data['produkTerlaris'] = $this->getProdukTerlarisPekanan();
        $data['orderTerbesarHariIni'] = $this->getOrderTerbesarHariIni();
        $data['marketplaces'] = Marketplace::pluck('nama', 'id');

        return view('admin.dashboard.index', $data);
    }

    private function getOmzetOfflinePekanan()
    {
        $cabangId = cabang_id();
        $results = DB::select("
            SELECT
                DATE(created_at) as date,
                SUM(total) as total_omzet
            FROM orders
            WHERE marketplace IS NULL
            AND deleted_at IS NULL
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND (? IS NULL OR cabang_id = ?)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)
        ", [$cabangId, $cabangId]);

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

    private function getOmzetOnlinePekanan()
    {
        $cabangId = cabang_id();

        $resultsProjectMp = DB::select("
            SELECT
                m.id as marketplace_id,
                m.nama as marketplace_nama,
                DATE(o.created_at) as date,
                COALESCE(SUM(o.total), 0) as total_omzet
            FROM marketplaces m
            LEFT JOIN project_mps o ON m.id = o.marketplace_id
                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND (? IS NULL OR o.cabang_id = ?)
            WHERE (? IS NULL OR m.cabang_id = ?)
            GROUP BY m.id, m.nama, DATE(o.created_at)
            ORDER BY m.id, DATE(o.created_at)
        ", [$cabangId, $cabangId, $cabangId, $cabangId]);

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
                AND (? IS NULL OR ord.cabang_id = ?)
            GROUP BY m.id, m.nama, DATE(ord.created_at)
            ORDER BY m.id, DATE(ord.created_at)
        ", [$cabangId, $cabangId]);

        $dateRange = collect(range(0, 6))->map(function ($day) {
            return now()->subDays(6 - $day)->format('Y-m-d');
        });

        $data = collect();
        foreach ($dateRange as $date) {
            $data[$date] = (object) ['date' => $date];
        }

        // Online omzet dari project_mp belum punya cabang_id — hanya tampilkan orders cabang aktif.
        foreach ($resultsOrders as $result) {
            if ($result->date && isset($data[$result->date])) {
                $columnName = 'mp_' . $result->marketplace_id;
                if (!isset($data[$result->date]->$columnName)) {
                    $data[$result->date]->$columnName = 0;
                }
                $data[$result->date]->$columnName += $result->total_omzet;
            }
        }

        // Tetap gabungkan project_mp agar chart marketplace tidak kosong sepenuhnya.
        foreach ($resultsProjectMp as $result) {
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

    private function getProdukTerlarisPekanan()
    {
        $cabangId = cabang_id();
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
                AND (? IS NULL OR o.cabang_id = ?)

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
                AND (? IS NULL OR o.cabang_id = ?)
            ) combined
            GROUP BY produk_id, nama_produk, model_nama, kategori_nama
            ORDER BY omzet DESC
            LIMIT 10
        ", [$cabangId, $cabangId, $cabangId, $cabangId]);

        return collect($results)->map(function ($item) {
            $nama = ($item->model_nama ?? '') . (!empty($item->nama_produk) ? ' (' . $item->nama_produk . ')' : '');

            return [
                'nama_produk' => $nama,
                'total' => (int)$item->total,
                'omzet' => (int)($item->omzet ?? 0)
            ];
        });
    }

    private function getOrderTerbesarHariIni()
    {
        $cabangId = cabang_id();
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
                AND (? IS NULL OR o.cabang_id = ?)

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
                AND (? IS NULL OR o.cabang_id = ?)
            ) combined
            GROUP BY produk_id, nama_produk, model_nama, kategori_nama
            ORDER BY omzet DESC
            LIMIT 10
        ", [$cabangId, $cabangId, $cabangId, $cabangId]);

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
