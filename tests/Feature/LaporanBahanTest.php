<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LaporanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class LaporanBahanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('produks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_model_id'); $t->string('nama')->nullable(); $t->integer('hpp')->nullable();
        });
        Schema::create('produk_models', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('kategori_id'); $t->string('nama'); $t->integer('harga')->nullable();
        });
        Schema::create('produk_kategoris', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('kategori_utama_id'); $t->string('nama');
        });
        Schema::create('produk_kategori_utamas', function (Blueprint $t) {
            $t->id(); $t->string('nama');
        });
        foreach (['orders', 'project_mps'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id(); $t->unsignedBigInteger('cabang_id')->nullable(); $t->timestamp('created_at'); $t->softDeletes();
            });
        }
        foreach (['order_details', 'project_mp_details'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id(); $t->unsignedBigInteger($table === 'order_details' ? 'order_id' : 'project_id');
                $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('produksi_id')->nullable();
                $t->integer('jumlah'); $t->integer('harga'); $t->integer('hpp')->nullable();
                $t->softDeletes();
            });
        }
        Schema::create('produk_produksi_bahans', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id')->nullable();
            $t->integer('jumlah'); $t->integer('hpp'); $t->timestamp('created_at');
        });
        Schema::create('produk_pakais', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id')->nullable();
            $t->integer('jumlah'); $t->integer('hpp'); $t->integer('harga')->nullable(); $t->timestamp('created_at');
        });

        DB::table('produk_kategori_utamas')->insert([['id' => 1, 'nama' => 'Bahan Baku'], ['id' => 2, 'nama' => 'Jadi']]);
        DB::table('produk_kategoris')->insert([['id' => 1, 'kategori_utama_id' => 1, 'nama' => 'Kain'], ['id' => 2, 'kategori_utama_id' => 2, 'nama' => 'Jadi']]);
        DB::table('produk_models')->insert([['id' => 1, 'kategori_id' => 1, 'nama' => 'Kain', 'harga' => 150], ['id' => 2, 'kategori_id' => 2, 'nama' => 'Tas', 'harga' => 200]]);
        DB::table('produks')->insert([['id' => 1, 'produk_model_id' => 1, 'hpp' => 100], ['id' => 2, 'produk_model_id' => 2, 'hpp' => 100]]);
    }

    public function test_laporan_memisahkan_penjualan_dan_pemakaian_kategori_bahan_saja(): void
    {
        DB::table('orders')->insert(['id' => 1, 'created_at' => '2026-09-12']);
        DB::table('project_mps')->insert(['id' => 1, 'created_at' => '2026-09-14']);
        DB::table('order_details')->insert([
            ['order_id' => 1, 'produk_id' => 1, 'jumlah' => 2, 'harga' => 140, 'hpp' => 100, 'produksi_id' => null],
            ['order_id' => 1, 'produk_id' => 1, 'jumlah' => 1, 'harga' => 140, 'hpp' => 100, 'produksi_id' => 4],
            ['order_id' => 1, 'produk_id' => 2, 'jumlah' => 1, 'harga' => 200, 'hpp' => 100, 'produksi_id' => null],
        ]);
        DB::table('project_mp_details')->insert(['project_id' => 1, 'produk_id' => 1, 'jumlah' => 1, 'harga' => 80, 'hpp' => 100]);
        DB::table('produk_produksi_bahans')->insert([
            ['produk_id' => 1, 'jumlah' => 3, 'hpp' => 110, 'created_at' => '2026-09-15 00:00:00'],
            ['produk_id' => 1, 'jumlah' => 9, 'hpp' => 110, 'created_at' => '2026-10-01 00:00:00'],
        ]);
        DB::table('produk_pakais')->insert([
            ['produk_id' => 1, 'jumlah' => 1, 'hpp' => 90, 'harga' => 140, 'created_at' => '2026-09-17 00:00:00'],
            ['produk_id' => 2, 'jumlah' => 1, 'hpp' => 100, 'harga' => 200, 'created_at' => '2026-09-18 00:00:00'],
        ]);

        $view = (new LaporanController())->bahan(Request::create('/admin/laporan-bahan?bulan=2026-09'));
        $data = $view->getData()['data'];

        $this->assertCount(1, $data);
        $this->assertEquals(3, $data[0]->konsumen->jumlah);
        $this->assertEquals(360, $data[0]->konsumen->omzet);
        $this->assertEquals(300, $data[0]->konsumen->biaya);
        $this->assertEquals(1, $data[0]->produksi->jumlah);
        $this->assertEquals(140, $data[0]->produksi->nilai);
        $this->assertEquals(90, $data[0]->produksi->biaya);
    }

    public function test_harga_katalog_kosong_ditandai_dan_rugi_konsumen_tetap_terlihat(): void
    {
        DB::table('produk_models')->where('id', 1)->update(['harga' => null]);
        DB::table('orders')->insert(['id' => 1, 'created_at' => '2026-09-12 00:00:00']);
        DB::table('order_details')->insert(['order_id' => 1, 'produk_id' => 1, 'jumlah' => 2, 'harga' => 80, 'hpp' => 100]);
        DB::table('produk_produksi_bahans')->insert(['produk_id' => 1, 'jumlah' => 2, 'hpp' => 100, 'created_at' => '2026-09-13 00:00:00']);
        DB::table('produk_pakais')->insert(['produk_id' => 1, 'jumlah' => 2, 'hpp' => 100, 'harga' => null, 'created_at' => '2026-09-13 00:00:00']);

        $view = (new LaporanController())->bahan(Request::create('/admin/laporan-bahan?bulan=2026-09'));
        $item = $view->getData()['data'][0];

        $this->assertEquals(-40, $item->konsumen->omzet - $item->konsumen->biaya);
        $this->assertEquals(2, $item->produksi->tanpa_harga);
        $this->assertEquals(200, $item->produksi->biaya);
    }

    public function test_pemakaian_bahan_dihitung_berdasarkan_harga_yang_tersimpan(): void
    {
        DB::table('produk_pakais')->insert([
            ['produk_id' => 1, 'jumlah' => 2, 'hpp' => 100, 'harga' => 140, 'created_at' => '2026-09-11 00:00:00'],
            ['produk_id' => 1, 'jumlah' => 1, 'hpp' => 120, 'harga' => 150, 'created_at' => '2026-09-12 00:00:00'],
        ]);

        $view = (new LaporanController())->bahan(Request::create('/admin/laporan-bahan?bulan=2026-09'));
        $item = $view->getData()['data'][0];

        $this->assertEquals(3, $item->produksi->jumlah);
        $this->assertEquals(320, $item->produksi->biaya);
        $this->assertEquals(430, $item->produksi->nilai);
    }

    public function test_catatan_pemakaian_lama_tanpa_snapshot_memakai_harga_katalog(): void
    {
        DB::table('produk_pakais')->insert(['produk_id' => 1, 'jumlah' => 2, 'hpp' => 100, 'harga' => null, 'created_at' => '2026-09-13 00:00:00']);

        $view = (new LaporanController())->bahan(Request::create('/admin/laporan-bahan?bulan=2026-09'));
        $item = $view->getData()['data'][0];

        $this->assertEquals(300, $item->produksi->nilai);
        $this->assertEquals(0, $item->produksi->tanpa_harga);
    }
}
