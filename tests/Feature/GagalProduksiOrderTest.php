<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GagalProduksiOrderTest extends TestCase
{
    private array $deniedPermissions = [];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->withoutMiddleware(\App\Http\Middleware\LoadSistemConfig::class);
        $this->withoutMiddleware(\App\Http\Middleware\EnsureCabangAktif::class);
        $this->withoutMiddleware(\App\Http\Middleware\RestrictKontenRole::class);

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id');
            $table->string('nota')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('produksi_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('project_mps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();
        });
        Schema::create('project_mp_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('produksi_id')->nullable();
            $table->timestamps();
        });
        Schema::create('produk_kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->softDeletes();
        });
        Schema::create('produk_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->string('nama');
            $table->softDeletes();
        });
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_model_id');
            $table->string('nama');
            $table->unsignedInteger('hpp');
        });
        Schema::create('gagal_produksis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_detail_id')->nullable();
            $table->unsignedBigInteger('project_mp_id')->nullable();
            $table->unsignedBigInteger('project_mp_detail_id')->nullable();
            $table->unsignedBigInteger('produk_id');
            $table->string('nama_produk');
            $table->unsignedBigInteger('cabang_id');
            $table->date('tanggal');
            $table->unsignedInteger('jumlah');
            $table->unsignedBigInteger('hpp_satuan');
            $table->unsignedBigInteger('kerugian');
            $table->string('tahap')->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        $this->withSession(['cabang_id' => 'all']);
        $user = new User();
        $user->id = 1;
        $user->setRelation('roles', collect([(object) ['name' => 'super']]));
        $this->actingAs($user);
        Gate::shouldReceive('denies')->andReturnUsing(fn ($ability) => in_array($ability, $this->deniedPermissions, true));
    }

    private function produk(): int
    {
        $kategori = DB::table('produk_kategoris')->insertGetId(['nama' => 'Lanyard']);
        $model = DB::table('produk_models')->insertGetId(['kategori_id' => $kategori, 'nama' => 'Custom']);

        return DB::table('produks')->insertGetId(['produk_model_id' => $model, 'nama' => 'Merah', 'hpp' => 2500]);
    }

    public function test_index_filters_inclusive_date_range_and_totals(): void
    {
        foreach ([
            ['tanggal' => '2026-11-03', 'nama_produk' => 'Sebelum', 'jumlah' => 1, 'kerugian' => 1000],
            ['tanggal' => '2026-11-04', 'nama_produk' => 'Awal', 'jumlah' => 2, 'kerugian' => 2000],
            ['tanggal' => '2026-11-05', 'nama_produk' => 'Akhir', 'jumlah' => 3, 'kerugian' => 3000],
            ['tanggal' => '2026-11-06', 'nama_produk' => 'Sesudah', 'jumlah' => 4, 'kerugian' => 4000],
        ] as $record) {
            DB::table('gagal_produksis')->insert($record + [
                'produk_id' => 1, 'cabang_id' => 1, 'hpp_satuan' => 1000,
            ]);
        }

        Gate::shouldReceive('forUser')->andReturnSelf();
        Gate::shouldReceive('check')->andReturn(true);
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });

        $response = $this->get('/admin/gagal-produksi?tanggal_awal=2026-11-04&tanggal_akhir=2026-11-05');

        $response->assertOk()
            ->assertViewHas('totalGagal', 5)
            ->assertViewHas('totalKerugian', 5000)
            ->assertSee('Awal')
            ->assertSee('Akhir')
            ->assertDontSee('Sebelum')
            ->assertDontSee('Sesudah');

        $this->get('/admin/gagal-produksi?tanggal_awal=2026-11-06&tanggal_akhir=2026-11-04')
            ->assertSessionHasErrors('tanggal_akhir');
    }

    public function test_offline_record_uses_only_details_from_its_order_and_snapshots_hpp(): void
    {
        $produk = $this->produk();
        $order = DB::table('orders')->insertGetId(['cabang_id' => 1, 'nota' => 'A']);
        $other = DB::table('orders')->insertGetId(['cabang_id' => 1, 'nota' => 'B']);
        $detail = DB::table('order_details')->insertGetId(['order_id' => $order, 'produk_id' => $produk]);
        $otherDetail = DB::table('order_details')->insertGetId(['order_id' => $other, 'produk_id' => $produk]);

        $this->post("/admin/order/{$order}/gagal-produksi", [
            'order_detail_id' => $otherDetail, 'tanggal' => '2026-11-04', 'jumlah' => 2,
        ])->assertNotFound();
        $this->assertSame(0, DB::table('gagal_produksis')->count());

        $this->post("/admin/order/{$order}/gagal-produksi", [
            'order_detail_id' => $detail, 'tanggal' => '2026-11-04', 'jumlah' => 2,
        ])->assertRedirect();
        $this->assertDatabaseHas('gagal_produksis', [
            'order_detail_id' => $detail, 'produk_id' => $produk, 'hpp_satuan' => 2500, 'kerugian' => 5000,
        ]);
    }

    public function test_online_record_rejects_product_from_another_project(): void
    {
        $produk = $this->produk();
        $project = DB::table('project_mps')->insertGetId(['cabang_id' => 1]);
        $other = DB::table('project_mps')->insertGetId(['cabang_id' => 1]);
        $detail = DB::table('project_mp_details')->insertGetId(['project_id' => $project, 'produk_id' => $produk]);
        $otherDetail = DB::table('project_mp_details')->insertGetId(['project_id' => $other, 'produk_id' => $produk]);

        $this->post("/admin/projectMp/{$project}/gagal-produksi", [
            'sumber' => 'project:' . $otherDetail, 'tanggal' => '2026-11-04', 'jumlah' => 1,
        ])->assertNotFound();

        $this->post("/admin/projectMp/{$project}/gagal-produksi", [
            'sumber' => 'project:' . $detail, 'tanggal' => '2026-11-04', 'jumlah' => 3,
        ])->assertRedirect();
        $this->assertDatabaseHas('gagal_produksis', [
            'project_mp_id' => $project, 'project_mp_detail_id' => $detail, 'hpp_satuan' => 2500, 'kerugian' => 7500,
        ]);
    }

    public function test_online_record_accepts_only_additional_offline_products_on_linked_order(): void
    {
        $produk = $this->produk();
        $order = DB::table('orders')->insertGetId(['cabang_id' => 2, 'nota' => 'Tambahan']);
        $other = DB::table('orders')->insertGetId(['cabang_id' => 2, 'nota' => 'Lain']);
        $project = DB::table('project_mps')->insertGetId(['cabang_id' => 1, 'order_id' => $order]);
        $detail = DB::table('order_details')->insertGetId(['order_id' => $order, 'produk_id' => $produk]);
        $otherDetail = DB::table('order_details')->insertGetId(['order_id' => $other, 'produk_id' => $produk]);

        $this->post("/admin/projectMp/{$project}/gagal-produksi", [
            'sumber' => 'order:' . $otherDetail, 'tanggal' => '2026-11-04', 'jumlah' => 1,
        ])->assertNotFound();

        $this->post("/admin/projectMp/{$project}/gagal-produksi", [
            'sumber' => 'order:' . $detail, 'tanggal' => '2026-11-04', 'jumlah' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('gagal_produksis', [
            'project_mp_id' => $project, 'order_detail_id' => $detail, 'cabang_id' => 1, 'kerugian' => 2500,
        ]);
    }

    public function test_access_permission_is_required_to_view_and_write_failures(): void
    {
        $this->deniedPermissions = ['gagal_produksi_access'];
        $this->get('/admin/gagal-produksi')->assertForbidden();

        $order = DB::table('orders')->insertGetId(['cabang_id' => 1]);
        $this->post("/admin/order/{$order}/gagal-produksi", [
            'order_detail_id' => 1, 'tanggal' => '2026-11-04', 'jumlah' => 1,
        ])->assertForbidden();
        $this->assertSame(0, DB::table('gagal_produksis')->count());
    }

    public function test_manage_permission_is_required_to_record_failures(): void
    {
        $this->deniedPermissions = ['gagal_produksi_manage'];
        $project = DB::table('project_mps')->insertGetId(['cabang_id' => 1]);
        $this->post("/admin/projectMp/{$project}/gagal-produksi", [
            'sumber' => 'project:1', 'tanggal' => '2026-11-04', 'jumlah' => 1,
        ])->assertForbidden();
        $this->assertSame(0, DB::table('gagal_produksis')->count());
    }
}
