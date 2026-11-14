<?php

namespace Tests\Feature;

use App\Models\ProjectMpDetail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectMpDashboardKomplainTest extends TestCase
{
    public function test_pengganti_komplain_muncul_tanpa_buffer_tetapi_produk_biasa_tidak(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('project_mp_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('komplain_id')->nullable();
        });
        Schema::create('marketplace_buffers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('status');
            $table->boolean('custom');
        });

        DB::table('project_mp_details')->insert([
            ['id' => 1, 'project_id' => 100, 'komplain_id' => null],
            ['id' => 2, 'project_id' => 100, 'komplain_id' => 10],
            ['id' => 3, 'project_id' => 200, 'komplain_id' => null],
            ['id' => 4, 'project_id' => 300, 'komplain_id' => null],
            ['id' => 5, 'project_id' => 300, 'komplain_id' => 11],
        ]);
        DB::table('marketplace_buffers')->insert([
            ['id' => 1, 'project_id' => 200, 'status' => 'SHIPPED', 'custom' => 1],
            ['id' => 2, 'project_id' => 300, 'status' => 'COMPLETED', 'custom' => 1],
        ]);

        $this->assertEqualsCanonicalizing(
            [2, 3, 5],
            ProjectMpDetail::forDashboardCustom()->pluck('id')->all()
        );
    }
}
