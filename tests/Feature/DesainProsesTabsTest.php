<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DesainController;
use App\Models\DesainGroup;
use App\Models\DesainTab;
use Illuminate\Support\Facades\DB;
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
}
