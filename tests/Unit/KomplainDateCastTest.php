<?php

namespace Tests\Unit;

use App\Models\Komplain;
use App\Models\OrderDetail;
use App\Models\Produk;
use Carbon\Carbon;
use Tests\TestCase;

class KomplainDateCastTest extends TestCase
{
    public function test_approval_date_can_be_formatted(): void
    {
        $komplain = new Komplain();
        $komplain->approved_at = '2026-11-03 12:00:00';

        $this->assertInstanceOf(Carbon::class, $komplain->approved_at);
        $this->assertSame('03-11-2026 12:00', $komplain->approved_at->format('d-m-Y H:i'));
    }

    public function test_replacement_unit_cost_falls_back_to_product_hpp(): void
    {
        $komplain = new Komplain();
        $komplain->setRelation('orderDetail', new OrderDetail(['hpp' => 0]));
        $komplain->setRelation('projectMpDetail', null);
        $komplain->setRelation('produk', new Produk(['hpp' => 1200]));

        $this->assertSame(1200, $komplain->hpp_satuan);
    }
}
