<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BelanjaController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StockBahanBelanjaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
        require_once base_path('database/migrations/2023_06_27_094613_create_permission_tables.php');
        (new \CreatePermissionTables())->up();
    }

    public function test_stock_bahan_melihat_hanya_belanja_di_menu_keuangan_dan_bisa_membuka_daftar(): void
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'stock_bahan', 'guard_name' => 'web']);
        $user = User::create(['name' => 'Stok Bahan', 'email' => 'stok@example.test', 'password' => 'secret']);
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId, 'model_type' => User::class, 'model_id' => $user->id,
        ]);
        $this->actingAs($user);

        $navigation = view('layouts.navigation')->render();
        $this->assertSame(1, preg_match('/Keuangan\s*<\/a>\s*<ul class="nav-group-items">(.*?)<\/ul>/s', $navigation, $matches));
        $keuangan = $matches[1];
        $this->assertStringContainsString(route('belanja.index'), $keuangan);
        $this->assertStringContainsString('Belanja', $keuangan);
        $this->assertStringNotContainsString('Pembayaran', $keuangan);
        $this->assertStringNotContainsString('Hutang/Piutang', $keuangan);

        Schema::create('belanjas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });
        $this->assertSame('admin.belanjas.index', app(BelanjaController::class)->index(Request::create('/admin/belanja'))->name());
    }
}
