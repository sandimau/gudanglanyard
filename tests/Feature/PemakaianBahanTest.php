<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PemakaianController;
use App\Models\ProdukStok;
use App\Models\User;
use App\Services\StokService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class PemakaianBahanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('produks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_model_id'); $t->integer('hpp');
        });
        Schema::create('produk_models', function (Blueprint $t) {
            $t->id(); $t->integer('harga')->nullable(); $t->softDeletes();
        });
        Schema::create('produk_pakais', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id')->nullable();
            $t->unsignedBigInteger('produk_stok_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable();
            $t->integer('jumlah'); $t->integer('hpp')->nullable(); $t->integer('harga')->nullable();
            $t->text('keterangan')->nullable(); $t->timestamps();
        });
        DB::table('produk_models')->insert(['id' => 1, 'harga' => 150]);
        DB::table('produks')->insert(['id' => 1, 'produk_model_id' => 1, 'hpp' => 100]);

        $user = new User();
        $user->id = 7;
        Auth::login($user);
        Session::put('cabang_id', 1);
    }

    public function test_pemakaian_mengurangi_stok_dan_menyimpan_hpp_serta_harga(): void
    {
        $stok = new ProdukStok();
        $stok->id = 42;
        $service = Mockery::mock(StokService::class);
        $service->shouldReceive('kurang')->once()
            ->with(1, 1, 2, 'pakai', 'Untuk jahit', null, ['user_id' => 7])
            ->andReturn($stok);
        app()->instance(StokService::class, $service);

        (new PemakaianController())->store(Request::create('/', 'POST', [
            'produk_id' => 1, 'jumlah' => 2, 'keterangan' => 'Untuk jahit',
        ]));

        $this->assertDatabaseHas('produk_pakais', [
            'produk_id' => 1, 'cabang_id' => 1, 'jumlah' => 2,
            'hpp' => 100, 'harga' => 150, 'produk_stok_id' => 42,
        ]);
    }

    public function test_stok_gagal_tidak_membuat_catatan(): void
    {
        $service = Mockery::mock(StokService::class);
        $service->shouldReceive('kurang')->once()->andThrow(ValidationException::withMessages(['jumlah' => 'Stok tidak cukup.']));
        app()->instance(StokService::class, $service);

        try {
            (new PemakaianController())->store(Request::create('/', 'POST', [
                'produk_id' => 1, 'jumlah' => 999, 'keterangan' => 'Untuk jahit',
            ]));
            $this->fail('Seharusnya menolak stok tidak cukup.');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('produk_pakais', 0);
        }
    }

    public function test_mode_semua_cabang_ditolak_agar_stok_pusat_tidak_berkurang(): void
    {
        Session::put('cabang_id', 'all');
        $this->expectException(ValidationException::class);
        (new PemakaianController())->store(Request::create('/', 'POST', [
            'produk_id' => 1, 'jumlah' => 1, 'keterangan' => 'Untuk jahit',
        ]));
    }

    public function test_catatan_dari_menu_lama_dipindah_tanpa_membuat_mutasi_stok_baru(): void
    {
        Schema::create('bahan_pakais', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id');
            $t->unsignedBigInteger('produk_stok_id'); $t->unsignedBigInteger('user_id');
            $t->integer('jumlah'); $t->integer('hpp'); $t->integer('harga');
            $t->text('keterangan')->nullable(); $t->timestamps();
        });
        DB::table('bahan_pakais')->insert([
            'produk_id' => 1, 'cabang_id' => 1, 'produk_stok_id' => 42,
            'user_id' => 7, 'jumlah' => 2, 'hpp' => 100, 'harga' => 150,
            'created_at' => '2026-09-12 00:00:00', 'updated_at' => '2026-09-12 00:00:00',
        ]);

        $migration = require database_path('migrations/2026_10_30_000004_move_bahan_pakais_to_produk_pakais.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseCount('produk_pakais', 1);
        $this->assertDatabaseHas('produk_pakais', ['produk_stok_id' => 42, 'harga' => 150, 'jumlah' => 2]);
        $this->assertDatabaseCount('bahan_pakais', 1);
    }
}
