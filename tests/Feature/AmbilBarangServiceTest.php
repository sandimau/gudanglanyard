<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Controllers\Admin\AmbilBarangController;
use App\Services\AmbilBarangService;
use App\Services\ShopeeStockSyncService;
use App\Http\Controllers\Webhook\BufferController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AmbilBarangServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        session(['cabang_id' => 1]);

        Schema::create('cabangs', function (Blueprint $t) { $t->id(); $t->string('kode'); $t->string('nama'); $t->boolean('status'); });
        Schema::create('produk_kategori_utamas', function (Blueprint $t) { $t->id(); $t->boolean('is_custom')->default(false); });
        Schema::create('produk_kategoris', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('kategori_utama_id')->nullable(); $t->string('nama'); $t->softDeletes(); });
        Schema::create('produk_models', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('kategori_id'); $t->string('nama'); $t->boolean('stok'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('produks', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('produk_model_id'); $t->string('nama')->nullable(); $t->integer('hpp')->default(0); $t->timestamps(); });
        Schema::create('kontaks', function (Blueprint $t) { $t->id(); $t->string('nama'); $t->unsignedBigInteger('cabang_id')->nullable(); $t->boolean('marketplace')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('orders', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('kontak_id'); $t->unsignedBigInteger('cabang_id'); $t->string('nota'); $t->string('pengiriman')->nullable(); $t->boolean('marketplace')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('project_mps', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('cabang_id'); $t->unsignedBigInteger('marketplace_id')->nullable(); $t->string('nota'); $t->string('konsumen')->nullable(); $t->string('shipping')->nullable(); $t->integer('total')->nullable(); $t->string('keterangan')->nullable(); $t->timestamp('deadline')->nullable(); $t->timestamps(); });
        Schema::create('marketplaces', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('cabang_id'); $t->string('nama'); $t->timestamps(); });
        Schema::create('marketplace_buffers', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('project_id')->nullable(); $t->string('mp')->nullable(); $t->string('nota')->nullable(); $t->unsignedBigInteger('marketplace_id')->nullable(); $t->boolean('custom')->nullable(); $t->timestamps(); });
        Schema::create('produk_marketplaces', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('model_id'); $t->unsignedBigInteger('item_id'); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('marketplace_id'); $t->integer('paket'); $t->integer('harga'); $t->string('nama'); $t->string('varian')->nullable(); $t->timestamps(); $t->unique(['model_id', 'item_id']); });
        Schema::create('produksis', function (Blueprint $t) { $t->id(); $t->string('nama'); $t->string('grup'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('order_details', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('order_id'); $t->unsignedBigInteger('produk_id'); $t->integer('jumlah'); $t->unsignedBigInteger('produksi_id')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('project_mp_details', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('project_id'); $t->unsignedBigInteger('produk_id'); $t->integer('jumlah'); $t->unsignedBigInteger('produksi_id')->nullable(); $t->integer('harga')->nullable(); $t->integer('hpp')->nullable(); $t->string('nota')->nullable(); $t->string('tema')->nullable(); $t->text('material_ids')->nullable(); $t->timestamp('custom_resep_disalin_at')->nullable(); $t->timestamps(); });
        Schema::create('produk_stoks', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id'); $t->integer('tambah'); $t->integer('kurang'); $t->integer('hpp')->nullable(); $t->string('kode')->nullable(); $t->string('keterangan')->nullable(); $t->unsignedBigInteger('detail_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('produk_last_stoks', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id'); $t->integer('tahun'); $t->integer('saldo'); $t->timestamps(); });
        Schema::create('ambil_barangs', function (Blueprint $t) { $t->id(); $t->string('sumber'); $t->unsignedBigInteger('detail_id'); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id'); $t->integer('jumlah'); $t->unsignedBigInteger('produk_stok_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable(); $t->timestamp('created_at')->nullable(); $t->string('nota')->nullable(); $t->string('pembeli')->nullable(); $t->string('jenis')->nullable(); $t->string('shipping')->nullable(); $t->timestamp('tanggal_order')->nullable(); $t->unique(['sumber', 'detail_id']); });
        Schema::create('cek_stok_kosongs', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('produk_id'); $t->unsignedBigInteger('cabang_id'); $t->timestamp('created_at')->nullable(); });

        DB::table('cabangs')->insert(['id' => 1, 'kode' => 'PUSAT', 'nama' => 'Pusat', 'status' => 1]);
        DB::table('produk_kategori_utamas')->insert(['id' => 1, 'is_custom' => false]);
        DB::table('produk_kategoris')->insert(['id' => 1, 'nama' => 'Kategori', 'kategori_utama_id' => 1]);
        DB::table('produk_models')->insert([['id' => 1, 'kategori_id' => 1, 'nama' => 'Barang', 'stok' => 1], ['id' => 2, 'kategori_id' => 1, 'nama' => 'Custom', 'stok' => 0]]);
        DB::table('produks')->insert([['id' => 1, 'produk_model_id' => 1, 'nama' => 'A'], ['id' => 2, 'produk_model_id' => 2, 'nama' => 'B']]);
        DB::table('kontaks')->insert(['id' => 1, 'nama' => 'Pembeli', 'cabang_id' => 1]);
        DB::table('orders')->insert(['id' => 1, 'kontak_id' => 1, 'cabang_id' => 1, 'nota' => 'OFF1', 'pengiriman' => 'Kurir Lokal']);
        DB::table('project_mps')->insert(['id' => 1, 'cabang_id' => 1, 'nota' => 'MP1', 'konsumen' => 'Pembeli MP', 'shipping' => 'SPX']);
        DB::table('marketplaces')->insert(['id' => 1, 'cabang_id' => 1, 'nama' => 'Shopee']);
        DB::table('produksis')->insert(['id' => 1, 'nama' => 'Persiapan', 'grup' => 'awal']);
        DB::table('order_details')->insert([
            ['id' => 1, 'order_id' => 1, 'produk_id' => 1, 'jumlah' => 4, 'produksi_id' => 1],
            ['id' => 2, 'order_id' => 1, 'produk_id' => 2, 'jumlah' => 10, 'produksi_id' => 1],
        ]);
        DB::table('project_mp_details')->insert(['id' => 1, 'project_id' => 1, 'produk_id' => 1, 'jumlah' => 5, 'produksi_id' => 1]);
        DB::table('produk_stoks')->insert(['produk_id' => 1, 'cabang_id' => 1, 'tambah' => 6, 'kurang' => 0, 'created_at' => now()]);

        $user = new User();
        $user->id = 1;
        $user->setRelation('roles', collect([(object) ['name' => 'super']]));
        $this->be($user);
        $this->mock(ShopeeStockSyncService::class)->shouldReceive('markDirty')->zeroOrMoreTimes();
    }

    public function test_kebutuhan_digabung_dan_produk_tanpa_stok_dikecualikan(): void
    {
        $group = app(AmbilBarangService::class)->kebutuhan()->sole();
        $this->assertSame(9, $group['jumlah']);
        $this->assertSame(6, $group['stok']);
        $this->assertEqualsCanonicalizing(['order:1', 'project:1'], $group['items']->pluck('key')->all());
    }

    public function test_marketplace_produk_langsung_memotong_stok_sekali_dan_custom_tetap_menunggu_pengambilan(): void
    {
        DB::table('marketplace_buffers')->insert([
            ['mp' => 'shopee', 'nota' => 'MP-PRODUK'],
            ['mp' => 'shopee', 'nota' => 'MP-CUSTOM'],
            ['mp' => 'shopee', 'nota' => 'MP-CAMPUR'],
        ]);
        $marketplace = (object) ['id' => 1, 'cabang_id' => 1, 'nama' => 'Shopee'];
        $orders = [
            ['order_sn' => 'MP-PRODUK', 'buyer_username' => 'Pembeli Produk', 'total_amount' => 100,
                'shipping_carrier' => 'Kurir', 'message_to_seller' => '', 'create_time' => time(), 'item_list' => [[
                    'model_sku' => '1', 'item_sku' => '', 'model_quantity_purchased' => 2, 'model_discounted_price' => 50,
                    'item_id' => 101, 'model_id' => 201, 'item_name' => 'Barang', 'model_name' => 'A',
                ]]],
            ['order_sn' => 'MP-CUSTOM', 'buyer_username' => 'Pembeli Custom', 'total_amount' => 50,
                'shipping_carrier' => 'Kurir', 'message_to_seller' => '', 'create_time' => time(), 'item_list' => [[
                    'model_sku' => 'CUSTOM_1', 'item_sku' => '', 'model_quantity_purchased' => 1, 'model_discounted_price' => 50,
                    'item_id' => 102, 'model_id' => 202, 'item_name' => 'Barang Custom', 'model_name' => 'A',
                ]]],
            ['order_sn' => 'MP-CAMPUR', 'buyer_username' => 'Pembeli Campur', 'total_amount' => 100,
                'shipping_carrier' => 'Kurir', 'message_to_seller' => '', 'create_time' => time(), 'item_list' => [
                    ['model_sku' => '1', 'item_sku' => '', 'model_quantity_purchased' => 1, 'model_discounted_price' => 50,
                        'item_id' => 103, 'model_id' => 203, 'item_name' => 'Barang', 'model_name' => 'A'],
                    ['model_sku' => 'CUSTOM_1', 'item_sku' => '', 'model_quantity_purchased' => 1, 'model_discounted_price' => 50,
                        'item_id' => 104, 'model_id' => 204, 'item_name' => 'Barang Custom', 'model_name' => 'A'],
                ]],
        ];
        $controller = \Mockery::mock(BufferController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('ambilApi')->twice()->andReturn(['response' => ['order_list' => $orders]]);
        $process = new \ReflectionMethod(BufferController::class, 'prosesBufferShop');
        $buffers = [(object) ['nota' => 'MP-PRODUK', 'status' => 'PROCESSED'], (object) ['nota' => 'MP-CUSTOM', 'status' => 'PROCESSED'], (object) ['nota' => 'MP-CAMPUR', 'status' => 'PROCESSED']];

        $process->invoke($controller, $marketplace, $buffers);
        $produk = DB::table('project_mp_details')->where('nota', 'MP-PRODUK')->first();
        $custom = DB::table('project_mp_details')->where('nota', 'MP-CUSTOM')->first();
        $campur = DB::table('project_mp_details')->where('nota', 'MP-CAMPUR')->orderBy('id')->get();
        $this->assertDatabaseHas('produk_stoks', ['kode' => 'shp', 'detail_id' => $produk->project_id, 'kurang' => 2]);
        $this->assertDatabaseHas('ambil_barangs', ['sumber' => 'project', 'detail_id' => $produk->id, 'jenis' => 'Produk']);
        $this->assertDatabaseHas('ambil_barangs', ['sumber' => 'project', 'detail_id' => $campur[0]->id, 'jenis' => 'Produk']);
        $this->assertDatabaseMissing('ambil_barangs', ['sumber' => 'project', 'detail_id' => $custom->id]);
        $this->assertDatabaseMissing('ambil_barangs', ['sumber' => 'project', 'detail_id' => $campur[1]->id]);
        $this->assertSame(3, app(\App\Services\StokService::class)->saldoTersedia(1, 1));
        $this->assertEqualsCanonicalizing(['project:' . $custom->id, 'project:' . $campur[1]->id, 'order:1', 'project:1'], app(AmbilBarangService::class)->kebutuhan()->first()['items']->pluck('key')->all());

        $process->invoke($controller, $marketplace, $buffers);
        $this->assertDatabaseCount('ambil_barangs', 2);
        $this->assertDatabaseCount('project_mp_details', 5);
        $this->assertDatabaseCount('produk_stoks', 3);

        $this->assertSame(1, app(AmbilBarangService::class)->ambil(['project:' . $campur[1]->id]));
        $this->assertDatabaseCount('ambil_barangs', 3);
    }

    public function test_halaman_tidak_menimpa_variabel_cabangs_yang_dipakai_header(): void
    {
        Gate::shouldReceive('allows')->once()->with('produk_stok_access')->andReturn(true);
        $data = app(AmbilBarangController::class)->index(Request::create('/admin/ambil-barang'), app(AmbilBarangService::class))->getData();

        $this->assertArrayNotHasKey('cabangs', $data);
        $this->assertSame('Pusat', $data['namaCabangAmbilBarang'][1]);
        $this->assertSame(1, $data['cabangKelompok']->count());
    }

    public function test_tab_arsip_menampilkan_data_pengambilan_tanpa_filter_shipping(): void
    {
        Gate::shouldReceive('allows')->twice()->with('produk_stok_access')->andReturn(true);
        $controller = app(AmbilBarangController::class);
        $service = app(AmbilBarangService::class);
        $pending = $controller->index(Request::create('/admin/ambil-barang'), $service)->getData();
        $this->assertCount(2, $pending['cabangKelompok'][1]['Stok kurang']->first()['items']);

        $service->ambil(['order:1']);
        DB::table('orders')->where('id', 1)->update(['deleted_at' => now()]);
        DB::table('order_details')->where('id', 1)->update(['deleted_at' => now()]);
        $archive = $controller->index(Request::create('/admin/ambil-barang', 'GET', ['tab' => 'arsip']), $service)->getData();
        $item = $archive['arsipCabang'][1]->first()['items']->first();
        $this->assertSame('OFF1', $item['nota']);
        $this->assertSame('Pembeli', $item['pembeli']);
        $this->assertSame(4, $item['jumlah']);
    }

    public function test_arsip_dipaginasi_dua_puluh_detail_dan_total_produk_tetap_akurat(): void
    {
        $rows = [];
        for ($id = 100; $id < 125; $id++) {
            $rows[] = [
                'sumber' => 'project', 'detail_id' => $id, 'produk_id' => 1,
                'cabang_id' => 1, 'jumlah' => 1, 'nota' => 'MP-' . $id,
                'created_at' => now(),
            ];
        }
        DB::table('ambil_barangs')->insert($rows);
        Gate::shouldReceive('allows')->twice()->with('produk_stok_access')->andReturn(true);

        $controller = app(AmbilBarangController::class);
        $service = app(AmbilBarangService::class);
        $first = $controller->index(Request::create('/admin/ambil-barang', 'GET', ['tab' => 'arsip']), $service)->getData();
        $second = $controller->index(Request::create('/admin/ambil-barang', 'GET', ['tab' => 'arsip', 'page' => 2]), $service)->getData();

        $this->assertSame(25, $first['arsipPagination']->total());
        $this->assertCount(20, $first['arsipPagination']->items());
        $this->assertCount(5, $second['arsipPagination']->items());
        $this->assertSame(21, $second['arsipPagination']->firstItem());
        $this->assertSame(25, $second['arsipCabang'][1]->first()['jumlah']);
        $this->assertStringContainsString('tab=arsip', $first['arsipPagination']->url(2));
        $this->assertStringContainsString('page=2', $first['arsipPagination']->url(2));
    }

    public function test_arsip_role_stock_bahan_hanya_memuat_cabang_member(): void
    {
        Schema::create('members', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('cabang_id');
            $t->softDeletes();
        });
        DB::table('cabangs')->insert(['id' => 2, 'kode' => 'CABANG', 'nama' => 'Cabang', 'status' => 1]);
        DB::table('members')->insert(['user_id' => 2, 'cabang_id' => 1]);
        DB::table('ambil_barangs')->insert([
            ['sumber' => 'project', 'detail_id' => 100, 'produk_id' => 1, 'cabang_id' => 1, 'jumlah' => 2, 'created_at' => now()],
            ['sumber' => 'project', 'detail_id' => 101, 'produk_id' => 1, 'cabang_id' => 2, 'jumlah' => 3, 'created_at' => now()],
        ]);

        $user = new User();
        $user->id = 2;
        $user->setRelation('roles', collect([(object) ['name' => 'stock_bahan']]));
        $this->be($user);

        $arsip = app(AmbilBarangService::class)->arsip();
        $this->assertSame(1, $arsip->total());
        $this->assertSame(1, $arsip->items()[0]['cabang_id']);
        $this->assertSame(2, $arsip->items()[0]['total_produk']);
    }

    public function test_tombol_opname_di_header_kelompok_memuat_beberapa_produk_di_modal(): void
    {
        $html = File::get(resource_path('views/admin/ambilBarang/index.blade.php'));

        $this->assertSame(1, substr_count($html, "route('ambilBarang.opname')"));
        $this->assertSame(1, substr_count($html, 'data-bs-target="#ambilBarangOpnameModal"'));
        $this->assertStringContainsString('data-bs-target="#ambilBarangOpnameModal"', $html);
        $this->assertStringContainsString('id="ambilBarangOpnameModal"', $html);
        $this->assertStringContainsString('data-cabang-id="{{ $cabangId }}" data-cabang-nama="{{ $cabangNama }}" data-kategori="{{ $kategori }}"', $html);
        $this->assertStringContainsString('row.dataset.cabangId !== cabangId || row.dataset.kategori !== kategori', $html);
        $this->assertStringContainsString('ambil-barang-opname-list { max-height:', $html);
        $this->assertStringContainsString('if (row.hidden) {', $html);
        $this->assertStringContainsString('name="items[{{ $indexOpname }}][stok_fisik]"', $html);
        $this->assertStringContainsString('name="items[{{ $indexOpname }}][pilih]"', $html);
        $this->assertStringNotContainsString('Shipping', $html);
        $this->assertStringContainsString('rowspan="{{ $group[\'items\']->count() }}"', $html);
        $this->assertStringContainsString('ambil-barang-produk-pilih', $html);
        $this->assertStringContainsString('input.dataset.produkId === productInput.dataset.produkId', $html);
        $this->assertStringContainsString('class="popup text-decoration-none fw-semibold"', $html);
        $this->assertStringContainsString("@include('admin.orders.partials.detail-order-modal')", $html);
        $this->assertStringContainsString("@include('admin.projectmps.partials.detail-projectmp-modal')", $html);
        $this->assertStringNotContainsString('Total produk', $html);
        $this->assertStringContainsString("@if (\$kategori !== 'Kosong di sistem')", $html);
        $this->assertStringContainsString('Penandaan tanpa potong stok', $html);
    }

    public function test_produk_stok_kosong_masuk_kelompok_opname_tanpa_pengambilan(): void
    {
        DB::table('produk_stoks')->where('produk_id', 1)->update(['tambah' => 0]);
        Gate::shouldReceive('allows')->once()->with('produk_stok_access')->andReturn(true);

        $data = app(AmbilBarangController::class)->index(Request::create('/admin/ambil-barang'), app(AmbilBarangService::class))->getData();
        $this->assertTrue($data['cabangKelompok'][1]->has('Kosong di sistem'));
        $this->assertFalse($data['cabangKelompok'][1]->has('Stok kurang'));
    }

    public function test_boleh_ambil_sebagian_order_dan_tidak_boleh_dua_kali(): void
    {
        $service = app(AmbilBarangService::class);
        $this->assertSame(1, $service->ambil(['order:1']));
        $this->assertSame(2, DB::table('produk_stoks')->sum(DB::raw('tambah - kurang')));
        $this->assertSame('project:1', $service->kebutuhan()->sole()['items']->sole()['key']);

        $this->expectException(ValidationException::class);
        $service->ambil(['order:1']);
    }

    public function test_opname_beberapa_produk_sekaligus_dengan_satu_keterangan(): void
    {
        DB::table('produk_models')->insert(['id' => 3, 'kategori_id' => 1, 'nama' => 'Barang Kedua', 'stok' => 1]);
        DB::table('produks')->insert(['id' => 3, 'produk_model_id' => 3, 'nama' => 'C']);
        DB::table('produk_stoks')->insert(['produk_id' => 3, 'cabang_id' => 1, 'tambah' => 5, 'kurang' => 0, 'created_at' => now()]);

        Gate::shouldReceive('allows')->once()->with('produk_stok_access')->andReturn(true);
        Gate::shouldReceive('allows')->once()->with('opname_access')->andReturn(true);

        $request = Request::create('/admin/ambil-barang/opname', 'POST', [
            'items' => [
                ['produk_id' => 1, 'cabang_id' => 1, 'pilih' => 1, 'stok_fisik' => 9],
                ['produk_id' => 3, 'cabang_id' => 1, 'pilih' => 1, 'stok_fisik' => 2],
                ['produk_id' => 2, 'cabang_id' => 1],
            ],
            'keterangan' => 'Hasil hitung fisik',
        ]);
        app(AmbilBarangController::class)->opname($request, app(\App\Services\StokService::class));

        $this->assertDatabaseHas('produk_stoks', ['produk_id' => 1, 'kode' => 'opn', 'tambah' => 3]);
        $this->assertDatabaseHas('produk_stoks', ['produk_id' => 3, 'kode' => 'opn', 'kurang' => 3]);
        $this->assertDatabaseCount('produk_stoks', 4);
    }

    public function test_stok_tidak_cukup_membatalkan_seluruh_pengambilan(): void
    {
        try {
            app(AmbilBarangService::class)->ambil(['order:1', 'project:1']);
            $this->fail('Stok seharusnya tidak cukup.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseCount('ambil_barangs', 0);
            $this->assertDatabaseCount('produk_stoks', 1);
        }
    }

    public function test_migrasi_menandai_mutasi_lama_tanpa_memotong_stok_ulang(): void
    {
        DB::table('produk_stoks')->insert([
            'produk_id' => 1, 'cabang_id' => 1, 'tambah' => 0, 'kurang' => 5,
            'kode' => 'shp', 'detail_id' => 1, 'created_at' => now(),
        ]);
        DB::table('orders')->insert(['id' => 2, 'kontak_id' => 1, 'cabang_id' => 1, 'nota' => 'OFF2']);
        DB::table('order_details')->insert(['id' => 3, 'order_id' => 2, 'produk_id' => 1, 'jumlah' => 3, 'produksi_id' => 1]);
        DB::table('produk_stoks')->insert([
            'produk_id' => 1, 'cabang_id' => 1, 'tambah' => 0, 'kurang' => 3,
            'kode' => 'jual', 'detail_id' => 2, 'created_at' => now(),
        ]);

        Schema::drop('ambil_barangs');
        Schema::drop('cek_stok_kosongs');
        $migration = require database_path('migrations/2026_11_16_000001_create_ambil_barangs_table.php');
        $migration->up();

        $this->assertDatabaseHas('ambil_barangs', ['sumber' => 'project', 'detail_id' => 1, 'jumlah' => 5]);
        $this->assertDatabaseHas('ambil_barangs', ['sumber' => 'order', 'detail_id' => 3, 'jumlah' => 3]);
        $this->assertDatabaseMissing('ambil_barangs', ['sumber' => 'order', 'detail_id' => 1]);
        $this->assertDatabaseCount('produk_stoks', 3);

        $archiveMigration = require database_path('migrations/2026_11_16_000002_add_archive_fields_to_ambil_barangs.php');
        $archiveMigration->up();
        $this->assertDatabaseHas('ambil_barangs', ['sumber' => 'order', 'detail_id' => 3, 'nota' => 'OFF2']);
        $this->assertDatabaseHas('ambil_barangs', ['sumber' => 'project', 'detail_id' => 1, 'nota' => 'MP1']);
    }

    public function test_marketplace_hanya_ditandai_jika_mutasi_shp_sudah_ada(): void
    {
        DB::table('orders')->where('id', 1)->update(['created_at' => now()->subDays(30)]);
        DB::table('project_mps')->where('id', 1)->update(['created_at' => now()->subDays(8)]);
        DB::table('project_mps')->insert([
            ['id' => 2, 'cabang_id' => 1, 'nota' => 'MP2', 'created_at' => now()->subDays(2)],
            ['id' => 3, 'cabang_id' => 1, 'nota' => 'MP3', 'created_at' => now()->subDays(2)],
        ]);
        DB::table('project_mp_details')->insert([
            ['id' => 2, 'project_id' => 2, 'produk_id' => 1, 'jumlah' => 2, 'produksi_id' => 1],
            ['id' => 3, 'project_id' => 3, 'produk_id' => 1, 'jumlah' => 2, 'produksi_id' => 1],
        ]);
        DB::table('produk_stoks')->insert([
            'produk_id' => 1, 'cabang_id' => 1, 'tambah' => 0, 'kurang' => 2,
            'kode' => 'shp', 'detail_id' => 3, 'created_at' => now(),
        ]);

        Schema::drop('ambil_barangs');
        Schema::drop('cek_stok_kosongs');
        $migration = require database_path('migrations/2026_11_16_000001_create_ambil_barangs_table.php');
        $migration->up();

        $this->assertDatabaseMissing('ambil_barangs', ['sumber' => 'project', 'detail_id' => 1]);
        $this->assertDatabaseMissing('ambil_barangs', ['sumber' => 'project', 'detail_id' => 2]);
        $this->assertDatabaseHas('ambil_barangs', ['sumber' => 'project', 'detail_id' => 3]);
        $this->assertDatabaseMissing('ambil_barangs', ['sumber' => 'order', 'detail_id' => 1]);
        $this->assertDatabaseCount('produk_stoks', 2);

        $sql = File::get(database_path('sql/2026_11_16_ambil_barang.sql'));
        $this->assertStringNotContainsString('@batas_marketplace', $sql);
        $this->assertStringContainsString('stok.total_mutasi >= kebutuhan.total_detail', $sql);
    }

    public function test_mutasi_lama_yang_ambigu_ditolak_agar_tidak_terpotong_dua_kali(): void
    {
        DB::table('produk_stoks')->insert([
            'produk_id' => 1, 'cabang_id' => 1, 'tambah' => 0, 'kurang' => 4,
            'kode' => 'jual', 'detail_id' => 1,
            'keterangan' => 'barang dijual ke Pembeli', 'created_at' => now(),
        ]);

        try {
            app(AmbilBarangService::class)->ambil(['order:1']);
            $this->fail('Mutasi lama seharusnya perlu verifikasi.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseCount('ambil_barangs', 0);
            $this->assertDatabaseCount('produk_stoks', 2);
        }
    }

    public function test_order_offline_finish_dan_batal_tidak_masuk_permintaan_atau_bisa_dipotong_lagi(): void
    {
        DB::table('produksis')->insert([
            ['id' => 2, 'nama' => 'finish', 'grup' => 'Selesai'],
            ['id' => 3, 'nama' => 'batal', 'grup' => 'batal'],
        ]);
        DB::table('orders')->insert(['id' => 2, 'kontak_id' => 1, 'cabang_id' => 1, 'nota' => 'OFF2']);
        DB::table('order_details')->insert([
            ['id' => 3, 'order_id' => 1, 'produk_id' => 1, 'jumlah' => 1, 'produksi_id' => 2],
            ['id' => 4, 'order_id' => 2, 'produk_id' => 1, 'jumlah' => 1, 'produksi_id' => 3],
        ]);

        $service = app(AmbilBarangService::class);
        $this->assertEqualsCanonicalizing(['order:1', 'project:1'], $service->kebutuhan()->sole()['items']->pluck('key')->all());
        foreach (['order:3', 'order:4'] as $key) {
            try {
                $service->ambil([$key]);
                $this->fail('Order finish/batal tidak boleh memotong stok.');
            } catch (ValidationException $exception) {
                $this->assertDatabaseCount('ambil_barangs', 0);
                $this->assertDatabaseCount('produk_stoks', 1);
            }
        }

        $sql = File::get(database_path('sql/2026_11_17_tandai_order_offline_finish_batal.sql'));
        $this->assertStringContainsString("LOWER(TRIM(ps.nama)) IN ('finish', 'batal')", $sql);
        $this->assertStringContainsString('o.marketplace IS NULL', $sql);
        $this->assertStringNotContainsString('INSERT INTO produk_stoks', $sql);
    }
}
