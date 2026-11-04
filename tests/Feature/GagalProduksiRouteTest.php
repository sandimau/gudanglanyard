<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class GagalProduksiRouteTest extends TestCase
{
    public function test_standalone_daily_report_and_entry_routes(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertSame('gagalProduksi.index', $routes->match(Request::create('/admin/gagal-produksi'))->getName());
        $this->assertSame('gagalProduksi.storeOrder', $routes->match(Request::create('/admin/order/123/gagal-produksi', 'POST'))->getName());
        $this->assertSame('gagalProduksi.storeProjectMp', $routes->match(Request::create('/admin/projectMp/123/gagal-produksi', 'POST'))->getName());
        $this->assertSame('gagalProduksi.destroy', $routes->match(Request::create('/admin/gagal-produksi/123', 'DELETE'))->getName());
    }
}
