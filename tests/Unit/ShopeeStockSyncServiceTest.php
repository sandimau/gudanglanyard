<?php

namespace Tests\Unit;

use App\Services\ShopeeStockSyncService;
use PHPUnit\Framework\TestCase;

class ShopeeStockSyncServiceTest extends TestCase
{
    public function test_stock_is_kept_within_shopee_limits(): void
    {
        $service = new TestableShopeeStockSyncService;

        $this->assertSame(0, $service->normalize(-1));
        $this->assertSame(123, $service->normalize(123));
        $this->assertSame(10000000, $service->normalize(49986624));
    }

    public function test_failure_reason_is_more_useful_than_generic_error_code(): void
    {
        $service = new TestableShopeeStockSyncService;
        $response = [
            'error' => 'product.error_busi_update_stock_failed',
            'message' => 'Update stock failed, please check failure_list for detailed reason',
            'response' => [
                'failure_list' => [[
                    'model_id' => 123,
                    'failed_reason' => 'Stock should be within 0-10000000 for model A',
                ]],
            ],
        ];

        $this->assertSame(
            'Stock should be within 0-10000000 for model A',
            $service->formatError($response)
        );
        $this->assertSame('Stock should be within 0-10000000 for model A', $service->entryError(123, $response));
        $this->assertNull($service->entryError(456, $response));
    }

    public function test_abnormal_listing_error_is_permanent_for_current_sync(): void
    {
        $service = new TestableShopeeStockSyncService;

        $this->assertTrue($service->permanent(
            'All the fields cannot be updated because the product status is abnormal'
        ));
        $this->assertFalse($service->permanent('Too many requests'));
    }
}

class TestableShopeeStockSyncService extends ShopeeStockSyncService
{
    public function normalize(int $stock): int
    {
        return $this->normalizeShopeeStock($stock);
    }

    public function formatError(array $response): string
    {
        return $this->formatApiError($response);
    }

    public function entryError(int $modelId, array $response): ?string
    {
        return $this->errorForEntry(
            $modelId,
            $this->failureReasonsByModel($response),
            $this->formatApiError($response)
        );
    }

    public function permanent(string $message): bool
    {
        return $this->isPermanentListingError($message);
    }
}
