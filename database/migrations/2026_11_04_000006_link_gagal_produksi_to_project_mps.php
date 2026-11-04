<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gagal_produksis', function (Blueprint $table) {
            $table->unsignedBigInteger('project_mp_id')->nullable()->index();
            $table->unsignedBigInteger('project_mp_detail_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('gagal_produksis', function (Blueprint $table) {
            $table->dropIndex(['project_mp_id']);
            $table->dropIndex(['project_mp_detail_id']);
            $table->dropColumn(['project_mp_id', 'project_mp_detail_id']);
        });
    }
};
