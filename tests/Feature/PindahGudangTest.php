<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PindahGudangController;
use App\Models\User;
use App\Services\StokService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PindahGudangTest extends TestCase
{
    private int $asalId;
    private int $tujuanId;
    private array $produkIds;

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
            $table->boolean('status');
            $table->timestamps();
        });
        Schema::create('produk_models', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
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

        $this->asalId = DB::table('cabangs')->insertGetId(['nama' => 'Pusat', 'status' => 1]);
        $this->tujuanId = DB::table('cabangs')->insertGetId(['nama' => 'Cabang', 'status' => 1]);
        $modelId = DB::table('produk_models')->insertGetId(['nama' => 'Lanyard', 'stok' => 1]);
        $this->produkIds = [
            DB::table('produks')->insertGetId(['produk_model_id' => $modelId, 'nama' => 'Merah', 'status' => 1]),
            DB::table('produks')->insertGetId(['produk_model_id' => $modelId, 'nama' => 'Biru', 'status' => 1]),
        ];

        foreach ($this->produkIds as $produkId) {
            DB::table('produk_stoks')->insert([
                'produk_id' => $produkId,
                'cabang_id' => $this->asalId,
                'tambah' => 10,
                'kurang' => 0,
                'kode' => 'opn',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

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

    public function test_moves_multiple_products_and_links_both_stock_entries(): void
    {
        $this->post(route('pindahGudang.store'), $this->payload([
            ['produk_id' => $this->produkIds[0], 'jumlah' => 3],
            ['produk_id' => $this->produkIds[1], 'jumlah' => 4],
            ['produk_id' => '', 'jumlah' => ''],
        ]))->assertRedirect(route('pindahGudang.index'));

        $stok = app(StokService::class);
        $this->assertSame(7, $stok->saldoTersedia($this->produkIds[0], $this->asalId));
        $this->assertSame(3, $stok->saldoTersedia($this->produkIds[0], $this->tujuanId));
        $this->assertSame(6, $stok->saldoTersedia($this->produkIds[1], $this->asalId));
        $this->assertSame(4, $stok->saldoTersedia($this->produkIds[1], $this->tujuanId));
        $this->assertDatabaseCount('produk_stoks', 6);

        $keluar = DB::table('produk_stoks')->where('kode', 'pgh')->where('kurang', 3)->first();
        $this->assertDatabaseHas('produk_stoks', [
            'detail_id' => $keluar->id,
            'produk_id' => $this->produkIds[0],
            'cabang_id' => $this->tujuanId,
            'tambah' => 3,
        ]);
    }

    public function test_insufficient_stock_rejects_entire_transfer(): void
    {
        $this->post(route('pindahGudang.store'), $this->payload([
            ['produk_id' => $this->produkIds[0], 'jumlah' => 2],
            ['produk_id' => $this->produkIds[1], 'jumlah' => 11],
        ]))->assertSessionHasErrors('items.1.jumlah');

        $this->assertDatabaseCount('produk_stoks', 2);
    }

    public function test_rejects_duplicate_products_and_same_warehouse(): void
    {
        $this->post(route('pindahGudang.store'), $this->payload([
            ['produk_id' => $this->produkIds[0], 'jumlah' => 2],
            ['produk_id' => $this->produkIds[0], 'jumlah' => 2],
        ]))->assertSessionHasErrors('items.1.produk_id');

        $payload = $this->payload([['produk_id' => $this->produkIds[0], 'jumlah' => 2]]);
        $payload['gudang_tujuan_id'] = $this->asalId;
        $this->post(route('pindahGudang.store'), $payload)->assertSessionHasErrors('gudang_tujuan_id');

        $this->assertDatabaseCount('produk_stoks', 2);
    }

    public function test_limits_form_to_ten_complete_rows(): void
    {
        $eleven = array_fill(0, 11, ['produk_id' => $this->produkIds[0], 'jumlah' => 1]);
        $this->post(route('pindahGudang.store'), $this->payload($eleven))
            ->assertSessionHasErrors('items');

        $this->post(route('pindahGudang.store'), $this->payload([
            ['produk_id' => $this->produkIds[0], 'jumlah' => ''],
        ]))->assertSessionHasErrors('items.0.jumlah');

        $this->assertDatabaseCount('produk_stoks', 2);
    }

    public function test_every_role_can_view_history_but_only_four_roles_can_submit(): void
    {
        $payload = $this->payload([['produk_id' => $this->produkIds[0], 'jumlah' => 1]]);

        foreach (['admin', 'user'] as $role) {
            $this->switchRole($role);
            $page = app(PindahGudangController::class)->index();
            $this->assertSame('admin.produkStoks.pindahGudang', $page->name());
            $this->assertFalse($page->getData()['bolehInput']);
            $this->post(route('pindahGudang.store'), $payload)->assertForbidden();
        }

        foreach (['super', 'manager', 'Keuangan', 'stock_bahan'] as $role) {
            $this->switchRole($role);
            $this->assertTrue(app(PindahGudangController::class)->index()->getData()['bolehInput']);
            $this->postJson(route('pindahGudang.store'), $this->payload([]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('items');
        }

        $this->assertDatabaseCount('produk_stoks', 2);
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

    private function payload(array $items): array
    {
        return [
            'gudang_asal_id' => $this->asalId,
            'gudang_tujuan_id' => $this->tujuanId,
            'items' => $items,
        ];
    }
}
