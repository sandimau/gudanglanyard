<?php

namespace App\Services;

use App\Http\Controllers\Traits\ShopeeApi;
use App\Models\Marketplace;
use App\Models\MarketplaceLog;
use App\Models\ShopeeStockSync;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShopeeStockSyncService
{
    use ShopeeApi;

    public const UNLIMITED_STOCK = 10000;

    public const MAX_SHOPEE_STOCK = 10000000;

    public function markDirty(int $produk_id): void
    {
        if (! DB::table('produks')->where('id', $produk_id)->exists()) {
            return;
        }

        ShopeeStockSync::updateOrCreate(
            ['produk_id' => $produk_id],
            [
                'dirty_at' => now(),
                'last_error' => null,
                'synced_marketplaces' => null,
            ]
        );
    }

    public function calculateShopeeStock(int $produk_id, int $paket = 1, ?int $cabang_id = null): int
    {
        $paket = max($paket, 1);
        $cabang_id = resolve_cabang_id($cabang_id);

        $produk = DB::table('produks')
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->where('produks.id', $produk_id)
            ->select('produk_models.stok', 'produk_models.stok_min_mp')
            ->first();

        if (! $produk) {
            return 0;
        }

        if ((int) $produk->stok !== 1) {
            return self::UNLIMITED_STOCK;
        }

        $saldo = app(StokService::class)->saldoTersedia($produk_id, $cabang_id);
        $buffer = (int) ($produk->stok_min_mp ?? 0);
        $saldo = max(0, $saldo - $buffer);

        return $this->normalizeShopeeStock((int) floor($saldo / $paket));
    }

    /**
     * @return array{success: bool, synced: int, failed: int, skipped: int, errors: array<int, string>}
     */
    public function processDirtyProducts(int $limit = 5, ?int $marketplaceId = null): array
    {
        $dirtyRows = ShopeeStockSync::whereNotNull('dirty_at')
            ->orderBy('dirty_at')
            ->limit($limit)
            ->get();

        if ($dirtyRows->isEmpty()) {
            return ['success' => true, 'synced' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
        }

        $produkIds = $dirtyRows->pluck('produk_id')->all();
        $grouped = $this->groupProdukIdsByMarketplace($produkIds, $marketplaceId);
        $scheduledProdukIds = $grouped->flatten()->map(fn ($id) => (int) $id)->unique()->all();
        $skipped = 0;

        foreach ($dirtyRows as $row) {
            if (in_array((int) $row->produk_id, $scheduledProdukIds, true)) {
                continue;
            }

            $this->tryClearDirty((int) $row->produk_id);

            if (! ShopeeStockSync::where('produk_id', $row->produk_id)->whereNotNull('dirty_at')->exists()) {
                $skipped++;
            }
        }

        if ($grouped->isEmpty()) {
            return ['success' => true, 'synced' => 0, 'failed' => 0, 'skipped' => $skipped, 'errors' => []];
        }

        $synced = 0;
        $failed = 0;
        $errors = [];

        foreach ($grouped as $mpId => $mpProdukIds) {
            $result = $this->syncMarketplaceListings((int) $mpId, $mpProdukIds);
            $synced += $result['synced'];
            $failed += $result['failed'];
            $skipped += $result['skipped'];
            $errors = array_merge($errors, $result['errors']);
        }

        return [
            'success' => $failed === 0,
            'synced' => $synced,
            'failed' => $failed,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int>  $produkIds
     * @return array{success: bool, synced: int, failed: int, skipped: int, errors: array<int, string>}
     */
    public function syncMarketplaceListings(int $marketplaceId, array $produkIds): array
    {
        $marketplace = Marketplace::withoutGlobalScope('cabang')
            ->where('id', $marketplaceId)
            ->where('marketplace', 'shopee')
            ->whereNotNull('shop_id')
            ->where('shop_id', '!=', 0)
            ->whereNotNull('access_token')
            ->first();

        if (! $marketplace) {
            return [
                'success' => false,
                'synced' => 0,
                'failed' => count($produkIds),
                'skipped' => 0,
                'errors' => [$marketplaceId => 'Marketplace Shopee tidak ditemukan atau belum tersinkron'],
            ];
        }

        return with_cabang($marketplace->cabang_id, function () use ($marketplace, $marketplaceId, $produkIds) {
            return $this->syncMarketplaceListingsInCabang($marketplace, $marketplaceId, $produkIds);
        });
    }

    /**
     * @param  array<int>  $produkIds
     * @return array{success: bool, synced: int, failed: int, skipped: int, errors: array<int, string>}
     */
    private function syncMarketplaceListingsInCabang(Marketplace $marketplace, int $marketplaceId, array $produkIds): array
    {
        if (! $marketplace->auto_sync_stok) {
            return ['success' => true, 'synced' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
        }

        $listings = DB::table('produk_marketplaces as pm')
            ->where('pm.marketplace_id', $marketplaceId)
            ->whereIn('pm.produk_id', $produkIds)
            ->select('pm.*')
            ->get();

        if ($listings->isEmpty()) {
            foreach ($produkIds as $produkId) {
                $this->tryClearDirty((int) $produkId);
            }

            return ['success' => true, 'synced' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
        }

        $synced = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];
        $productStates = [];
        $marketplaceUpdated = false;

        foreach ($listings as $listing) {
            $productStates[(int) $listing->produk_id] = [
                'failed' => false,
                'stock' => $this->calculateShopeeStock((int) $listing->produk_id, 1, (int) $marketplace->cabang_id),
            ];
        }

        foreach ($listings->groupBy('item_id') as $itemId => $itemListings) {
            $stockList = [];
            $entries = [];

            foreach ($itemListings as $listing) {
                $stock = $this->calculateShopeeStock(
                    (int) $listing->produk_id,
                    (int) $listing->paket,
                    (int) $marketplace->cabang_id
                );
                $entry = [
                    'seller_stock' => [
                        ['stock' => $stock],
                    ],
                ];

                if ((int) $listing->model_id > 0) {
                    $entry['model_id'] = (int) $listing->model_id;
                }

                $stockList[] = $entry;
                $entries[] = [
                    'produk_id' => (int) $listing->produk_id,
                    'model_id' => (int) $listing->model_id,
                    'stock' => $stock,
                ];
            }

            $body = [
                'item_id' => (int) $itemId,
                'stock_list' => $stockList,
            ];

            $resp = $this->kirimApiWithRecovery($marketplace, 'product/update_stock', $body);
            $failureReasons = $this->failureReasonsByModel($resp);

            if ($this->isApiSuccess($resp)) {
                foreach ($entries as $entry) {
                    $synced++;
                }
                $marketplaceUpdated = true;
            } else {
                $errorMsg = $this->formatApiError($resp);
                $entryResults = [];
                $hasPermanentFailure = false;
                $hasRetryableFailure = false;

                foreach ($entries as $entry) {
                    $entryError = $this->errorForEntry($entry['model_id'], $failureReasons, $errorMsg);
                    $permanent = $entryError !== null && $this->isPermanentListingError($entryError, $resp);
                    $hasPermanentFailure = $hasPermanentFailure || $permanent;
                    $hasRetryableFailure = $hasRetryableFailure || ($entryError !== null && ! $permanent);
                    $entryResults[] = [$entry, $entryError, $permanent];
                }

                if ($hasPermanentFailure || $hasRetryableFailure) {
                    $this->logError(
                        $marketplace,
                        $hasRetryableFailure ? 'sync stok' : 'sync stok dilewati',
                        $errorMsg,
                        $body
                    );
                }

                foreach ($entryResults as [$entry, $entryError, $permanent]) {
                    if ($entryError === null) {
                        $synced++;
                        $marketplaceUpdated = true;

                        continue;
                    }

                    if ($permanent) {
                        $skipped++;

                        continue;
                    }

                    $produkId = $entry['produk_id'];
                    $productStates[$produkId]['failed'] = true;
                    $failed++;
                    $errors[] = sprintf(
                        '%s / item %s / produk %s: %s',
                        $marketplace->nama,
                        $itemId,
                        $produkId,
                        $entryError
                    );

                    ShopeeStockSync::where('produk_id', $produkId)->update([
                        'last_error' => $entryError,
                        'updated_at' => now(),
                    ]);
                }
            }

            usleep(500000);
        }

        if ($marketplaceUpdated) {
            $marketplace->update(['tglSyncStok' => now()]);
        }

        foreach ($productStates as $produkId => $state) {
            if (! $state['failed']) {
                $this->markMarketplaceSynced((int) $produkId, $marketplaceId, $state['stock']);
            }

            $this->tryClearDirty((int) $produkId);
        }

        return [
            'success' => $failed === 0,
            'synced' => $synced,
            'failed' => $failed,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function tryClearDirty(int $produk_id): void
    {
        $row = ShopeeStockSync::where('produk_id', $produk_id)->first();

        if (! $row || ! $row->needsSync()) {
            return;
        }

        $requiredMarketplaceIds = $this->getActiveMarketplaceIdsForProduk($produk_id);

        if (empty($requiredMarketplaceIds)) {
            $this->clearDirty($produk_id, $this->calculateShopeeStock($produk_id));

            return;
        }

        $synced = $row->synced_marketplaces ?? [];

        foreach ($requiredMarketplaceIds as $mpId) {
            if (! isset($synced[(string) $mpId]) && ! isset($synced[$mpId])) {
                return;
            }
        }

        $stock = $this->calculateShopeeStock($produk_id);
        $this->clearDirty($produk_id, $stock);
    }

    public function markMarketplaceSynced(int $produk_id, int $marketplaceId, int $stock): void
    {
        $row = ShopeeStockSync::firstOrCreate(['produk_id' => $produk_id]);
        $synced = $row->synced_marketplaces ?? [];
        $synced[(string) $marketplaceId] = [
            'synced_at' => now()->toDateTimeString(),
            'stock' => $stock,
        ];

        $row->update([
            'synced_marketplaces' => $synced,
            'last_error' => null,
        ]);
    }

    public function clearDirty(int $produk_id, int $stock): void
    {
        ShopeeStockSync::where('produk_id', $produk_id)->update([
            'dirty_at' => null,
            'last_synced_at' => now(),
            'last_synced_stock' => $stock,
            'last_error' => null,
            'synced_marketplaces' => null,
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int>  $produkIds
     */
    public function groupProdukIdsByMarketplace(array $produkIds, ?int $marketplaceId = null): Collection
    {
        $syncRows = ShopeeStockSync::whereIn('produk_id', $produkIds)
            ->get()
            ->keyBy('produk_id');

        $query = DB::table('produk_marketplaces as pm')
            ->join('marketplaces as m', 'm.id', '=', 'pm.marketplace_id')
            ->whereIn('pm.produk_id', $produkIds)
            ->where('m.marketplace', 'shopee')
            ->whereNotNull('m.shop_id')
            ->where('m.shop_id', '!=', 0)
            ->whereNotNull('m.access_token')
            ->where('m.auto_sync_stok', true)
            ->select('pm.produk_id', 'pm.marketplace_id');

        if ($marketplaceId) {
            $query->where('pm.marketplace_id', $marketplaceId);
        }

        return $query->get()
            ->filter(function ($row) use ($syncRows) {
                $synced = $syncRows->get($row->produk_id)?->synced_marketplaces ?? [];

                return ! isset($synced[(string) $row->marketplace_id])
                    && ! isset($synced[(int) $row->marketplace_id]);
            })
            ->groupBy('marketplace_id')
            ->map(fn ($rows) => $rows->pluck('produk_id')->unique()->values()->all());
    }

    /**
     * @return array<int>
     */
    public function getActiveMarketplaceIdsForProduk(int $produk_id): array
    {
        return DB::table('produk_marketplaces as pm')
            ->join('marketplaces as m', 'm.id', '=', 'pm.marketplace_id')
            ->where('pm.produk_id', $produk_id)
            ->where('m.marketplace', 'shopee')
            ->whereNotNull('m.shop_id')
            ->where('m.shop_id', '!=', 0)
            ->whereNotNull('m.access_token')
            ->where('m.auto_sync_stok', true)
            ->pluck('pm.marketplace_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<int, ShopeeStockSync>
     */
    public function getPendingSyncs(int $limit = 100): Collection
    {
        return ShopeeStockSync::whereNotNull('dirty_at')
            ->with('produk')
            ->orderByDesc('dirty_at')
            ->limit($limit)
            ->get();
    }

    protected function kirimApiWithRecovery(Marketplace $marketplace, string $path, array $body): array
    {
        $resp = $this->kirimApi($marketplace, $path, $body);

        if (! $this->isApiSuccess($resp) && $this->isTokenApiError($resp)) {
            if ($this->refreshMarketplaceToken($marketplace->id)) {
                $marketplace = Marketplace::withoutGlobalScope('cabang')->find($marketplace->id);
                $resp = $this->kirimApi($marketplace, $path, $body);
            }
        }

        return is_array($resp) ? $resp : ['error' => 'response tidak valid'];
    }

    protected function isApiSuccess(?array $resp): bool
    {
        if (empty($resp) || ! is_array($resp)) {
            return false;
        }

        if (! empty($resp['error'])) {
            return false;
        }

        return empty($resp['response']['failure_list']);
    }

    protected function formatApiError(array $resp): string
    {
        $failureReasons = array_values(array_unique(array_filter(array_map(
            fn ($failure) => $failure['failed_reason'] ?? null,
            $resp['response']['failure_list'] ?? []
        ))));

        if ($failureReasons) {
            return implode('; ', $failureReasons);
        }

        if (! empty($resp['message'])) {
            return (string) $resp['message'];
        }

        if (! empty($resp['error'])) {
            return is_array($resp['error']) ? json_encode($resp['error']) : (string) $resp['error'];
        }

        return json_encode($resp);
    }

    protected function normalizeShopeeStock(int $stock): int
    {
        return min(max($stock, 0), self::MAX_SHOPEE_STOCK);
    }

    /**
     * @return array<string, string>
     */
    protected function failureReasonsByModel(array $resp): array
    {
        $reasons = [];

        foreach ($resp['response']['failure_list'] ?? [] as $failure) {
            $key = array_key_exists('model_id', $failure) ? (string) $failure['model_id'] : '*';
            $reasons[$key] = (string) ($failure['failed_reason'] ?? $this->formatApiError($resp));
        }

        return $reasons;
    }

    /**
     * Null berarti entry tidak tercantum dalam failure_list dan berhasil diproses.
     */
    protected function errorForEntry(int $modelId, array $failureReasons, string $fallback): ?string
    {
        if (! $failureReasons) {
            return $fallback;
        }

        if (isset($failureReasons[(string) $modelId])) {
            return $failureReasons[(string) $modelId];
        }

        if (isset($failureReasons['*'])) {
            return $failureReasons['*'];
        }

        if ($modelId === 0 && count($failureReasons) === 1) {
            return reset($failureReasons);
        }

        return null;
    }

    protected function isPermanentListingError(string $message, array $resp = []): bool
    {
        $haystack = strtolower($message.' '.($resp['message'] ?? '').' '.($resp['debug_message'] ?? ''));

        return str_contains($haystack, 'status is abnormal')
            || str_contains($haystack, 'cannot update any mpsku field');
    }

    protected function logError(Marketplace $marketplace, string $jenis, string $isi, array $context = []): void
    {
        MarketplaceLog::create([
            'isi' => $context ? $isi.' | '.json_encode($context) : $isi,
            'jenis' => $jenis,
            'shop_id' => $marketplace->shop_id,
            'marketplace' => $marketplace->nama,
            'tanggal' => now(),
        ]);
    }
}
