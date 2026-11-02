<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\HppReferensiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class HppReferensiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('produks', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('produk_model_id'); $table->string('nama')->nullable(); $table->integer('hpp')->nullable(); $table->timestamps();
        });
        Schema::create('produk_models', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('kategori_id'); $table->string('nama');
        });
        Schema::create('produk_kategoris', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('kategori_utama_id'); $table->string('nama');
        });
        Schema::create('produk_kategori_utamas', function (Blueprint $table) {
            $table->id(); $table->string('nama');
        });
        DB::table('produk_kategori_utamas')->insert(['id' => 1, 'nama' => 'PO TALILANYARD']);
        DB::table('produk_kategoris')->insert(['id' => 1, 'kategori_utama_id' => 1, 'nama' => 'Lanyard']);
        DB::table('produk_models')->insert(['id' => 1, 'kategori_id' => 1, 'nama' => '1,5 CM']);
        DB::table('produks')->insert(['id' => 1, 'produk_model_id' => 1, 'nama' => '1 sisi', 'hpp' => 100]);
        (require database_path('migrations/2026_10_30_000006_create_hpp_referensis_table.php'))->up();
        (require database_path('migrations/2026_10_30_000007_link_hpp_bahan_to_produks.php'))->up();
        (require database_path('migrations/2026_10_30_000008_sync_hpp_from_excel.php'))->up();
        (require database_path('migrations/2026_10_31_000001_add_hpp_tali_satuan.php'))->up();
        (require database_path('migrations/2026_11_02_000001_add_hpp_idcard_satuan.php'))->up();
    }

    public function test_modal_bahan_satu_dan_dua_sisi_dihitung_dari_komponen(): void
    {
        $row = DB::table('hpp_referensis')->where('jenis', 'bahan')->first();
        $biaya = json_decode($row->biaya, true);

        $this->assertEqualsWithDelta(3029.444444, array_sum($biaya) - $biaya['print2'], 0.00001);
        $this->assertEqualsWithDelta(3704.444444, array_sum($biaya), 0.00001);
        $this->assertSame(11, DB::table('hpp_referensis')->where('jenis', 'bahan')->count());
        $satin = json_decode(DB::table('hpp_referensis')->where('nama', '2 cm satin lanyard')->value('biaya'), true);
        $this->assertSame(2440.0, array_sum($satin));
        $sablon = json_decode(DB::table('hpp_referensis')->where('nama', '1,5 CM (sablon)')->value('biaya'), true);
        $this->assertSame(2547, array_sum($sablon) - $sablon['print2']);
    }

    public function test_modal_idcard_dan_aksesori_terpisah(): void
    {
        $row = DB::table('hpp_referensis')->where('nama', 'OFFSET 10rb')->first();
        $this->assertSame(895, array_sum(json_decode($row->biaya, true)));
        $this->assertSame(5, DB::table('hpp_referensis')->where('jenis', 'aksesori')->count());
        $this->assertSame(18, DB::table('hpp_referensis')->where('jenis', 'idcard')->count());
        $this->assertSame(3, DB::table('hpp_referensis')->where('jenis', 'resin')->count());
        $resin = json_decode(DB::table('hpp_referensis')->where('nama', 'RESIN A4_1 pcs')->value('biaya'), true);
        $this->assertSame(6097, array_sum($resin));
    }

    public function test_perhitungan_tali_bawaan_dan_tambah_nama_produk(): void
    {
        $this->assertSame(6, DB::table('hpp_referensis')->where('jenis', 'tali')->count());
        $biaya = json_decode(DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', 'tali isu 1CM')->value('biaya'), true);
        $this->assertEqualsWithDelta(544.444444, $biaya['modal'] / $biaya['bagi'], 0.00001);

        (new HppReferensiController())->storeTali(Request::create('/admin/hpp/tali', 'POST', [
            'nama' => 'Tali baru', 'modal' => 90000, 'bagi' => 50,
        ]));
        $row = DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', 'Tali baru')->first();
        $this->assertEquals(1800, json_decode($row->biaya, true)['modal'] / json_decode($row->biaya, true)['bagi']);

        (new HppReferensiController())->update(Request::create('/admin/hpp/'.$row->id, 'PATCH', [
            'nama' => 'Tali baru 2', 'biaya' => ['modal' => 80000, 'bagi' => 40],
        ]), $row->id);
        $updated = json_decode(DB::table('hpp_referensis')->where('id', $row->id)->value('biaya'), true);
        $this->assertSame('Tali baru 2', DB::table('hpp_referensis')->where('id', $row->id)->value('nama'));
        $this->assertEquals(2000, $updated['modal'] / $updated['bagi']);
    }

    public function test_bagi_tali_tidak_boleh_nol(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new HppReferensiController())->storeTali(Request::create('/admin/hpp/tali', 'POST', [
            'nama' => 'Tali baru', 'modal' => 90000, 'bagi' => 0,
        ]));
    }

    public function test_perhitungan_bahan_idcard_sesuai_gambar_dan_dapat_disimpan(): void
    {
        $this->assertSame(17, DB::table('hpp_referensis')->where('jenis', 'idcard_satuan')->count());
        foreach (['Offset A4 Lem 0.15' => 420, 'Inkjet A3 0.35' => 580, 'Overlay A4 0.1' => 220] as $nama => $satuan) {
            $biaya = json_decode(DB::table('hpp_referensis')->where('jenis', 'idcard_satuan')->where('nama', $nama)->value('biaya'), true);
            $this->assertEquals($satuan, $biaya['modal'] / $biaya['bagi']);
        }
        $row = DB::table('hpp_referensis')->where('jenis', 'idcard_satuan')->where('nama', 'Pisau Pond / Cm')->first();
        $this->assertSame(['modal' => null, 'bagi' => null], json_decode($row->biaya, true));
        (new HppReferensiController())->update(Request::create('/admin/hpp/'.$row->id, 'PATCH', [
            'nama' => $row->nama, 'biaya' => ['modal' => 200, 'bagi' => 4],
        ]), $row->id);
        $biaya = json_decode(DB::table('hpp_referensis')->where('id', $row->id)->value('biaya'), true);
        $this->assertEquals(50, $biaya['modal'] / $biaya['bagi']);

        (new HppReferensiController())->storeIdcardSatuan(Request::create('/admin/hpp/idcard-satuan', 'POST', [
            'nama' => 'Barang baru', 'modal' => 900, 'bagi' => 3,
        ]));
        $biaya = json_decode(DB::table('hpp_referensis')->where('jenis', 'idcard_satuan')->where('nama', 'Barang baru')->value('biaya'), true);
        $this->assertEquals(300, $biaya['modal'] / $biaya['bagi']);
    }

    public function test_bagi_idcard_tidak_boleh_nol(): void
    {
        $row = DB::table('hpp_referensis')->where('jenis', 'idcard_satuan')->first();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new HppReferensiController())->update(Request::create('/admin/hpp/'.$row->id, 'PATCH', [
            'nama' => $row->nama, 'biaya' => ['modal' => 100, 'bagi' => 0],
        ]), $row->id);
    }

    public function test_semua_rute_hpp_memakai_permission_khusus(): void
    {
        foreach (['hpp.index', 'hpp.cariProduk', 'hpp.tali.store', 'hpp.idcard-satuan.store', 'hpp.update', 'hpp.terapkan'] as $name) {
            $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('can:hpp_access', $middleware);
            $this->assertNotContains('can:produk_access', $middleware);
        }
    }

    public function test_biaya_tidak_bisa_negatif(): void
    {
        $row = DB::table('hpp_referensis')->where('jenis', 'bahan')->first();
        $biaya = json_decode($row->biaya, true);
        $biaya['tali'] = -1;
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new HppReferensiController())->update(Request::create('/admin/hpp/'.$row->id, 'PATCH', ['nama' => 'Tes', 'biaya' => $biaya]), $row->id);
    }

    public function test_hpp_produk_hanya_berubah_saat_diterapkan_secara_eksplisit(): void
    {
        $row = DB::table('hpp_referensis')->where('jenis', 'bahan')->first();
        $biaya = json_decode($row->biaya, true);
        (new HppReferensiController())->update(Request::create('/admin/hpp/'.$row->id, 'PATCH', [
            'nama' => $row->nama, 'biaya' => $biaya, 'produk_id_1s' => 1,
        ]), $row->id);

        $this->assertSame(100, DB::table('produks')->where('id', 1)->value('hpp'));
        (new HppReferensiController())->terapkan(Request::create('/admin/hpp/'.$row->id.'/terapkan', 'POST', ['sisi' => '1s']), $row->id);
        $this->assertSame(3029, DB::table('produks')->where('id', 1)->value('hpp'));
    }

    public function test_simpan_biaya_tanpa_kolom_sku_tidak_melepas_produk_yang_sudah_terhubung(): void
    {
        $row = DB::table('hpp_referensis')->where('jenis', 'bahan')->first();
        DB::table('hpp_referensis')->where('id', $row->id)->update(['produk_id_1s' => 1]);

        (new HppReferensiController())->update(Request::create('/admin/hpp/'.$row->id, 'PATCH', [
            'nama' => $row->nama, 'biaya' => json_decode($row->biaya, true),
        ]), $row->id);

        $this->assertSame(1, DB::table('hpp_referensis')->where('id', $row->id)->value('produk_id_1s'));
    }

    public function test_autocomplete_mencari_sku_dan_nama_produk_bahan(): void
    {
        $controller = new HppReferensiController();
        $bySku = $controller->cariProduk(Request::create('/admin/hpp/cari-produk', 'GET', ['q' => '#1']));
        $this->assertSame(1, $bySku->getData()[0]->id);
        // Minimal dua karakter agar pencarian tidak memuat seluruh katalog.
        $this->assertSame(200, $controller->cariProduk(Request::create('/admin/hpp/cari-produk', 'GET', ['q' => '1,5']))->status());
        $this->assertSame(1, $controller->cariProduk(Request::create('/admin/hpp/cari-produk', 'GET', ['q' => '1,5']))->getData()[0]->id);
    }
}
