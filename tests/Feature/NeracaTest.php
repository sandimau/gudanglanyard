<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LaporanController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NeracaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('akun_kategoris', function (Blueprint $t) {
            $t->id(); $t->softDeletes();
        });
        Schema::create('akun_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('akun_kategori_id'); $t->integer('saldo'); $t->softDeletes();
        });
        Schema::create('hutangs', function (Blueprint $t) {
            $t->id(); $t->string('jenis'); $t->integer('jumlah');
        });
        Schema::create('hutang_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('hutang_id'); $t->integer('jumlah');
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); $t->integer('total'); $t->integer('bayar')->nullable();
            $t->string('marketplace')->nullable(); $t->softDeletes();
        });
        Schema::create('produks', function (Blueprint $t) {
            $t->id(); $t->integer('hpp');
        });
        Schema::create('produk_last_stoks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id');
            $t->integer('tahun'); $t->integer('saldo');
        });
        Schema::create('produk_stoks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id');
            $t->integer('tambah')->nullable(); $t->integer('kurang')->nullable();
            $t->timestamp('created_at'); $t->softDeletes();
        });
    }

    public function test_neraca_memakai_hpp_stok_saldo_hutang_positif_dan_order_aktif(): void
    {
        DB::table('akun_kategoris')->insert([['id' => 1], ['id' => 5]]);
        DB::table('akun_details')->insert([
            ['akun_kategori_id' => 1, 'saldo' => 1000],
            ['akun_kategori_id' => 5, 'saldo' => 400],
        ]);
        DB::table('hutangs')->insert([
            ['id' => 1, 'jenis' => 'piutang', 'jumlah' => 150],
            ['id' => 2, 'jenis' => 'piutang', 'jumlah' => 100],
            ['id' => 3, 'jenis' => 'belanja produksi', 'jumlah' => 200],
            ['id' => 4, 'jenis' => 'aset', 'jumlah' => 80],
        ]);
        DB::table('hutang_details')->insert([
            ['hutang_id' => 1, 'jumlah' => 50],
            ['hutang_id' => 2, 'jumlah' => 150],
            ['hutang_id' => 3, 'jumlah' => 40],
        ]);
        DB::table('orders')->insert([
            ['total' => 500, 'bayar' => null, 'marketplace' => null, 'deleted_at' => null],
            ['total' => 100, 'bayar' => 150, 'marketplace' => null, 'deleted_at' => null],
            ['total' => 300, 'bayar' => 0, 'marketplace' => null, 'deleted_at' => now()],
            ['total' => 200, 'bayar' => 0, 'marketplace' => 'shopee', 'deleted_at' => null],
        ]);
        DB::table('produks')->insert(['id' => 1, 'hpp' => 20]);
        DB::table('produk_last_stoks')->insert([
            ['produk_id' => 1, 'cabang_id' => 1, 'tahun' => now()->year - 2, 'saldo' => 3],
            ['produk_id' => 1, 'cabang_id' => 1, 'tahun' => now()->year - 1, 'saldo' => 4],
            ['produk_id' => 1, 'cabang_id' => 1, 'tahun' => now()->year, 'saldo' => 6],
            ['produk_id' => 1, 'cabang_id' => 2, 'tahun' => now()->year - 1, 'saldo' => 2],
        ]);
        DB::table('produk_stoks')->insert([
            ['produk_id' => 1, 'cabang_id' => 1, 'tambah' => 3, 'kurang' => 0, 'created_at' => now(), 'deleted_at' => null],
            ['produk_id' => 1, 'cabang_id' => 1, 'tambah' => 0, 'kurang' => 1, 'created_at' => now(), 'deleted_at' => null],
            ['produk_id' => 1, 'cabang_id' => 2, 'tambah' => 0, 'kurang' => 1, 'created_at' => now(), 'deleted_at' => null],
            ['produk_id' => 1, 'cabang_id' => 2, 'tambah' => 50, 'kurang' => 0, 'created_at' => now(), 'deleted_at' => now()],
        ]);

        $data = (new LaporanController())->neraca()->getData();

        $this->assertEquals(140, $data['stok']); // (4 + 2 + 2 - 1) x HPP 20
        $this->assertEquals(100, $data['total_piutang']);
        $this->assertEquals(240, $data['total_hutang']);
        $this->assertEquals(500, $data['total_order']);
        $this->assertEquals(1740, $data['totalAset']);
        $this->assertEquals(1100, $data['selisih']);
    }
}
