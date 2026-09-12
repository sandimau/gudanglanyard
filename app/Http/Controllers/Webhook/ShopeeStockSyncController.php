<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\ShopeeStockSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ShopeeStockSyncController extends Controller
{
    public function sync(Request $request, ShopeeStockSyncService $service): JsonResponse
    {
        @set_time_limit(300);

        // Satu produk dapat terhubung ke banyak listing di beberapa toko. Batch
        // kecil menjaga request cron tetap selesai sebelum timeout HTTP eksternal.
        $limit = min(max((int) $request->query('limit', 5), 1), 25);
        $marketplaceId = $request->query('marketplace') ? (int) $request->query('marketplace') : null;

        $lock = Cache::lock('shopee_stock_sync_cron', 300);

        if (! $lock->get()) {
            return response()->json([
                'success' => true,
                'busy' => true,
                'message' => 'Proses sync stok sebelumnya masih berjalan.',
                'synced' => 0,
                'failed' => 0,
                'skipped' => 0,
                'errors' => [],
            ]);
        }

        try {
            $result = $service->processDirtyProducts($limit, $marketplaceId);
        } finally {
            $lock->release();
        }

        return response()->json([
            'success' => $result['success'],
            'busy' => false,
            'synced' => $result['synced'],
            'failed' => $result['failed'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
        ]);
    }
}
