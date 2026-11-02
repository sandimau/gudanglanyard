<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\HppReferensiController;
use App\Http\Controllers\Admin\ProdukController;
use App\Models\Produk;
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
        Schema::create('belanja_details', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('produk_id'); $table->integer('harga'); $table->softDeletes();
        });
        Schema::create('produk_models', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('kategori_id'); $table->string('nama'); $table->integer('harga')->nullable(); $table->string('satuan')->nullable();
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
        (require database_path('migrations/2026_11_02_000002_link_hpp_tali_to_produks.php'))->up();
        (require database_path('migrations/2026_11_02_000003_add_harga_beli_hpp_to_produks.php'))->up();
        (require database_path('migrations/2026_11_02_000004_sync_modal_tali_to_produk_hpp.php'))->up();
        (require database_path('migrations/2026_11_02_000005_link_hpp_tali_to_bahan.php'))->up();
        (require database_path('migrations/2026_11_02_000006_read_modal_tali_from_last_purchase.php'))->up();
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

        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 90000]);
        (new HppReferensiController())->storeTali(Request::create('/admin/hpp/tali', 'POST', [
            'nama' => 'Tali baru', 'bagi' => 50, 'produk_id_tali' => 1,
        ]));
        $row = DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', 'Tali baru')->first();
        $this->assertEquals(1800, json_decode($row->biaya, true)['modal'] / json_decode($row->biaya, true)['bagi']);

        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 80000]);
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
            'nama' => 'Tali baru', 'bagi' => 0, 'produk_id_tali' => 1,
        ]));
    }

    public function test_satuan_tali_bawaan_memperbarui_kolom_tali_bahan_tanpa_mengubah_komponen_lain(): void
    {
        $tali = DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', 'tali isu 1,5CM')->first();
        $bahan = DB::table('hpp_referensis')->where('jenis', 'bahan')->where('nama', '1,5 CM')->first();
        $this->assertEquals($bahan->id, $tali->bahan_id_tali);
        $sebelum = json_decode($bahan->biaya, true);
        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 36000]);

        (new HppReferensiController())->update(Request::create('/admin/hpp/'.$tali->id, 'PATCH', [
            'nama' => $tali->nama, 'biaya' => ['bagi' => 45], 'produk_id_tali' => 1,
        ]), $tali->id);

        $sesudah = json_decode(DB::table('hpp_referensis')->where('id', $bahan->id)->value('biaya'), true);
        $this->assertEquals(800, $sesudah['tali']);
        $this->assertSame($sebelum['print1'], $sesudah['print1']);
        $this->assertSame($sebelum['print2'], $sesudah['print2']);
    }

    public function test_tali_baru_dapat_ditautkan_dan_dipindah_ke_baris_bahan_lain(): void
    {
        $bahanAwal = DB::table('hpp_referensis')->where('jenis', 'bahan')->where('nama', '1,5 CM (varian kedua)')->first();
        $bahanBaru = DB::table('hpp_referensis')->where('jenis', 'bahan')->where('nama', '2 CM (varian kedua)')->first();
        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 90000]);
        (new HppReferensiController())->storeTali(Request::create('/admin/hpp/tali', 'POST', [
            'nama' => 'Tali varian', 'bagi' => 50, 'bahan_id_tali' => $bahanAwal->id, 'produk_id_tali' => 1,
        ]));
        $tali = DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', 'Tali varian')->first();
        $this->assertEquals(1800, json_decode(DB::table('hpp_referensis')->where('id', $bahanAwal->id)->value('biaya'), true)['tali']);

        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 100000]);
        (new HppReferensiController())->update(Request::create('/admin/hpp/'.$tali->id, 'PATCH', [
            'nama' => $tali->nama, 'biaya' => ['bagi' => 50], 'bahan_id_tali' => $bahanBaru->id,
        ]), $tali->id);
        $this->assertEquals(1800, json_decode(DB::table('hpp_referensis')->where('id', $bahanAwal->id)->value('biaya'), true)['tali']);
        $this->assertEquals(2000, json_decode(DB::table('hpp_referensis')->where('id', $bahanBaru->id)->value('biaya'), true)['tali']);
    }

    public function test_baris_non_bahan_tidak_bisa_ditautkan_sebagai_tali(): void
    {
        $idcard = DB::table('hpp_referensis')->where('jenis', 'idcard')->value('id');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new HppReferensiController())->storeTali(Request::create('/admin/hpp/tali', 'POST', [
            'nama' => 'Tali baru', 'bagi' => 45, 'bahan_id_tali' => $idcard, 'produk_id_tali' => 1,
        ]));
    }

    public function test_modal_tali_hanya_dari_pembelian_terakhir_dan_tidak_menimpa_hpp_produk(): void
    {
        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 700]);
        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 800, 'deleted_at' => now()]);
        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 900]);

        (new HppReferensiController())->storeTali(Request::create('/admin/hpp/tali', 'POST', [
            'nama' => 'Tali Inventory', 'modal' => 45000, 'bagi' => 45, 'produk_id_tali' => 1,
        ]));
        $row = DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', 'Tali Inventory')->first();
        $this->assertSame(1, $row->produk_id_tali);
        $this->assertEquals(900, json_decode($row->biaya, true)['modal']);
        $this->assertSame(100, DB::table('produks')->where('id', 1)->value('hpp'));
        $this->assertNull(DB::table('produks')->where('id', 1)->value('harga_beli_hpp'));

        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 1200]);
        (new HppReferensiController())->update(Request::create('/admin/hpp/'.$row->id, 'PATCH', [
            'nama' => 'Tali Inventory', 'biaya' => ['modal' => 50000.75, 'bagi' => 40],
        ]), $row->id);
        $this->assertEquals(1200, json_decode(DB::table('hpp_referensis')->where('id', $row->id)->value('biaya'), true)['modal']);
        $this->assertNull(DB::table('produks')->where('id', 1)->value('harga_beli_hpp'));
        $this->assertSame(100, DB::table('produks')->where('id', 1)->value('hpp'));
        $this->assertSame(700, DB::table('belanja_details')->where('produk_id', 1)->value('harga'));
    }

    public function test_migrasi_mengembalikan_harga_beli_dari_belanja_dan_memperbarui_satuan_bahan(): void
    {
        $row = DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', 'tali isu 1,5CM')->first();
        DB::table('hpp_referensis')->where('id', $row->id)->update([
            'produk_id_tali' => 1, 'biaya' => json_encode(['modal' => 90000, 'bagi' => 45]),
        ]);
        DB::table('produks')->where('id', 1)->update(['hpp' => 100, 'harga_beli_hpp' => 2000]);
        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 45000]);

        (require database_path('migrations/2026_11_02_000006_read_modal_tali_from_last_purchase.php'))->up();

        $this->assertSame(45000, DB::table('produks')->where('id', 1)->value('hpp'));
        $this->assertNull(DB::table('produks')->where('id', 1)->value('harga_beli_hpp'));
        $this->assertEquals(45000, json_decode(DB::table('hpp_referensis')->where('id', $row->id)->value('biaya'), true)['modal']);
        $this->assertEquals(1000, json_decode(DB::table('hpp_referensis')->where('id', $row->bahan_id_tali)->value('biaya'), true)['tali']);
    }

    public function test_halaman_hpp_memperbarui_modal_dan_tali_setelah_ada_pembelian_baru(): void
    {
        $tali = DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', 'tali isu 1,5CM')->first();
        DB::table('hpp_referensis')->where('id', $tali->id)->update(['produk_id_tali' => 1]);
        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 45000]);

        (new HppReferensiController())->index(Request::create('/admin/hpp?jenis=bahan', 'GET', ['jenis' => 'bahan']));
        $this->assertEquals(45000, json_decode(DB::table('hpp_referensis')->where('id', $tali->id)->value('biaya'), true)['modal']);
        $this->assertEquals(1000, json_decode(DB::table('hpp_referensis')->where('id', $tali->bahan_id_tali)->value('biaya'), true)['tali']);

        DB::table('belanja_details')->insert(['produk_id' => 1, 'harga' => 54000]);
        (new HppReferensiController())->index(Request::create('/admin/hpp?jenis=bahan', 'GET', ['jenis' => 'bahan']));
        $this->assertEquals(54000, json_decode(DB::table('hpp_referensis')->where('id', $tali->id)->value('biaya'), true)['modal']);
        $this->assertEquals(1200, json_decode(DB::table('hpp_referensis')->where('id', $tali->bahan_id_tali)->value('biaya'), true)['tali']);
    }

    public function test_tali_baru_memerlukan_produk_dengan_riwayat_belanja(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new HppReferensiController())->storeTali(Request::create('/admin/hpp/tali', 'POST', [
            'nama' => 'Tanpa belanja', 'produk_id_tali' => 1, 'bagi' => 45,
        ]));
    }

    public function test_produk_inventory_tidak_valid_ditolak(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new HppReferensiController())->storeTali(Request::create('/admin/hpp/tali', 'POST', [
            'nama' => 'Tali baru', 'modal' => 90000, 'bagi' => 45, 'produk_id_tali' => 999,
        ]));
    }

    public function test_pencarian_tali_menemukan_produk_inventory_berdasarkan_sku(): void
    {
        $response = (new HppReferensiController())->cariProdukTali(Request::create('/admin/hpp/cari-produk-tali', 'GET', ['q' => '#1']));
        $this->assertSame(1, $response->getData()[0]->id);
        $this->assertSame(1, $response->getData()[0]->model_id);
        $this->assertSame(1, $response->getData()[0]->kategori_id);
        $this->assertEquals(100, $response->getData()[0]->hpp);
    }

    public function test_url_detail_produk_lama_mengarah_ke_halaman_model_inventory(): void
    {
        $response = (new ProdukController())->show(Produk::findOrFail(1));
        $this->assertSame(route('produkModel.show', ['produkModel' => 1, 'kategori_id' => 1]), $response->getTargetUrl());
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
        foreach (['hpp.index', 'hpp.cariProduk', 'hpp.cariProdukTali', 'hpp.tali.store', 'hpp.idcard-satuan.store', 'hpp.update', 'hpp.terapkan'] as $name) {
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
