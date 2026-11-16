<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\HutangController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HutangCabangKontakTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode');
        });
        Schema::create('kontaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id');
            $table->string('nama');
            $table->softDeletes();
        });
        Schema::create('hutangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kontak_id');
            $table->unsignedBigInteger('akun_detail_id')->nullable();
            $table->string('jenis');
            $table->integer('jumlah');
            $table->timestamps();
        });
        Schema::create('akun_details', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('hutang_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hutang_id');
            $table->integer('jumlah');
        });

        DB::table('cabangs')->insert([['id' => 1, 'kode' => 'PUSAT'], ['id' => 2, 'kode' => 'JKT']]);
        DB::table('kontaks')->insert(['id' => 1, 'cabang_id' => 1, 'nama' => 'Pemasok Pusat']);
        DB::table('hutangs')->insert(['id' => 1, 'kontak_id' => 1, 'jenis' => 'hutang', 'jumlah' => 1000]);
    }

    public function test_hutang_tetap_menampilkan_nama_kontak_saat_berpindah_cabang(): void
    {
        session(['cabang_id' => 2]);

        $hutang = app(HutangController::class)->index(Request::create('/admin/hutang'))->getData()['hutangs']->first();

        $this->assertSame('Pemasok Pusat', $hutang->kontak->nama);
    }

    public function test_hutang_tetap_menampilkan_kontak_yang_sudah_diarsipkan(): void
    {
        session(['cabang_id' => 2]);
        DB::table('kontaks')->where('id', 1)->update(['deleted_at' => now()]);

        $hutang = app(HutangController::class)->index(Request::create('/admin/hutang'))->getData()['hutangs']->first();

        $this->assertSame('Pemasok Pusat', $hutang->kontak->nama);
    }
}
