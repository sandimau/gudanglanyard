<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrderProdukStockSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
        DB::purge('sqlite');
        $this->withoutMiddleware();

        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode');
            $table->boolean('status');
        });
        Schema::create('kontaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id');
            $table->string('nama')->nullable();
            $table->string('noTelp')->nullable();
            $table->string('email')->nullable();
            $table->boolean('konsumen')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('produk_kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
        });
        Schema::create('produk_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->string('nama');
            $table->integer('harga');
            $table->boolean('jual');
            $table->boolean('produksi')->default(false);
            $table->boolean('stok')->default(true);
            $table->softDeletes();
        });
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_model_id');
            $table->string('nama');
            $table->boolean('status');
        });
        Schema::create('produk_stoks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('cabang_id');
            $table->integer('tambah');
            $table->integer('kurang');
            $table->timestamp('created_at');
            $table->softDeletes();
        });
        Schema::create('produk_last_stoks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('cabang_id');
            $table->integer('tahun');
            $table->integer('saldo');
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kontak_id')->nullable();
            $table->unsignedBigInteger('cabang_id');
            $table->integer('total')->nullable();
            $table->string('jasa')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('ongkir')->nullable();
            $table->string('pengiriman')->nullable();
            $table->string('invoice')->nullable();
            $table->string('jenis_pembayaran')->nullable();
            $table->text('ket_kirim')->nullable();
            $table->date('deathline')->nullable();
            $table->string('nota')->nullable();
            $table->string('marketplace')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('produksi_id')->nullable();
            $table->string('tema')->nullable();
            $table->integer('jumlah');
            $table->integer('harga');
            $table->integer('hpp')->nullable();
            $table->text('keterangan')->nullable();
            $table->date('deathline')->nullable();
            $table->string('nota')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('produksis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('grup');
            $table->integer('urutan')->nullable();
            $table->softDeletes();
        });
        Schema::create('speks', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->softDeletes();
        });
        Schema::create('order_speks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_detail_id');
            $table->unsignedBigInteger('spek_id');
            $table->string('keterangan')->nullable();
        });
        Schema::create('project_mps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('nota')->nullable();
            $table->string('konsumen')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamps();
        });
    }

    public function test_additional_offline_order_uses_linked_order_branch_and_rejects_zero_stock(): void
    {
        $pusat = DB::table('cabangs')->insertGetId(['nama' => 'Pusat', 'kode' => 'PUSAT', 'status' => 1]);
        $cabang = DB::table('cabangs')->insertGetId(['nama' => 'Cabang', 'kode' => 'CAB', 'status' => 1]);
        $this->withSession(['cabang_id' => $pusat]);
        $kategori = DB::table('produk_kategoris')->insertGetId(['nama' => 'Lanyard']);
        $model = DB::table('produk_models')->insertGetId([
            'kategori_id' => $kategori, 'nama' => 'Polos', 'harga' => 1000, 'jual' => 1,
        ]);
        $stokPusat = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Merah', 'status' => 1]);
        $stokCabang = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Biru', 'status' => 1]);
        $habis = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Hijau', 'status' => 1]);
        $this->mutasi($stokPusat, $pusat, 3, 0);
        $this->mutasi($stokCabang, $cabang, 2, 0);
        $this->mutasi($habis, $cabang, 1, 1);

        $orderId = DB::table('orders')->insertGetId(['cabang_id' => $cabang]);
        $linkedProjectId = DB::table('project_mps')->insertGetId(['cabang_id' => $pusat, 'order_id' => $orderId]);
        $newProjectId = DB::table('project_mps')->insertGetId(['cabang_id' => $pusat]);

        $linkedResult = $this->getJson('/admin/produk/api?q=Lanyard&stok_tersedia=1&project_mp_id=' . $linkedProjectId)->assertOk();
        $this->assertSame([$stokCabang], collect($linkedResult->json())->pluck('id')->all());

        $newResult = $this->getJson('/admin/produk/api?q=Lanyard&stok_tersedia=1&project_mp_id=' . $newProjectId)->assertOk();
        $this->assertSame([$stokPusat], collect($newResult->json())->pluck('id')->all());

        $user = new User();
        $user->setRelation('roles', collect([(object) ['name' => 'super']]));
        $this->actingAs($user);

        $this->postJson('/admin/projectMpOrder/create', [
            'project_mp_id' => $linkedProjectId,
            'produk_id' => $stokPusat,
            'harga' => 1000,
            'jumlah' => 1,
            'deathline' => date('Y-m-d'),
        ])->assertUnprocessable()->assertJsonValidationErrors('produk_id');
    }

    public function test_order_search_only_shows_saleable_products_with_positive_stock_in_the_customers_branch(): void
    {
        $pusat = DB::table('cabangs')->insertGetId(['nama' => 'Pusat', 'kode' => 'PUSAT', 'status' => 1]);
        $cabang = DB::table('cabangs')->insertGetId(['nama' => 'Cabang', 'kode' => 'CAB', 'status' => 1]);
        $this->withSession(['cabang_id' => $pusat]);
        $kontak = DB::table('kontaks')->insertGetId(['cabang_id' => $cabang]);
        $kategori = DB::table('produk_kategoris')->insertGetId(['nama' => 'Lanyard']);
        $model = DB::table('produk_models')->insertGetId([
            'kategori_id' => $kategori, 'nama' => 'Polos', 'harga' => 1000, 'jual' => 1,
        ]);

        $tersedia = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Merah', 'status' => 1]);
        $habis = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Biru', 'status' => 1]);
        $stokCabang = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Hijau', 'status' => 1]);
        $saldoLalu = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Kuning', 'status' => 1]);
        $nonaktif = DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Hitam', 'status' => 0]);

        $this->mutasi($tersedia, $pusat, 5, 3);
        $this->mutasi($habis, $pusat, 4, 4);
        $this->mutasi($stokCabang, $cabang, 3, 0);
        $this->mutasi($saldoLalu, $pusat, 0, 1);
        $this->mutasi($nonaktif, $pusat, 2, 0);
        DB::table('produk_last_stoks')->insert([
            'produk_id' => $saldoLalu, 'cabang_id' => $pusat,
            'tahun' => (int) date('Y') - 1, 'saldo' => 3,
        ]);

        $pusatResult = $this->getJson('/admin/produk/api?q=Lanyard&stok_tersedia=1')->assertOk();
        $this->assertEqualsCanonicalizing([$tersedia, $saldoLalu], collect($pusatResult->json())->pluck('id')->all());

        $cabangResult = $this->getJson('/admin/produk/api?q=Lanyard&stok_tersedia=1&kontak_id=' . $kontak)->assertOk();
        $this->assertSame([$stokCabang], collect($cabangResult->json())->pluck('id')->all());

        $normalResult = $this->getJson('/admin/produk/api?q=Lanyard')->assertOk();
        $this->assertEqualsCanonicalizing(
            [$tersedia, $habis, $stokCabang, $saldoLalu],
            collect($normalResult->json())->pluck('id')->all()
        );

        $this->postJson('/admin/order', [
            'kontak_id' => $kontak,
            'produk_id' => $tersedia,
            'harga' => 1000,
            'jumlah' => 1,
            'deathline' => date('Y-m-d'),
        ])->assertUnprocessable()->assertJsonValidationErrors('produk_id');
    }

    public function test_new_order_can_find_and_save_made_to_order_sublim_without_stock(): void
    {
        $pusat = DB::table('cabangs')->insertGetId(['nama' => 'Pusat', 'kode' => 'PUSAT', 'status' => 1]);
        $this->withSession(['cabang_id' => $pusat]);
        $kontak = DB::table('kontaks')->insertGetId(['cabang_id' => $pusat]);
        $kategori = DB::table('produk_kategoris')->insertGetId(['nama' => 'SUBLIM']);
        $sublimModel = DB::table('produk_models')->insertGetId([
            'kategori_id' => $kategori, 'nama' => 'SUBLIM 2CM', 'harga' => 1000,
            'jual' => 1, 'produksi' => 1, 'stok' => 0,
        ]);
        $sublim = DB::table('produks')->insertGetId([
            'produk_model_id' => $sublimModel, 'nama' => 'Cetak', 'status' => 1,
        ]);
        $stokModel = DB::table('produk_models')->insertGetId([
            'kategori_id' => $kategori, 'nama' => 'Barang stok', 'harga' => 1000,
            'jual' => 1, 'produksi' => 0, 'stok' => 1,
        ]);
        $tanpaStok = DB::table('produks')->insertGetId([
            'produk_model_id' => $stokModel, 'nama' => 'Merah', 'status' => 1,
        ]);

        $this->assertSame([], $this->getJson('/admin/produk/api?q=sublim&stok_tersedia=1')->assertOk()->json());
        $result = $this->getJson('/admin/produk/api?q=sublime&stok_tersedia=1&produksi_tanpa_stok=1')->assertOk();
        $this->assertSame([$sublim], collect($result->json())->pluck('id')->all());
        $stokResult = $this->getJson('/admin/produk/api?q=Barang%20stok&stok_tersedia=1&produksi_tanpa_stok=1')->assertOk();
        $this->assertNotContains($tanpaStok, collect($stokResult->json())->pluck('id')->all());

        DB::table('produksis')->insert([
            ['nama' => 'batal', 'grup' => 'batal'],
            ['nama' => 'awal', 'grup' => 'awal'],
        ]);
        $this->post('/admin/order', [
            'kontak_id' => $kontak,
            'produk_id' => $sublim,
            'harga' => 1000,
            'jumlah' => 1,
            'deathline' => date('Y-m-d'),
            'nota' => 'ORDER-SUBLIM',
        ])->assertRedirect();
        $this->assertDatabaseHas('orders', ['nota' => 'ORDER-SUBLIM', 'cabang_id' => $pusat]);
        $this->assertDatabaseHas('order_details', ['produk_id' => $sublim, 'nota' => 'ORDER-SUBLIM']);
    }

    public function test_project_add_order_can_find_and_save_made_to_order_sublim_without_stock(): void
    {
        $cabang = DB::table('cabangs')->insertGetId(['nama' => 'Pusat', 'kode' => 'PUSAT', 'status' => 1]);
        $this->withSession(['cabang_id' => $cabang]);
        $kategori = DB::table('produk_kategoris')->insertGetId(['nama' => 'SUBLIM']);
        $sublimModel = DB::table('produk_models')->insertGetId([
            'kategori_id' => $kategori, 'nama' => 'SUBLIM 2CM', 'harga' => 1000,
            'jual' => 1, 'produksi' => 1, 'stok' => 0,
        ]);
        $sublim = DB::table('produks')->insertGetId([
            'produk_model_id' => $sublimModel, 'nama' => 'Cetak', 'status' => 1,
        ]);
        $stokModel = DB::table('produk_models')->insertGetId([
            'kategori_id' => $kategori, 'nama' => 'Barang stok', 'harga' => 1000,
            'jual' => 1, 'produksi' => 0, 'stok' => 1,
        ]);
        $tanpaStok = DB::table('produks')->insertGetId([
            'produk_model_id' => $stokModel, 'nama' => 'Merah', 'status' => 1,
        ]);
        $projectId = DB::table('project_mps')->insertGetId([
            'cabang_id' => $cabang,
            'nota' => 'PROJECT-SUBLIM',
            'konsumen' => 'Pembeli Sublim',
        ]);

        $user = new User();
        $user->setRelation('roles', collect([(object) ['name' => 'super']]));
        $this->actingAs($user);

        $autocomplete = view('admin.orderDetails.partials.produk-autocomplete-standalone', [
            'stockProjectMpId' => $projectId,
        ])->render();
        $this->assertStringContainsString("params.set('produksi_tanpa_stok', '1')", $autocomplete);

        $result = $this->getJson('/admin/produk/api?q=sublim&stok_tersedia=1&produksi_tanpa_stok=1&project_mp_id=' . $projectId)
            ->assertOk();
        $this->assertSame([$sublim], collect($result->json())->pluck('id')->all());

        $this->postJson('/admin/projectMpOrder/create', [
            'project_mp_id' => $projectId,
            'produk_id' => $tanpaStok,
            'harga' => 1000,
            'jumlah' => 1,
            'deathline' => date('Y-m-d'),
        ])->assertUnprocessable()->assertJsonValidationErrors('produk_id');

        DB::table('produksis')->insert([
            ['nama' => 'batal', 'grup' => 'batal'],
            ['nama' => 'awal', 'grup' => 'awal'],
        ]);
        $this->post('/admin/projectMpOrder/create', [
            'project_mp_id' => $projectId,
            'produk_id' => $sublim,
            'harga' => 1000,
            'jumlah' => 1,
            'deathline' => date('Y-m-d'),
        ])->assertRedirect('/admin/projectMpDetail/' . $projectId);

        $orderId = DB::table('project_mps')->where('id', $projectId)->value('order_id');
        $this->assertNotNull($orderId);
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'nota' => 'PROJECT-SUBLIM']);
        $this->assertDatabaseHas('order_details', [
            'order_id' => $orderId,
            'produk_id' => $sublim,
            'nota' => 'PROJECT-SUBLIM',
        ]);
    }

    private function mutasi(int $produkId, int $cabangId, int $tambah, int $kurang): void
    {
        DB::table('produk_stoks')->insert([
            'produk_id' => $produkId,
            'cabang_id' => $cabangId,
            'tambah' => $tambah,
            'kurang' => $kurang,
            'created_at' => now(),
        ]);
    }
}
