<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DesainController;
use App\Models\DesainGroup;
use App\Models\DesainTab;
use App\Models\Desain;
use App\Models\Pemproses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DesainProsesTabsTest extends TestCase
{
    public function test_semua_tab_setup_tersedia_sebagai_pilihan_tapi_akhir_selesai_tidak_muncul_di_papan(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        (require base_path('database/migrations/2026_10_27_000001_create_desain_tables.php'))->up();
        (require base_path('database/migrations/2026_10_27_000003_create_desain_groups.php'))->up();
        (require base_path('database/migrations/2026_06_17_000001_create_pemproses_table.php'))->up();
        (require base_path('database/migrations/2026_08_08_000003_add_kategori_to_pemproses_table.php'))->up();
        (require base_path('database/migrations/2026_11_09_000001_add_pemproses_id_to_desains_table.php'))->up();

        DesainGroup::create(['nama' => 'Proses', 'urutan' => 1]);
        DesainGroup::create(['nama' => 'Akhir', 'urutan' => 2]);
        $proses = DesainTab::create(['nama' => 'Revisi', 'grup' => 'Proses', 'warna' => '#123456', 'urutan' => 1]);
        $lain = DesainTab::create(['nama' => 'Arsip', 'grup' => 'Akhir', 'warna' => '#123456', 'urutan' => 1]);
        $selesai = DesainTab::create(['nama' => 'Selesai', 'grup' => 'Akhir', 'warna' => '#123456', 'urutan' => 2]);
        $finish = DesainTab::create(['nama' => 'Finish', 'grup' => 'Akhir', 'warna' => '#123456', 'urutan' => 3]);

        $data = app(DesainController::class)->proses()->getData();

        $this->assertEqualsCanonicalizing(
            [$proses->id, $lain->id, $selesai->id, $finish->id],
            $data['allTabs']->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$proses->id, $lain->id],
            $data['tabs']->flatten()->pluck('id')->all()
        );
    }

    public function test_pemproses_desain_hanya_menerima_kategori_setting_dan_bisa_dikosongkan(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('produks', function (Blueprint $table) {
            $table->id();
        });
        (require base_path('database/migrations/2026_10_27_000001_create_desain_tables.php'))->up();
        (require base_path('database/migrations/2026_06_17_000001_create_pemproses_table.php'))->up();
        (require base_path('database/migrations/2026_08_08_000003_add_kategori_to_pemproses_table.php'))->up();
        (require base_path('database/migrations/2026_11_09_000001_add_pemproses_id_to_desains_table.php'))->up();

        $desain = Desain::create(['nama_konsumen' => 'Tes']);
        $setting = Pemproses::create(['nama' => 'Desainer', 'kategori' => Pemproses::KATEGORI_SETTING]);
        $utama = Pemproses::create(['nama' => 'Utama', 'kategori' => Pemproses::KATEGORI_UTAMA]);

        app(DesainController::class)->updatePemproses(Request::create('/', 'PATCH', ['pemproses_id' => $setting->id]), $desain);
        $this->assertEquals($setting->id, $desain->fresh()->pemproses_id);

        try {
            app(DesainController::class)->updatePemproses(Request::create('/', 'PATCH', ['pemproses_id' => $utama->id]), $desain);
            $this->fail('Pemproses kategori utama seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pemproses_id', $exception->errors());
        }
        $this->assertEquals($setting->id, $desain->fresh()->pemproses_id);

        app(DesainController::class)->updatePemproses(Request::create('/', 'PATCH', ['pemproses_id' => '']), $desain);
        $this->assertNull($desain->fresh()->pemproses_id);
    }
}
