<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProdukKategoriUtamaController;
use App\Models\ProdukKategoriUtama;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProdukKategoriUtamaCustomTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('produk_kategori_utamas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->boolean('jual');
            $table->boolean('beli');
            $table->boolean('stok');
            $table->boolean('produksi');
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
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
            $table->boolean('jual');
            $table->boolean('beli');
            $table->boolean('stok');
            $table->boolean('produksi');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_model_id');
        });
        Schema::create('produk_custom_reseps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
        });

        DB::table('produk_kategori_utamas')->insert([
            ['id' => 17, 'nama' => 'CETAK CUSTOM OFLINE', 'jual' => 1, 'beli' => 0, 'stok' => 0, 'produksi' => 0, 'is_custom' => 0],
            ['id' => 18, 'nama' => 'Produk Stok', 'jual' => 1, 'beli' => 0, 'stok' => 1, 'produksi' => 0, 'is_custom' => 0],
        ]);
        DB::table('produk_kategoris')->insert([
            ['id' => 1, 'kategori_utama_id' => 17, 'nama' => 'Custom'],
            ['id' => 2, 'kategori_utama_id' => 18, 'nama' => 'Barang'],
        ]);
        DB::table('produk_models')->insert([
            ['id' => 1, 'kategori_id' => 1, 'jual' => 1, 'beli' => 0, 'stok' => 0, 'produksi' => 0],
            ['id' => 2, 'kategori_id' => 2, 'jual' => 1, 'beli' => 0, 'stok' => 1, 'produksi' => 0],
        ]);
        DB::table('produks')->insert(['id' => 1, 'produk_model_id' => 1]);
    }

    private function requestFor(int $id, bool $custom): Request
    {
        return Request::create('/admin/produk-kategori-utama/' . $id, 'PUT', [
            'nama' => $id === 17 ? 'CETAK CUSTOM OFLINE' : 'Produk Stok',
            'jual' => '1', 'beli' => '0', 'stok' => $id === 17 ? '0' : '1',
            'produksi' => '0', 'is_custom' => $custom ? '1' : '0',
        ]);
    }

    public function test_kategori_lama_bisa_diaktifkan_tanpa_mengubah_produksi(): void
    {
        app(ProdukKategoriUtamaController::class)->update($this->requestFor(17, true), ProdukKategoriUtama::findOrFail(17));

        $this->assertDatabaseHas('produk_kategori_utamas', ['id' => 17, 'is_custom' => 1, 'stok' => 0, 'produksi' => 0]);
        $this->assertDatabaseHas('produk_models', ['id' => 1, 'stok' => 0, 'produksi' => 0]);
        $this->assertStringContainsString('name="is_custom"', File::get(resource_path('views/produk-kategori-utama/edit.blade.php')));
    }

    public function test_kategori_dengan_resep_tidak_boleh_dimatikan(): void
    {
        DB::table('produk_kategori_utamas')->where('id', 17)->update(['is_custom' => 1]);
        DB::table('produk_custom_reseps')->insert(['produk_id' => 1]);

        $this->expectException(ValidationException::class);
        app(ProdukKategoriUtamaController::class)->update($this->requestFor(17, false), ProdukKategoriUtama::findOrFail(17));
    }

    public function test_kategori_produk_berstok_tidak_boleh_dijadikan_custom(): void
    {
        $this->expectException(ValidationException::class);
        app(ProdukKategoriUtamaController::class)->update($this->requestFor(18, true), ProdukKategoriUtama::findOrFail(18));
    }

    public function test_kategori_custom_baru_tidak_mencatat_stok_produk_jadi(): void
    {
        app(ProdukKategoriUtamaController::class)->store(Request::create('/admin/produk-kategori-utama', 'POST', [
            'nama' => 'Cetak Baru', 'jual' => '0', 'beli' => '1', 'stok' => '1',
            'produksi' => '0', 'is_custom' => '1',
        ]));

        $this->assertDatabaseHas('produk_kategori_utamas', [
            'nama' => 'Cetak Baru', 'is_custom' => 1, 'jual' => 1, 'beli' => 0, 'stok' => 0, 'produksi' => 0,
        ]);
    }
}
