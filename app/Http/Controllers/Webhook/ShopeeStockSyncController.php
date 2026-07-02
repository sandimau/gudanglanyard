<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\ShopeeStockSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopeeStockSyncController extends Controller
{
    public function sync(Request $request, ShopeeStockSyncService $service): JsonResponse
    {
        @set_time_limit(300);

        $limit = (int) $request->query('limit', 200);
        $marketplaceId = $request->query('marketplace') ? (int) $request->query('marketplace') : null;

        $result = $service->processDirtyProducts($limit, $marketplaceId);

        return response()->json([
            'success' => $result['success'],
            'synced' => $result['synced'],
            'failed' => $result['failed'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
        ]);
    }
}
