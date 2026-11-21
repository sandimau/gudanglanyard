<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LaporanController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LabaRugiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (['orders', 'project_mps'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id(); $t->integer('total'); $t->timestamp('created_at');
                if ($table === 'orders') {
                    $t->softDeletes(); $t->integer('marketplace')->nullable(); $t->integer('bayar')->nullable();
                } else {
                    $t->integer('bersih')->nullable();
                }
            });
        }
        foreach (['order_details', 'project_mp_details'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id(); $t->unsignedBigInteger($table === 'order_details' ? 'order_id' : 'project_id');
                $t->unsignedBigInteger('produk_id'); $t->integer('harga'); $t->integer('jumlah');
                $t->integer('hpp')->nullable(); $t->integer('produksi_id')->nullable();
                if ($table === 'order_details') $t->softDeletes();
            });
        }
        Schema::create('produks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_model_id'); $t->integer('hpp');
        });
        Schema::create('produk_models', function (Blueprint $t) {
            $t->id(); $t->integer('stok')->nullable();
        });
        Schema::create('produk_stoks', function (Blueprint $t) {
            $t->id(); $t->string('kode'); $t->integer('hpp');
            $t->integer('tambah')->nullable(); $t->integer('kurang')->nullable();
            $t->timestamp('created_at'); $t->softDeletes();
        });
        Schema::create('belanjas', function (Blueprint $t) {
            $t->id(); $t->timestamp('created_at'); $t->softDeletes();
        });
        Schema::create('belanja_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('belanja_id'); $t->unsignedBigInteger('produk_id');
            $t->integer('harga'); $t->integer('jumlah');
        });
        Schema::create('penggajians', function (Blueprint $t) {
            $t->id(); $t->integer('total'); $t->integer('kasbon')->nullable();
            $t->timestamp('created_at'); $t->softDeletes();
        });
        Schema::create('tunjangans', function (Blueprint $t) {
            $t->id(); $t->integer('jumlah'); $t->timestamp('created_at'); $t->softDeletes();
        });
        Schema::create('komplains', function (Blueprint $t) {
            $t->id(); $t->string('status'); $t->integer('kerugian'); $t->timestamp('approved_at')->nullable();
            $t->softDeletes();
        });
        Schema::create('gagal_produksis', function (Blueprint $t) {
            $t->id(); $t->date('tanggal'); $t->integer('kerugian');
        });
    }

    public function test_laporan_mengabaikan_data_dihapus_dan_memisahkan_opname_masuk_keluar(): void
    {
        $tanggal = '2024-03-15 12:00:00';
        DB::table('produk_models')->insert(['id' => 1, 'stok' => null]);
        DB::table('produks')->insert(['id' => 1, 'produk_model_id' => 1, 'hpp' => 80]);
        DB::table('orders')->insert([
            ['id' => 1, 'total' => 1000, 'created_at' => $tanggal, 'deleted_at' => null],
            ['id' => 2, 'total' => 900, 'created_at' => $tanggal, 'deleted_at' => $tanggal],
        ]);
        DB::table('order_details')->insert([
            ['order_id' => 1, 'produk_id' => 1, 'harga' => 100, 'jumlah' => 2, 'hpp' => 50, 'deleted_at' => null],
            ['order_id' => 1, 'produk_id' => 1, 'harga' => 100, 'jumlah' => 3, 'hpp' => 50, 'deleted_at' => $tanggal],
            ['order_id' => 2, 'produk_id' => 1, 'harga' => 100, 'jumlah' => 4, 'hpp' => 50, 'deleted_at' => null],
        ]);
        DB::table('produk_stoks')->insert([
            ['kode' => 'opn', 'hpp' => 10, 'tambah' => 3, 'kurang' => null, 'created_at' => $tanggal, 'deleted_at' => null],
            ['kode' => 'opn', 'hpp' => 10, 'tambah' => null, 'kurang' => 2, 'created_at' => $tanggal, 'deleted_at' => null],
            ['kode' => 'opn', 'hpp' => 10, 'tambah' => null, 'kurang' => 99, 'created_at' => $tanggal, 'deleted_at' => $tanggal],
        ]);
        DB::table('belanjas')->insert([
            ['id' => 1, 'created_at' => $tanggal, 'deleted_at' => null],
            ['id' => 2, 'created_at' => $tanggal, 'deleted_at' => $tanggal],
        ]);
        DB::table('belanja_details')->insert([
            ['belanja_id' => 1, 'produk_id' => 1, 'harga' => 5, 'jumlah' => 2],
            ['belanja_id' => 2, 'produk_id' => 1, 'harga' => 100, 'jumlah' => 2],
        ]);
        DB::table('penggajians')->insert(['total' => 40, 'kasbon' => null, 'created_at' => $tanggal]);

        $view = (new LaporanController())->labarugi(Request::create('/admin/labarugi?bulan=2024-03'));
        $data = $view->getData();

        $this->assertEquals(1000, $data['omzet']);
        $this->assertEquals(100, $data['hpp']);
        $this->assertEquals(30, $data['opnameMasuk']);
        $this->assertEquals(20, $data['opnameKeluar']);
        $this->assertEquals(10, $data['beban']);
        $this->assertEquals(40, $data['gaji']);
        $this->assertEquals(860, $data['labaBersih']);
    }

    public function test_bulan_tidak_valid_ditolak(): void
    {
        $this->expectException(ValidationException::class);
        (new LaporanController())->labarugi(Request::create('/admin/labarugi?bulan=2024-99'));
    }
}
