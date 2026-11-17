<?php

namespace Tests\Feature;

use App\Models\OrderDetail;
use App\Models\ProjectMpDetail;
use App\Services\ProduksiStatusService;
use App\Services\ShopeeStockSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProduksiStatusStockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        session(['cabang_id' => 1]);

        Schema::create('cabangs', function (Blueprint $t) {
            $t->id();
            $t->string('kode');
            $t->string('nama');
            $t->boolean('status');
        });
        Schema::create('produk_kategori_utamas', function (Blueprint $t) {
            $t->id();
            $t->string('nama')->nullable();
            $t->boolean('is_custom')->default(false);
        });
        Schema::create('produk_kategoris', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('kategori_utama_id')->nullable();
            $t->string('nama');
            $t->softDeletes();
        });
        Schema::create('produk_models', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('kategori_id');
            $t->string('nama');
            $t->boolean('stok');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('produks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('produk_model_id');
            $t->string('nama')->nullable();
            $t->integer('hpp')->default(0);
            $t->boolean('status')->default(true);
            $t->timestamps();
        });
        Schema::create('kontaks', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->unsignedBigInteger('cabang_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('kontak_id');
            $t->unsignedBigInteger('cabang_id');
            $t->string('nota');
            $t->string('konsumen_detail')->nullable();
            $t->integer('total')->default(0);
            $t->integer('diskon')->default(0);
            $t->integer('ongkir')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('marketplaces', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('cabang_id');
            $t->string('nama');
            $t->timestamps();
        });
        Schema::create('project_mps', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('cabang_id');
            $t->unsignedBigInteger('marketplace_id')->nullable();
            $t->string('nota');
            $t->string('konsumen')->nullable();
            $t->timestamps();
        });
        Schema::create('produksis', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('grup');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('order_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('produk_id');
            $t->integer('jumlah');
            $t->unsignedBigInteger('produksi_id')->nullable();
            $t->integer('hpp')->nullable();
            $t->integer('harga')->nullable();
            $t->unsignedBigInteger('komplain_id')->nullable();
            $t->text('material_ids')->nullable();
            $t->timestamp('custom_resep_disalin_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('project_mp_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('project_id');
            $t->unsignedBigInteger('produk_id');
            $t->integer('jumlah');
            $t->unsignedBigInteger('produksi_id')->nullable();
            $t->integer('hpp')->nullable();
            $t->text('material_ids')->nullable();
            $t->timestamp('custom_resep_disalin_at')->nullable();
            $t->timestamps();
        });
        Schema::create('order_custom_bahans', function (Blueprint $t) {
            $t->id();
            $t->string('sumber');
            $t->unsignedBigInteger('detail_id');
            $t->unsignedBigInteger('produk_id');
            $t->integer('jumlah');
            $t->integer('hpp')->default(0);
            $t->unsignedBigInteger('ambil_barang_id')->nullable();
            $t->timestamps();
        });
        Schema::create('produk_stoks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('produk_id');
            $t->unsignedBigInteger('cabang_id');
            $t->integer('tambah');
            $t->integer('kurang');
            $t->integer('hpp')->nullable();
            $t->string('kode')->nullable();
            $t->string('keterangan')->nullable();
            $t->unsignedBigInteger('detail_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('produk_last_stoks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('produk_id');
            $t->unsignedBigInteger('cabang_id');
            $t->integer('tahun');
            $t->integer('saldo');
            $t->timestamps();
        });

        DB::table('cabangs')->insert(['id' => 1, 'kode' => 'PUSAT', 'nama' => 'Pusat', 'status' => 1]);
        DB::table('produk_kategori_utamas')->insert([
            ['id' => 1, 'nama' => 'Biasa', 'is_custom' => 0],
            ['id' => 2, 'nama' => 'Produk Custom', 'is_custom' => 1],
        ]);
        DB::table('produk_kategoris')->insert([
            ['id' => 1, 'kategori_utama_id' => 1, 'nama' => 'Biasa'],
            ['id' => 2, 'kategori_utama_id' => 2, 'nama' => 'Custom'],
        ]);
        DB::table('produk_models')->insert([
            ['id' => 1, 'kategori_id' => 1, 'nama' => 'Barang', 'stok' => 1],
            ['id' => 2, 'kategori_id' => 2, 'nama' => 'Custom', 'stok' => 0],
            ['id' => 3, 'kategori_id' => 1, 'nama' => 'Bahan', 'stok' => 1],
        ]);
        DB::table('produks')->insert([
            ['id' => 1, 'produk_model_id' => 1, 'nama' => 'Produk A', 'hpp' => 1000],
            ['id' => 2, 'produk_model_id' => 2, 'nama' => 'Produk Custom', 'hpp' => 0],
            ['id' => 3, 'produk_model_id' => 3, 'nama' => 'Bahan A', 'hpp' => 100],
        ]);
        DB::table('kontaks')->insert(['id' => 1, 'nama' => 'Pembeli', 'cabang_id' => 1]);
        DB::table('orders')->insert([
            'id' => 1, 'kontak_id' => 1, 'cabang_id' => 1, 'nota' => 'OFF1', 'konsumen_detail' => '',
        ]);
        DB::table('marketplaces')->insert(['id' => 1, 'cabang_id' => 1, 'nama' => 'Shopee']);
        DB::table('project_mps')->insert([
            'id' => 1, 'cabang_id' => 1, 'marketplace_id' => 1, 'nota' => 'MP1', 'konsumen' => 'Pembeli MP',
        ]);
        DB::table('produksis')->insert([
            ['id' => 1, 'nama' => 'Persiapan', 'grup' => 'awal'],
            ['id' => 2, 'nama' => 'Desain', 'grup' => 'Desain'],
            ['id' => 3, 'nama' => 'Finish', 'grup' => 'Selesai'],
            ['id' => 4, 'nama' => 'batal', 'grup' => 'batal'],
        ]);
        DB::table('order_details')->insert([
            ['id' => 1, 'order_id' => 1, 'produk_id' => 1, 'jumlah' => 3, 'produksi_id' => 1],
            ['id' => 2, 'order_id' => 1, 'produk_id' => 2, 'jumlah' => 2, 'produksi_id' => 1],
        ]);
        DB::table('project_mp_details')->insert([
            ['id' => 1, 'project_id' => 1, 'produk_id' => 1, 'jumlah' => 4, 'produksi_id' => 1],
            ['id' => 2, 'project_id' => 1, 'produk_id' => 2, 'jumlah' => 2, 'produksi_id' => 1],
        ]);
        DB::table('order_custom_bahans')->insert([
            ['sumber' => 'order', 'detail_id' => 2, 'produk_id' => 3, 'jumlah' => 10],
            ['sumber' => 'project', 'detail_id' => 2, 'produk_id' => 3, 'jumlah' => 6],
        ]);
        DB::table('produk_stoks')->insert([
            ['produk_id' => 1, 'cabang_id' => 1, 'tambah' => 10, 'kurang' => 0, 'kode' => 'awal', 'created_at' => now()],
            ['produk_id' => 3, 'cabang_id' => 1, 'tambah' => 100, 'kurang' => 0, 'kode' => 'awal', 'created_at' => now()],
        ]);

        $this->mock(ShopeeStockSyncService::class)->shouldReceive('markDirty')->zeroOrMoreTimes();
    }

    public function test_order_offline_memotong_stok_saat_keluar_grup_awal_dan_mengembalikan_saat_batal(): void
    {
        $service = app(ProduksiStatusService::class);
        $detail = OrderDetail::findOrFail(1);

        $service->apply($detail, 2);
        $this->assertSame(7, app(\App\Services\StokService::class)->saldoTersedia(1, 1));
        $this->assertDatabaseHas('produk_stoks', [
            'produk_id' => 1, 'detail_id' => 1, 'kode' => 'jual', 'kurang' => 3,
        ]);

        $service->apply($detail->fresh(), 3);
        $service->apply($detail->fresh(), 4);
        $this->assertSame(10, app(\App\Services\StokService::class)->saldoTersedia(1, 1));
        $this->assertDatabaseHas('produk_stoks', [
            'produk_id' => 1, 'detail_id' => 1, 'kode' => 'btl', 'tambah' => 3,
        ]);
    }

    public function test_order_offline_custom_memotong_stok_bahan_resep(): void
    {
        app(ProduksiStatusService::class)->apply(OrderDetail::findOrFail(2), 2);

        $this->assertSame(90, app(\App\Services\StokService::class)->saldoTersedia(3, 1));
        $this->assertDatabaseHas('produk_stoks', [
            'produk_id' => 3, 'detail_id' => 1, 'kode' => 'jual', 'kurang' => 10,
        ]);
    }

    public function test_order_online_produk_berstok_tidak_dipotong_lagi_saat_pindah_status(): void
    {
        app(ProduksiStatusService::class)->apply(ProjectMpDetail::findOrFail(1), 2);

        $this->assertSame(10, app(\App\Services\StokService::class)->saldoTersedia(1, 1));
        $this->assertDatabaseMissing('produk_stoks', ['produk_id' => 1, 'kode' => 'jual']);
    }

    public function test_order_online_custom_memotong_stok_bahan_resep(): void
    {
        app(ProduksiStatusService::class)->apply(ProjectMpDetail::findOrFail(2), 2);

        $this->assertSame(94, app(\App\Services\StokService::class)->saldoTersedia(3, 1));
        $this->assertDatabaseHas('produk_stoks', [
            'produk_id' => 3, 'detail_id' => 1, 'kode' => 'jual', 'kurang' => 6,
        ]);
    }
}
