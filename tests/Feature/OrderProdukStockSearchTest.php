<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrderProdukStockSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
        DB::purge('sqlite');
        $this->withoutMiddleware();

        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode');
            $table->boolean('status');
        });
        Schema::create('kontaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id');
            $table->softDeletes();
        });
        Schema::create('produk_kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
        });
        Schema::create('produk_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->string('nama');
            $table->integer('harga');
            $table->boolean('jual');
        });
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_model_id');
            $table->string('nama');
            $table->boolean('status');
        });
        Schema::create('produk_stoks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('cabang_id');
            $table->integer('tambah');
            $table->integer('kurang');
            $table->timestamp('created_at');
            $table->softDeletes();
        });
        Schema::create('produk_last_stoks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('cabang_id');
            $table->integer('tahun');
            $table->integer('saldo');
        });
    }

    public function test_order_search_only_shows_saleable_products_with_positive_stock_in_the_customers_branch(): void
    {
        $pusat = DB::table('cabangs')->insertGetId(['nama' => 'Pusat', 'kode' => 'PUSAT', 'status' => 1]);
        $cabang = DB::table('cabangs')->insertGetId(['nama' => 'Cabang', 'kode' => 'CAB', 'status' => 1]);
        $this->withSession(['cabang_id' => $pusat]);
        $kontak = DB::table('kontaks')->insertGetId(['cabang_id' => $cabang]);
        $kategori = DB::table('produk_kategoris')->insertGetId(['nama' => 'Lanyard']);
        $model = DB::table('produk_models')->insertGetId([
            'kategori_id' => $kategori, 'nama' => 'Polos', 'harga' => 1000, 'jual' => 1,
        ]);

        $tersedia = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Merah', 'status' => 1]);
        $habis = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Biru', 'status' => 1]);
        $stokCabang = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Hijau', 'status' => 1]);
        $saldoLalu = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Kuning', 'status' => 1]);
        $nonaktif = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Hitam', 'status' => 0]);

        $this->mutasi($tersedia, $pusat, 5, 3);
        $this->mutasi($habis, $pusat, 4, 4);
        $this->mutasi($stokCabang, $cabang, 3, 0);
        $this->mutasi($saldoLalu, $pusat, 0, 1);
        $this->mutasi($nonaktif, $pusat, 2, 0);
        DB::table('produk_last_stoks')->insert([
            'produk_id' => $saldoLalu, 'cabang_id' => $pusat,
            'tahun' => (int) date('Y') - 1, 'saldo' => 3,
        ]);

        $pusatResult = $this->getJson('/admin/produk/api?q=Lanyard&stok_tersedia=1')->assertOk();
        $this->assertEqualsCanonicalizing([$tersedia, $saldoLalu], collect($pusatResult->json())->pluck('id')->all());

        $cabangResult = $this->getJson('/admin/produk/api?q=Lanyard&stok_tersedia=1&kontak_id=' . $kontak)->assertOk();
        $this->assertSame([$stokCabang], collect($cabangResult->json())->pluck('id')->all());

        $normalResult = $this->getJson('/admin/produk/api?q=Lanyard')->assertOk();
        $this->assertEqualsCanonicalizing(
            [$tersedia, $habis, $stokCabang, $saldoLalu],
            collect($normalResult->json())->pluck('id')->all()
        );

        $this->postJson('/admin/order', [
            'kontak_id' => $kontak,
            'produk_id' => $tersedia,
            'harga' => 1000,
            'jumlah' => 1,
            'deathline' => date('Y-m-d'),
        ])->assertUnprocessable()->assertJsonValidationErrors('produk_id');
    }

    private function mutasi(int $produkId, int $cabangId, int $tambah, int $kurang): void
    {
        DB::table('produk_stoks')->insert([
            'produk_id' => $produkId,
            'cabang_id' => $cabangId,
            'tambah' => $tambah,
            'kurang' => $kurang,
            'created_at' => now(),
        ]);
    }
}
