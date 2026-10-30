<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PemakaianCabangLegacyTest extends TestCase
{
    public function test_migrasi_menambah_cabang_dan_mewarisi_cabang_mutasi_stok(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('cabangs', function (Blueprint $t) {
            $t->id(); $t->string('kode');
        });
        Schema::create('produk_stoks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('cabang_id')->nullable();
        });
        Schema::create('produk_pakais', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_stok_id')->nullable();
        });

        DB::table('cabangs')->insert([['id' => 1, 'kode' => 'PUSAT'], ['id' => 2, 'kode' => 'CABANG']]);
        DB::table('produk_stoks')->insert(['id' => 10, 'cabang_id' => 2]);
        DB::table('produk_pakais')->insert([['id' => 1, 'produk_stok_id' => 10], ['id' => 2, 'produk_stok_id' => null]]);

        $migration = require database_path('migrations/2026_10_30_000005_add_missing_cabang_to_produk_pakais.php');
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('produk_pakais', 'cabang_id'));
        $this->assertDatabaseHas('produk_pakais', ['id' => 1, 'cabang_id' => 2]);
        $this->assertDatabaseHas('produk_pakais', ['id' => 2, 'cabang_id' => 1]);
    }
}
