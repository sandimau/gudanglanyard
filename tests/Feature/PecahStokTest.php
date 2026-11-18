<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PecahStokController;
use App\Models\User;
use App\Services\StokService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PecahStokTest extends TestCase
{
    private int $gudangId;
    private int $produkAsalId;
    private int $produkTujuanId;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('app.url', 'http://localhost');
        config()->set('logging.default', 'errorlog');
        URL::forceRootUrl('http://localhost');
        DB::purge('sqlite');
        $this->withoutMiddleware();
        $this->withSession([]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->nullable();
            $table->boolean('status');
            $table->timestamps();
        });
        Schema::create('produk_kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('produk_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('nama');
            $table->string('satuan');
            $table->boolean('stok');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_model_id');
            $table->string('nama');
            $table->boolean('status');
            $table->integer('hpp')->nullable();
            $table->timestamps();
        });
        Schema::create('produk_stoks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('cabang_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('detail_id')->nullable();
            $table->integer('tambah');
            $table->integer('kurang');
            $table->integer('hpp')->nullable();
            $table->string('kode');
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('produk_last_stoks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('cabang_id');
            $table->integer('tahun');
            $table->integer('saldo');
            $table->timestamps();
            $table->unique(['produk_id', 'cabang_id', 'tahun']);
        });
        Schema::create('shopee_stock_syncs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id')->unique();
            $table->timestamp('dirty_at')->nullable();
            $table->text('last_error')->nullable();
            $table->text('synced_marketplaces')->nullable();
            $table->timestamps();
        });

        $kategoriId = DB::table('produk_kategoris')->insertGetId(['nama' => 'Lanyard']);
        $this->gudangId = DB::table('cabangs')->insertGetId(['nama' => 'Pusat', 'kode' => 'PUSAT', 'status' => 1]);

        $modelAsalId = DB::table('produk_models')->insertGetId([
            'kategori_id' => $kategoriId,
            'nama' => 'Lanyard Roll',
            'satuan' => 'Roll',
            'stok' => 1,
        ]);
        $modelTujuanId = DB::table('produk_models')->insertGetId([
            'kategori_id' => $kategoriId,
            'nama' => 'Lanyard Polos',
            'satuan' => 'Pcs',
            'stok' => 1,
        ]);

        $this->produkAsalId = DB::table('produks')->insertGetId([
            'produk_model_id' => $modelAsalId,
            'nama' => 'Hitam',
            'status' => 1,
            'hpp' => 5000,
        ]);
        $this->produkTujuanId = DB::table('produks')->insertGetId([
            'produk_model_id' => $modelTujuanId,
            'nama' => 'Hitam',
            'status' => 1,
            'hpp' => 0,
        ]);

        DB::table('produk_stoks')->insert([
            'produk_id' => $this->produkAsalId,
            'cabang_id' => $this->gudangId,
            'tambah' => 10,
            'kurang' => 0,
            'kode' => 'opn',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::create([
            'name' => 'Petugas',
            'email' => 'petugas@example.test',
            'password' => 'secret',
        ]));

        foreach (['super', 'manager', 'Keuangan', 'stock_bahan', 'admin', 'user'] as $role) {
            DB::table('roles')->insert(['name' => $role, 'guard_name' => 'web']);
        }
        $this->switchRole('super');
    }

    public function test_splits_roll_stock_into_unit_stock_and_moves_hpp(): void
    {
        $this->post(route('pecahStok.store'), $this->payload([
            'jumlah_asal' => 2,
            'rasio' => 100,
        ]))->assertRedirect(route('pecahStok.index'));

        $stok = app(StokService::class);
        $this->assertSame(8, $stok->saldoTersedia($this->produkAsalId, $this->gudangId));
        $this->assertSame(200, $stok->saldoTersedia($this->produkTujuanId, $this->gudangId));

        $keluar = DB::table('produk_stoks')
            ->where('kode', 'pch')
            ->where('produk_id', $this->produkAsalId)
            ->where('kurang', 2)
            ->first();

        $this->assertNotNull($keluar);
        $this->assertSame(5000, (int) $keluar->hpp);

        $this->assertDatabaseHas('produk_stoks', [
            'detail_id' => $keluar->id,
            'produk_id' => $this->produkTujuanId,
            'cabang_id' => $this->gudangId,
            'tambah' => 200,
            'hpp' => 50,
        ]);

        // 2 roll x 5.000 = 10.000 dialihkan ke 200 pcs => 50 per pcs.
        $this->assertSame(50, (int) DB::table('produks')->where('id', $this->produkTujuanId)->value('hpp'));
    }

    public function test_target_hpp_uses_weighted_average_with_existing_stock(): void
    {
        DB::table('produks')->where('id', $this->produkTujuanId)->update(['hpp' => 40]);
        DB::table('produk_stoks')->insert([
            'produk_id' => $this->produkTujuanId,
            'cabang_id' => $this->gudangId,
            'tambah' => 100,
            'kurang' => 0,
            'kode' => 'opn',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('pecahStok.store'), $this->payload([
            'jumlah_asal' => 1,
            'rasio' => 100,
        ]))->assertRedirect(route('pecahStok.index'));

        // ((100 x 40) + (100 x 50)) / 200 = 45
        $this->assertSame(45, (int) DB::table('produks')->where('id', $this->produkTujuanId)->value('hpp'));
        $this->assertSame(200, app(StokService::class)->saldoTersedia($this->produkTujuanId, $this->gudangId));
    }

    public function test_insufficient_source_stock_rejects_whole_split(): void
    {
        $this->post(route('pecahStok.store'), $this->payload([
            'jumlah_asal' => 11,
            'rasio' => 100,
        ]))->assertSessionHasErrors('jumlah_asal');

        $stok = app(StokService::class);
        $this->assertSame(10, $stok->saldoTersedia($this->produkAsalId, $this->gudangId));
        $this->assertSame(0, $stok->saldoTersedia($this->produkTujuanId, $this->gudangId));
        $this->assertDatabaseCount('produk_stoks', 1);
    }

    public function test_rejects_same_product_zero_ratio_and_oversized_result(): void
    {
        $payload = $this->payload(['produk_tujuan_id' => $this->produkAsalId]);
        $this->post(route('pecahStok.store'), $payload)->assertSessionHasErrors('produk_asal_id');

        $this->post(route('pecahStok.store'), $this->payload(['rasio' => 0]))
            ->assertSessionHasErrors('rasio');

        $this->post(route('pecahStok.store'), $this->payload([
            'jumlah_asal' => 2,
            'rasio' => 600000,
        ]))->assertSessionHasErrors('rasio');

        $this->assertDatabaseCount('produk_stoks', 1);
    }

    public function test_blade_views_compile(): void
    {
        $compiler = app('blade.compiler');

        foreach ([
            'admin/produkStoks/pecahStok.blade.php',
            'admin/produkStoks/partials/pecahStokForm.blade.php',
        ] as $view) {
            $compiled = $compiler->compileString(file_get_contents(resource_path('views/' . $view)));
            $this->assertStringContainsString('<?php', $compiled);
        }
    }

    public function test_every_role_can_view_history_but_only_four_roles_can_submit(): void
    {
        $payload = $this->payload();

        foreach (['admin', 'user'] as $role) {
            $this->switchRole($role);
            $page = app(PecahStokController::class)->index();
            $this->assertSame('admin.produkStoks.pecahStok', $page->name());
            $this->assertFalse($page->getData()['bolehInput']);
            $this->post(route('pecahStok.store'), $payload)->assertForbidden();
        }

        foreach (['super', 'manager', 'Keuangan', 'stock_bahan'] as $role) {
            $this->switchRole($role);
            $this->assertTrue(app(PecahStokController::class)->index()->getData()['bolehInput']);
            $this->postJson(route('pecahStok.store'), [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('gudang_id');
        }

        $this->assertDatabaseCount('produk_stoks', 1);
    }

    private function switchRole(string $role): void
    {
        $user = auth()->user();
        DB::table('model_has_roles')->where('model_id', $user->id)->delete();
        DB::table('model_has_roles')->insert([
            'role_id' => DB::table('roles')->where('name', $role)->value('id'),
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
        $user->unsetRelation('roles');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'gudang_id' => $this->gudangId,
            'produk_asal_id' => $this->produkAsalId,
            'produk_tujuan_id' => $this->produkTujuanId,
            'jumlah_asal' => 1,
            'rasio' => 100,
        ], $overrides);
    }
}
