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

    public function markDirty(int $produk_id): void
    {
        if (!DB::table('produks')->where('id', $produk_id)->exists()) {
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

    public function calculateShopeeStock(int $produk_id, int $paket = 1): int
    {
        $paket = max($paket, 1);

        $produk = DB::table('produks')
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->where('produks.id', $produk_id)
            ->select('produk_models.stok', 'produk_models.stok_min_mp')
            ->first();

        if (!$produk) {
            return 0;
        }

        if ((int) $produk->stok !== 1) {
            return self::UNLIMITED_STOCK;
        }

        $saldo = app(StokService::class)->saldoTersedia($produk_id);
        $buffer = (int) ($produk->stok_min_mp ?? 0);
        $saldo = max(0, $saldo - $buffer);

        return (int) floor($saldo / $paket);
    }

    /**
     * @return array{success: bool, synced: int, failed: int, skipped: int, errors: array<int, string>}
     */
    public function processDirtyProducts(int $limit = 200, ?int $marketplaceId = null): array
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

        if ($grouped->isEmpty()) {
            foreach ($dirtyRows as $row) {
                $this->clearDirty($row->produk_id, $this->calculateShopeeStock($row->produk_id));
            }

            return ['success' => true, 'synced' => 0, 'failed' => 0, 'skipped' => count($produkIds), 'errors' => []];
        }

        $synced = 0;
        $failed = 0;
        $errors = [];

        foreach ($grouped as $mpId => $mpProdukIds) {
            $result = $this->syncMarketplaceListings((int) $mpId, $mpProdukIds);
            $synced += $result['synced'];
            $failed += $result['failed'];
            $errors = array_merge($errors, $result['errors']);
        }

        return [
            'success' => $failed === 0,
            'synced' => $synced,
            'failed' => $failed,
            'skipped' => 0,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int>  $produkIds
     * @return array{success: bool, synced: int, failed: int, errors: array<int, string>}
     */
    public function syncMarketplaceListings(int $marketplaceId, array $produkIds): array
    {
        $marketplace = Marketplace::where('id', $marketplaceId)
            ->where('marketplace', 'shopee')
            ->whereNotNull('shop_id')
            ->where('shop_id', '!=', 0)
            ->whereNotNull('access_token')
            ->first();

        if (!$marketplace) {
            return [
                'success' => false,
                'synced' => 0,
                'failed' => count($produkIds),
                'errors' => [$marketplaceId => 'Marketplace Shopee tidak ditemukan atau belum tersinkron'],
            ];
        }

        if (!$marketplace->auto_sync_stok) {
            return ['success' => true, 'synced' => 0, 'failed' => 0, 'errors' => []];
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

            return ['success' => true, 'synced' => 0, 'failed' => 0, 'errors' => []];
        }

        $synced = 0;
        $failed = 0;
        $errors = [];
        $processedProdukIds = [];

        foreach ($listings->groupBy('item_id') as $itemId => $itemListings) {
            $stockList = [];
            $itemProdukIds = [];

            foreach ($itemListings as $listing) {
                $stock = $this->calculateShopeeStock((int) $listing->produk_id, (int) $listing->paket);
                $entry = [
                    'seller_stock' => [
                        ['stock' => $stock],
                    ],
                ];

                if ((int) $listing->model_id > 0) {
                    $entry['model_id'] = (int) $listing->model_id;
                }

                $stockList[] = $entry;
                $itemProdukIds[(int) $listing->produk_id] = $stock;
            }

            $body = [
                'item_id' => (int) $itemId,
                'stock_list' => $stockList,
            ];

            $resp = $this->kirimApiWithRecovery($marketplace, 'product/update_stock', $body);

            if ($this->isApiSuccess($resp)) {
                foreach ($itemProdukIds as $produkId => $stock) {
                    $this->markMarketplaceSynced($produkId, $marketplaceId, $stock);
                    $processedProdukIds[$produkId] = true;
                    $synced++;
                }

                $marketplace->update(['tglSyncStok' => now()]);
            } else {
                $errorMsg = $this->formatApiError($resp);
                $this->logError($marketplace, 'sync stok', $errorMsg, $body);
                $failed += count($itemProdukIds);

                foreach (array_keys($itemProdukIds) as $produkId) {
                    $errors[$produkId] = $errorMsg;
                    ShopeeStockSync::where('produk_id', $produkId)->update([
                        'last_error' => $errorMsg,
                        'updated_at' => now(),
                    ]);
                }
            }

            usleep(500000);
        }

        foreach (array_keys($processedProdukIds) as $produkId) {
            $this->tryClearDirty((int) $produkId);
        }

        return [
            'success' => $failed === 0,
            'synced' => $synced,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    public function tryClearDirty(int $produk_id): void
    {
        $row = ShopeeStockSync::where('produk_id', $produk_id)->first();

        if (!$row || !$row->needsSync()) {
            return;
        }

        $requiredMarketplaceIds = $this->getActiveMarketplaceIdsForProduk($produk_id);

        if (empty($requiredMarketplaceIds)) {
            $this->clearDirty($produk_id, $this->calculateShopeeStock($produk_id));

            return;
        }

        $synced = $row->synced_marketplaces ?? [];

        foreach ($requiredMarketplaceIds as $mpId) {
            if (!isset($synced[(string) $mpId]) && !isset($synced[$mpId])) {
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

        if (!$this->isApiSuccess($resp) && $this->isTokenApiError($resp)) {
            if ($this->refreshMarketplaceToken($marketplace->id)) {
                $marketplace = Marketplace::find($marketplace->id);
                $resp = $this->kirimApi($marketplace, $path, $body);
            }
        }

        return is_array($resp) ? $resp : ['error' => 'response tidak valid'];
    }

    protected function isApiSuccess(?array $resp): bool
    {
        if (empty($resp) || !is_array($resp)) {
            return false;
        }

        if (!empty($resp['error'])) {
            return false;
        }

        return isset($resp['response']) || empty($resp['error']);
    }

    protected function formatApiError(array $resp): string
    {
        if (!empty($resp['error'])) {
            return is_array($resp['error']) ? json_encode($resp['error']) : (string) $resp['error'];
        }

        if (!empty($resp['message'])) {
            return (string) $resp['message'];
        }

        return json_encode($resp);
    }

    protected function logError(Marketplace $marketplace, string $jenis, string $isi, array $context = []): void
    {
        MarketplaceLog::create([
            'isi' => $context ? $isi . ' | ' . json_encode($context) : $isi,
            'jenis' => $jenis,
            'shop_id' => $marketplace->shop_id,
            'marketplace' => $marketplace->nama,
            'tanggal' => now(),
        ]);
    }
}
