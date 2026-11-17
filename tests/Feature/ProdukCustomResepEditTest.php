<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProdukCustomResepController;
use App\Models\Produk;
use App\Models\ProdukCustomResep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProdukCustomResepEditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('produk_kategori_utamas', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_custom');
        });
        Schema::create('produk_kategoris', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_utama_id');
            $table->string('nama');
            $table->softDeletes();
        });
        Schema::create('produk_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->string('nama');
            $table->boolean('stok');
            $table->softDeletes();
        });
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_model_id');
            $table->string('nama')->nullable();
            $table->boolean('status');
            $table->integer('hpp');
            $table->timestamps();
        });
        Schema::create('produk_custom_reseps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->string('jenis');
            $table->unsignedBigInteger('komponen_model_id')->nullable();
            $table->decimal('jumlah', 12, 2);
            $table->integer('biaya')->nullable();
            $table->string('keterangan')->nullable();
            $table->boolean('per_order')->default(false);
            $table->timestamps();
        });

        DB::table('produk_kategori_utamas')->insert([['id' => 1, 'is_custom' => 1], ['id' => 2, 'is_custom' => 0]]);
        DB::table('produk_kategoris')->insert([['id' => 1, 'kategori_utama_id' => 1, 'nama' => 'Custom'], ['id' => 2, 'kategori_utama_id' => 2, 'nama' => 'Bahan']]);
        DB::table('produk_models')->insert([
            ['id' => 1, 'kategori_id' => 1, 'nama' => 'Produk custom', 'stok' => 0],
            ['id' => 2, 'kategori_id' => 2, 'nama' => 'Komponen', 'stok' => 1],
        ]);
        DB::table('produks')->insert([
            ['id' => 1, 'produk_model_id' => 1, 'status' => 1, 'hpp' => 0],
            ['id' => 2, 'produk_model_id' => 2, 'status' => 1, 'hpp' => 1000],
            ['id' => 3, 'produk_model_id' => 2, 'status' => 1, 'hpp' => 2000],
        ]);
        Gate::shouldReceive('allows')->with('produk_access')->andReturn(true);
    }

    public function test_edit_bahan_memperbarui_jumlah_dan_estimasi_hpp(): void
    {
        $resep = ProdukCustomResep::create(['produk_id' => 1, 'jenis' => 'bahan', 'komponen_model_id' => 2, 'jumlah' => 1]);

        app(ProdukCustomResepController::class)->update(Request::create('/admin/produk/1/resep/' . $resep->id, 'PUT', [
            'jenis' => 'bahan', 'komponen_model_id' => 2, 'jumlah' => 2,
        ]), Produk::findOrFail(1), $resep);

        $this->assertDatabaseHas('produk_custom_reseps', ['id' => $resep->id, 'jumlah' => 2]);
        $this->assertDatabaseHas('produks', ['id' => 1, 'hpp' => 3000]);
    }

    public function test_edit_biaya_internal_memperbarui_hpp(): void
    {
        $resep = ProdukCustomResep::create(['produk_id' => 1, 'jenis' => 'internal', 'jumlah' => 1, 'biaya' => 100, 'per_order' => false]);

        app(ProdukCustomResepController::class)->update(Request::create('/admin/produk/1/resep/' . $resep->id, 'PUT', [
            'jenis' => 'internal', 'jumlah' => 1, 'biaya' => 250, 'per_order' => 1, 'keterangan' => 'Packing',
        ]), Produk::findOrFail(1), $resep);

        $this->assertDatabaseHas('produk_custom_reseps', ['id' => $resep->id, 'biaya' => 250, 'per_order' => 1, 'keterangan' => 'Packing']);
        $this->assertDatabaseHas('produks', ['id' => 1, 'hpp' => 0]);
    }

    public function test_halaman_resep_memuat_hpp_komponen_dan_baris_yang_diedit(): void
    {
        $resep = ProdukCustomResep::create(['produk_id' => 1, 'jenis' => 'bahan', 'komponen_model_id' => 2, 'jumlah' => 1]);
        $view = app(ProdukCustomResepController::class)->index(
            Request::create('/admin/produk/1/resep?edit=' . $resep->id), Produk::findOrFail(1)
        );

        $this->assertSame($resep->id, $view->getData()['editResep']->id);
        $this->assertSame(1500, (int) $view->getData()['reseps'][0]->komponenModel->produk->avg('hpp'));
        $this->assertSame(2, $view->getData()['komponenId']);
    }

    public function test_tidak_bisa_edit_resep_produk_lain(): void
    {
        $resep = ProdukCustomResep::create(['produk_id' => 99, 'jenis' => 'internal', 'jumlah' => 1, 'biaya' => 100]);
        $this->expectException(HttpException::class);

        app(ProdukCustomResepController::class)->update(Request::create('/admin/produk/1/resep/' . $resep->id, 'PUT', [
            'jenis' => 'internal', 'jumlah' => 1, 'biaya' => 250,
        ]), Produk::findOrFail(1), $resep);
    }
}
