<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('project_mp_details', 'pemproses_id')) {
            return;
        }

        Schema::table('project_mp_details', function (Blueprint $table) {
            $table->unsignedBigInteger('pemproses_id')->nullable()->after('produksi_id');
            $table->foreign('pemproses_id')->references('id')->on('pemproses')->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('project_mp_details', 'pemproses_id')) {
            return;
        }

        Schema::table('project_mp_details', function (Blueprint $table) {
            $table->dropForeign(['pemproses_id']);
            $table->dropColumn('pemproses_id');
        });
    }
};
