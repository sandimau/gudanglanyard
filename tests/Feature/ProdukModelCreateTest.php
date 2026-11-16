<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProdukModelController;
use App\Models\ProdukModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ProdukModelCreateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->withoutMiddleware();
        view()->share('errors', new ViewErrorBag());

        Schema::create('produk_kategori_utamas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->boolean('jual');
            $table->boolean('beli');
            $table->boolean('stok');
            $table->boolean('produksi');
            $table->boolean('is_custom')->default(false);
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
            $table->integer('harga');
            $table->string('satuan');
            $table->text('deskripsi')->nullable();
            $table->boolean('jual');
            $table->boolean('beli');
            $table->boolean('stok');
            $table->boolean('produksi');
            $table->string('gambar')->nullable();
            $table->unsignedBigInteger('kontak_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_model_id');
            $table->boolean('status');
            $table->string('nama')->nullable();
            $table->integer('hpp')->nullable();
            $table->integer('harga_beli_hpp')->nullable();
            $table->timestamps();
        });
        Schema::create('belanja_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->integer('harga');
        });
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->boolean('status');
        });

        DB::table('produk_kategori_utamas')->insert(['id' => 1, 'nama' => 'Utama', 'jual' => 1, 'beli' => 0, 'stok' => 1, 'produksi' => 0]);
        DB::table('produk_kategoris')->insert(['id' => 130, 'kategori_utama_id' => 1, 'nama' => 'Kategori']);
    }

    public function test_daftar_produk_custom_tidak_menampilkan_kolom_stok_cabang(): void
    {
        DB::table('produk_kategori_utamas')->insert(['id' => 17, 'nama' => 'CETAK CUSTOM OFLINE', 'jual' => 1,
            'beli' => 0, 'stok' => 0, 'produksi' => 0, 'is_custom' => 1]);
        DB::table('produk_kategoris')->insert(['id' => 137, 'kategori_utama_id' => 17, 'nama' => 'ID Card']);
        DB::table('cabangs')->insert(['id' => 1, 'nama' => 'Pusat', 'status' => 1]);

        $this->app->instance('request', Request::create('/admin/produkModel', 'GET', ['kategori_id' => 137]));
        $custom = app(ProdukModelController::class)->index()->getData();
        $this->assertFalse($custom['tampilkanStok']);
        $this->assertCount(0, $custom['cabangs']);
        $this->assertSame([], $custom['stokByProdukCabang']);
        $table = File::get(resource_path('views/produkModel/index.blade.php'));
        $this->assertStringContainsString('@unless ($kategori->kategoriUtama->is_custom)', $table);
        $this->assertStringContainsString('<th>Margin</th>', $table);
        $this->assertStringContainsString('$margin = $hargaJual - (float) $produk->hpp;', $table);

        $this->app->instance('request', Request::create('/admin/produkModel', 'GET', ['kategori_id' => 130]));
        $biasa = app(ProdukModelController::class)->index()->getData();
        $this->assertTrue($biasa['tampilkanStok']);
        $this->assertCount(1, $biasa['cabangs']);
    }

    public function test_form_tambah_tersedia_langsung_di_modal_dan_simpan_membuat_sku(): void
    {
        $popupView = File::get(resource_path('views/produkModel/index.blade.php'));
        $this->assertStringNotContainsString('<iframe', $popupView);
        $this->assertStringContainsString("document.querySelectorAll('[data-produk-popup]')", $popupView);
        $this->assertStringContainsString("body.addEventListener('submit'", $popupView);

        $form = view('produkModel.partials.create-form', [
            'kategori' => \App\Models\ProdukKategori::findOrFail(130),
            'satuan' => ['Pcs'],
            'inModal' => true,
        ])->render();
        $this->assertStringContainsString('enctype="multipart/form-data"', $form);
        $this->assertStringContainsString('type="file"', $form);
        $this->assertStringContainsString('type="submit"', $form);

        $this->from(route('produkModel.index', ['kategori_id' => 130]))
            ->post(route('produkModel.store', ['kategori_id' => 130]), [
                'kategori_id' => 130, 'nama' => 'Produk Baru', 'harga' => 1000, 'satuan' => 'Pcs',
            ])
            ->assertRedirect(route('produkModel.index', ['kategori_id' => 130]));

        $this->assertDatabaseHas('produk_models', ['nama' => 'Produk Baru', 'kategori_id' => 130]);
        $this->assertDatabaseHas('produks', ['produk_model_id' => 1, 'status' => 1]);
    }

    public function test_validasi_kembali_ke_modal_dengan_pesan(): void
    {
        $this->from(route('produkModel.index', ['kategori_id' => 130]))
            ->post(route('produkModel.store', ['kategori_id' => 130]), [
                'kategori_id' => 130, 'nama' => '', 'harga' => 1000, 'satuan' => 'Pcs',
            ])
            ->assertRedirect(route('produkModel.index', ['kategori_id' => 130]))
            ->assertSessionHasErrors('nama');

        $this->assertDatabaseCount('produk_models', 0);
    }

    public function test_gambar_tidak_menimpa_file_lain_saat_upload_beruntun(): void
    {
        $filenames = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $this->post(route('produkModel.store', ['kategori_id' => 130]), [
                    'kategori_id' => 130, 'nama' => 'Produk ' . $i, 'harga' => 1000, 'satuan' => 'Pcs',
                    'gambar' => UploadedFile::fake()->image('foto.png'),
                ])->assertRedirect();
            }

            $filenames = DB::table('produk_models')->pluck('gambar')->all();
            $this->assertCount(2, array_unique($filenames));
            foreach ($filenames as $filename) {
                $this->assertStringStartsWith('storage:', $filename);
                $this->assertFileExists(storage_path('app/produk/' . substr($filename, 8)));
                $this->assertSame(route('produkModel.gambar', ['filename' => substr($filename, 8)]), ProdukModel::gambarUrl($filename));
                $this->get(ProdukModel::gambarUrl($filename))->assertOk()->assertHeader('content-type', 'image/png');
            }
        } finally {
            foreach ($filenames as $filename) {
                File::delete(storage_path('app/produk/' . substr($filename, 8)));
            }
        }
    }

    public function test_update_gambar_menyimpan_file_baru_dan_memperbarui_produk(): void
    {
        DB::table('produk_models')->insert([
            'id' => 5, 'kategori_id' => 130, 'nama' => 'Sebelum', 'harga' => 1000,
            'satuan' => 'Pcs', 'jual' => 1, 'beli' => 0, 'stok' => 1, 'produksi' => 0,
            'gambar' => null,
        ]);

        $filename = null;
        try {
            $request = Request::create(route('produkModel.update', ['produkModel' => 5, 'kategori_id' => 130]), 'PUT', [
                'nama' => 'Sesudah', 'harga' => 2000, 'satuan' => 'Box',
            ], [], ['gambar' => UploadedFile::fake()->image('pengganti.png')]);
            $response = (new ProdukModelController())->update($request, ProdukModel::findOrFail(5));
            $this->assertSame(route('produkModel.index', ['kategori_id' => 130]), $response->getTargetUrl());

            $produk = DB::table('produk_models')->where('id', 5)->first();
            $filename = $produk->gambar;
            $this->assertSame('Sesudah', $produk->nama);
            $this->assertSame('Box', $produk->satuan);
            $this->assertNotEmpty($filename);
            $this->assertStringStartsWith('storage:', $filename);
            $this->assertFileExists(storage_path('app/produk/' . substr($filename, 8)));
        } finally {
            if ($filename) {
                File::delete(storage_path('app/produk/' . substr($filename, 8)));
            }
        }
    }

    public function test_gambar_lama_tetap_menggunakan_url_public_dan_path_tidak_sah_ditolak(): void
    {
        $this->assertSame(asset('uploads/produk/lama.png'), ProdukModel::gambarUrl('lama.png'));
        $this->get(route('produkModel.gambar', ['filename' => 'file-tidak-valid.png']))->assertNotFound();
    }
}
