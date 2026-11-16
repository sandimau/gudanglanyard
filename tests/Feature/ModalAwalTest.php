<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AkunDetailController;
use App\Models\AkunDetail;
use App\Services\BukuBesarService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModalAwalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('akun_details', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedBigInteger('akun_kategori_id');
            $table->bigInteger('saldo')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('buku_besars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('akun_detail_id');
            $table->integer('debet');
            $table->integer('kredit');
            $table->string('kode');
            $table->string('ket');
            $table->unsignedBigInteger('detail_id')->nullable();
            $table->timestamps();
        });
        Schema::create('akun_last_saldos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('akun_detail_id');
            $table->integer('tahun');
            $table->bigInteger('saldo');
            $table->timestamps();
        });
    }

    public function test_modal_awal_menambah_saldo_sekali_saja(): void
    {
        $akun = AkunDetail::create(['nama' => 'modal', 'akun_kategori_id' => 5]);
        $controller = new AkunDetailController();

        $controller->storeModalAwal(Request::create('/', 'POST', ['jumlah' => '250000']), $akun, app(BukuBesarService::class));

        $this->assertDatabaseHas('buku_besars', [
            'akun_detail_id' => $akun->id,
            'kode' => 'awal',
            'ket' => 'Modal awal',
            'debet' => 250000,
            'kredit' => 0,
        ]);
        $this->assertSame(250000, (int) $akun->fresh()->saldo);

        try {
            $controller->storeModalAwal(Request::create('/', 'POST', ['jumlah' => '250000']), $akun, app(BukuBesarService::class));
            $this->fail('Modal awal tidak boleh dicatat dua kali.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('jumlah', $exception->errors());
        }

        $this->assertSame(1, DB::table('buku_besars')->count());
        $this->assertSame(250000, (int) $akun->fresh()->saldo);
    }

    public function test_modal_awal_tidak_bisa_diinput_pada_akun_non_modal(): void
    {
        $akun = AkunDetail::create(['nama' => 'kas', 'akun_kategori_id' => 1]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new AkunDetailController())->storeModalAwal(
            Request::create('/', 'POST', ['jumlah' => '250000']),
            $akun,
            app(BukuBesarService::class)
        );
    }
}
