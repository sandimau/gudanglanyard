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
        $this->assertSame('gagalProduksi.create', $routes->match(Request::create('/admin/gagal-produksi/create'))->getName());
        $this->assertSame('gagalProduksi.cariProduk', $routes->match(Request::create('/admin/gagal-produksi/cari-produk'))->getName());
        $this->assertSame('gagalProduksi.store', $routes->match(Request::create('/admin/gagal-produksi', 'POST'))->getName());
        $this->assertSame('gagalProduksi.destroy', $routes->match(Request::create('/admin/gagal-produksi/123', 'DELETE'))->getName());
    }
}
