<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('project_mps', 'pemproses_id')) {
            Schema::table('project_mps', function (Blueprint $table) {
                $table->unsignedBigInteger('pemproses_id')->nullable()->after('marketplace_id');
                $table->foreign('pemproses_id')->references('id')->on('pemproses')->onUpdate('cascade')->onDelete('set null');
            });
        }

        // Pindahkan URG dari detail ke project utama
        $urgId = DB::table('pemproses')
            ->whereRaw('UPPER(TRIM(nama)) = ?', ['URG'])
            ->value('id');

        if ($urgId && Schema::hasColumn('project_mp_details', 'pemproses_id')) {
            $projectIds = DB::table('project_mp_details')
                ->where('pemproses_id', $urgId)
                ->whereNotNull('project_id')
                ->distinct()
                ->pluck('project_id');

            if ($projectIds->isNotEmpty()) {
                DB::table('project_mps')
                    ->whereIn('id', $projectIds)
                    ->whereNull('pemproses_id')
                    ->update(['pemproses_id' => $urgId]);

                DB::table('project_mp_details')
                    ->whereIn('project_id', $projectIds)
                    ->where('pemproses_id', $urgId)
                    ->update(['pemproses_id' => null]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('project_mps', 'pemproses_id')) {
            return;
        }

        Schema::table('project_mps', function (Blueprint $table) {
            $table->dropForeign(['pemproses_id']);
            $table->dropColumn('pemproses_id');
        });
    }
};
