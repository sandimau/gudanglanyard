<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\KontenController;
use App\Http\Controllers\Admin\KontenTabController;
use App\Models\Konten;
use App\Models\KontenTab;
use App\Models\User;
use App\Http\Middleware\LoadSistemConfig;
use App\Http\Middleware\EnsureCabangAktif;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class KontenFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('app.url', 'http://localhost');
        config()->set('logging.default', 'errorlog');
        URL::forceRootUrl('http://localhost');
        DB::purge('sqlite');

        $migration = require base_path('database/migrations/2026_10_22_000001_create_konten_tables.php');
        $migration->up();
    }

    public function test_jadwal_dan_proses_memakai_tab_konten_sendiri(): void
    {
        $tabController = app(KontenTabController::class);
        $tabController->store(Request::create('/admin/konten/setup', 'POST', [
            'nama' => 'Ide', 'grup' => 'Perencanaan', 'warna' => '#123456',
        ]));
        $tabAwal = KontenTab::firstOrFail();
        $this->assertSame(1, $tabAwal->urutan);

        $controller = app(KontenController::class);
        $controller->store(Request::create('/admin/konten', 'POST', [
            'tanggal' => '2026-10-22',
            'topik' => 'Video proses cetak',
            'platform' => 'Instagram',
            'waktu_posting' => '15:30',
            'tujuan' => 'Menjelaskan proses produksi',
        ]));

        $konten = Konten::firstOrFail();
        $this->assertSame($tabAwal->id, $konten->konten_tab_id);
        $this->assertSame('Video proses cetak', $controller->jadwal(Request::create('/admin/konten/jadwal', 'GET', [
            'bulan' => '2026-10',
        ]))->getData()['kontens']->get('2026-10-22')->first()->topik);
        $this->assertFalse($controller->jadwal(Request::create('/admin/konten/jadwal', 'GET', [
            'bulan' => '2026-11',
        ]))->getData()['kontens']->has('2026-10-22'));

        $tabBaru = KontenTab::create(['nama' => 'Draft', 'grup' => 'Produksi', 'warna' => '#654321', 'urutan' => 1]);
        $controller->updateTab(Request::create('/admin/konten/'.$konten->id.'/tab', 'PATCH', [
            'konten_tab_id' => $tabBaru->id,
        ]), $konten);
        $this->assertSame($tabBaru->id, $konten->fresh()->konten_tab_id);
        $this->assertCount(1, $controller->proses()->getData()['perTab']->get($tabBaru->id));

        $tabController->destroy($tabBaru);
        $this->assertTrue(KontenTab::whereKey($tabBaru->id)->exists());
    }

    public function test_urutan_tab_hanya_bisa_diubah_dalam_grup_sendiri(): void
    {
        $pertama = KontenTab::create(['nama' => 'Ide', 'grup' => 'Perencanaan', 'warna' => '#123456', 'urutan' => 1]);
        $kedua = KontenTab::create(['nama' => 'Riset', 'grup' => 'Perencanaan', 'warna' => '#123456', 'urutan' => 2]);
        $lain = KontenTab::create(['nama' => 'Draft', 'grup' => 'Produksi', 'warna' => '#123456', 'urutan' => 1]);

        app(KontenTabController::class)->reorder(Request::create('/admin/konten/setup/urutan', 'POST', [
            'grup' => 'Perencanaan', 'ids' => [$kedua->id, $pertama->id],
        ]));

        $this->assertSame(1, $kedua->fresh()->urutan);
        $this->assertSame(2, $pertama->fresh()->urutan);
        $this->assertSame(1, $lain->fresh()->urutan);
        $this->assertTrue(Schema::hasTable('kontens'));
    }

    public function test_jadwal_tidak_bisa_dibuat_sebelum_ada_tab_setup(): void
    {
        $controller = app(KontenController::class);

        $this->assertFalse($controller->jadwal(Request::create('/admin/konten/jadwal'))->getData()['bolehTambahKonten']);
        $this->assertSame(route('konten.jadwal'), $controller->create(Request::create('/admin/konten/create'))->getTargetUrl());

        $response = $controller->store(Request::create('/admin/konten', 'POST', [
            'tanggal' => '2026-10-22',
            'topik' => 'Tips desain lanyard',
            'platform' => 'TikTok',
            'waktu_posting' => '10:00',
            'tujuan' => 'Edukasi pelanggan',
        ]));

        $this->assertSame(route('konten.jadwal'), $response->getTargetUrl());
        $this->assertSame(0, Konten::count());
    }

    public function test_setup_konten_hanya_untuk_super_dan_manager(): void
    {
        $this->withoutMiddleware(LoadSistemConfig::class);
        $this->withoutMiddleware(EnsureCabangAktif::class);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
        require_once base_path('database/migrations/2023_06_27_094613_create_permission_tables.php');
        (new \CreatePermissionTables())->up();

        $roles = collect(['super', 'Manager', 'admin'])->mapWithKeys(function ($name) {
            return [$name => DB::table('roles')->insertGetId(['name' => $name, 'guard_name' => 'web'])];
        });
        $users = collect(['super', 'Manager', 'admin'])->mapWithKeys(function ($name) use ($roles) {
            $user = User::create(['name' => $name, 'email' => strtolower($name).'@example.test', 'password' => 'secret']);
            DB::table('model_has_roles')->insert([
                'role_id' => $roles[$name], 'model_type' => User::class, 'model_id' => $user->id,
            ]);

            return [$name => $user];
        });

        $tab = KontenTab::create(['nama' => 'Ide', 'grup' => 'Perencanaan', 'warna' => '#123456', 'urutan' => 1]);
        $payload = ['nama' => 'Draft', 'grup' => 'Produksi', 'warna' => '#654321'];

        $this->actingAs($users['admin']);
        $this->assertStringNotContainsString('Setup Konten', view('layouts.navigation')->render());
        $this->get(route('konten.setup'))->assertForbidden();
        $this->post(route('konten.tab.store'), $payload)->assertForbidden();
        $this->patch(route('konten.tab.update', $tab), $payload)->assertForbidden();
        $this->delete(route('konten.tab.destroy', $tab))->assertForbidden();
        $this->post(route('konten.tab.reorder'), ['grup' => 'Perencanaan', 'ids' => [$tab->id]])->assertForbidden();
        $this->assertSame(1, KontenTab::count());

        $this->actingAs($users['Manager']);
        $this->assertStringContainsString('Setup Konten', view('layouts.navigation')->render());
        $this->post(route('konten.tab.store'), $payload)->assertRedirect(route('konten.setup'));
        $this->assertSame(2, KontenTab::count());

        $this->actingAs($users['super']);
        $this->assertStringContainsString('Setup Konten', view('layouts.navigation')->render());
        $this->patch(route('konten.tab.update', $tab), [
            'nama' => 'Ide Baru', 'grup' => 'Perencanaan', 'warna' => '#123456',
        ])->assertRedirect(route('konten.setup'));
        $this->assertSame('Ide Baru', $tab->fresh()->nama);
    }

    public function test_konten_baru_masuk_tab_pertama_sesuai_urutan_setup(): void
    {
        $pertama = KontenTab::create(['nama' => 'Ide', 'grup' => 'Perencanaan', 'warna' => '#123456', 'urutan' => 1]);
        $kedua = KontenTab::create(['nama' => 'Riset', 'grup' => 'Perencanaan', 'warna' => '#123456', 'urutan' => 2]);
        app(KontenTabController::class)->reorder(Request::create('/admin/konten/setup/urutan', 'POST', [
            'grup' => 'Perencanaan', 'ids' => [$kedua->id, $pertama->id],
        ]));

        app(KontenController::class)->store(Request::create('/admin/konten', 'POST', [
            'tanggal' => '2026-10-22',
            'topik' => 'Tips desain lanyard',
            'platform' => 'TikTok',
            'waktu_posting' => '10:00',
            'tujuan' => 'Edukasi pelanggan',
        ]));

        $this->assertSame($kedua->id, Konten::firstOrFail()->konten_tab_id);
    }

    public function test_tambah_dari_popup_proses_kembali_ke_dashboard_proses(): void
    {
        $tabAwal = KontenTab::create(['nama' => 'Ide', 'grup' => 'Perencanaan', 'warna' => '#123456', 'urutan' => 1]);
        $response = app(KontenController::class)->store(Request::create('/admin/konten', 'POST', [
            'asal' => 'proses',
            '_konten_popup' => '1',
            'tanggal' => '2026-10-22',
            'topik' => 'Tips desain lanyard',
            'platform' => 'TikTok',
            'waktu_posting' => '10:00',
            'tujuan' => 'Edukasi pelanggan',
        ]));

        $this->assertSame(route('konten.proses'), $response->getTargetUrl());
        $this->assertDatabaseHas('kontens', ['topik' => 'Tips desain lanyard', 'platform' => 'TikTok']);

        $konten = Konten::firstOrFail();
        $this->assertSame($tabAwal->id, $konten->konten_tab_id);
        $updated = app(KontenController::class)->update(Request::create('/admin/konten/'.$konten->id, 'PATCH', [
            'asal' => 'proses',
            'tanggal' => '2026-10-23',
            'topik' => 'Tips desain lanyard baru',
            'platform' => 'TikTok',
            'waktu_posting' => '11:00',
            'tujuan' => 'Edukasi pelanggan',
        ]), $konten);
        $this->assertSame(route('konten.proses'), $updated->getTargetUrl());

        $deleted = app(KontenController::class)->destroy(Request::create('/admin/konten/'.$konten->id, 'DELETE', [
            'asal' => 'proses',
        ]), $konten);
        $this->assertSame(route('konten.proses'), $deleted->getTargetUrl());
        $this->assertDatabaseMissing('kontens', ['id' => $konten->id]);
    }

    public function test_semua_konten_menampilkan_semua_bulan_dan_pencarian(): void
    {
        $oktober = Konten::create([
            'tanggal' => '2026-10-22', 'topik' => 'Foto lanyard', 'platform' => 'Instagram',
            'waktu_posting' => '10:00', 'tujuan' => 'Promosi produk',
        ]);
        Konten::create([
            'tanggal' => '2026-09-18', 'topik' => 'Video pabrik', 'platform' => 'TikTok',
            'waktu_posting' => '14:00', 'tujuan' => 'Edukasi proses',
        ]);

        $controller = app(KontenController::class);
        $semua = $controller->semua(Request::create('/admin/konten/semua', 'GET'));
        $this->assertSame(2, $semua->getData()['kontens']->total());
        $this->assertSame('Foto lanyard', $semua->getData()['kontens']->first()->topik);

        $hasilCari = $controller->semua(Request::create('/admin/konten/semua', 'GET', ['q' => 'TikTok']));
        $this->assertSame(1, $hasilCari->getData()['kontens']->total());
        $this->assertSame('Video pabrik', $hasilCari->getData()['kontens']->first()->topik);

        $response = $controller->update(Request::create('/admin/konten/'.$oktober->id, 'PATCH', [
            'asal' => 'semua', 'daftar_bulan' => '2026-10', 'daftar_q' => 'lanyard', 'daftar_page' => 2,
            'tanggal' => '2026-10-23', 'topik' => 'Foto lanyard baru', 'platform' => 'Instagram',
            'waktu_posting' => '11:00', 'tujuan' => 'Promosi produk baru',
        ]), $oktober);
        $this->assertSame(route('konten.semua', ['bulan' => '2026-10', 'q' => 'lanyard', 'page' => 2]), $response->getTargetUrl());
        $this->assertSame('Foto lanyard baru', $oktober->fresh()->topik);
    }

    public function test_chat_konten_menyimpan_pesan_dan_memisahkan_riwayat_per_konten(): void
    {
        $this->withoutMiddleware(LoadSistemConfig::class);
        $this->withoutMiddleware(EnsureCabangAktif::class);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('cabang_id')->nullable();
            $table->string('nama_lengkap');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->text('isi')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('project_mp_id')->nullable();
            $table->timestamps();
        });

        $migration = require base_path('database/migrations/2026_10_22_000002_add_konten_id_to_chats_table.php');
        $migration->up();
        $this->assertTrue(Schema::hasColumn('chats', 'konten_id'));

        $pengguna = User::create(['name' => 'Admin Konten', 'email' => 'konten@example.test', 'password' => 'secret']);
        DB::table('members')->insert(['user_id' => $pengguna->id, 'nama_lengkap' => 'Sandi Admin']);
        $kontenA = Konten::create([
            'tanggal' => '2026-10-22', 'topik' => 'Konten A', 'platform' => 'Instagram',
            'waktu_posting' => '10:00', 'tujuan' => 'Promosi',
        ]);
        $kontenB = Konten::create([
            'tanggal' => '2026-10-23', 'topik' => 'Konten B', 'platform' => 'TikTok',
            'waktu_posting' => '11:00', 'tujuan' => 'Edukasi',
        ]);

        $this->actingAs($pengguna)
            ->postJson('/admin/konten/'.$kontenA->id.'/chat', ['isi' => 'Siapkan naskah'])
            ->assertCreated()
            ->assertJsonPath('chat.author', 'Sandi Admin')
            ->assertJsonPath('chat.text', 'Siapkan naskah');
        $this->assertDatabaseHas('chats', ['konten_id' => $kontenA->id, 'isi' => 'Siapkan naskah']);

        $this->getJson('/admin/konten/'.$kontenA->id.'/chat')
            ->assertOk()->assertJsonCount(1, 'chats')
            ->assertJsonPath('chats.0.author', 'Sandi Admin');
        $this->getJson('/admin/konten/'.$kontenB->id.'/chat')
            ->assertOk()->assertJsonCount(0, 'chats');
        $this->postJson('/admin/konten/'.$kontenB->id.'/chat', ['isi' => '   '])
            ->assertUnprocessable()->assertJsonValidationErrors('isi');

    }
}
