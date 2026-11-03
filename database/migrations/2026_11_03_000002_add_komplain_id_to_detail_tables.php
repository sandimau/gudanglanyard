<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_details') && !Schema::hasColumn('order_details', 'komplain_id')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->unsignedBigInteger('komplain_id')->nullable()->after('order_id');
                $table->foreign('komplain_id')->references('id')->on('komplains')->onUpdate('cascade')->onDelete('set null');
            });
        }

        if (Schema::hasTable('project_mp_details') && !Schema::hasColumn('project_mp_details', 'komplain_id')) {
            Schema::table('project_mp_details', function (Blueprint $table) {
                $table->unsignedBigInteger('komplain_id')->nullable()->after('project_id');
                $table->foreign('komplain_id')->references('id')->on('komplains')->onUpdate('cascade')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_details') && Schema::hasColumn('order_details', 'komplain_id')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->dropForeign(['komplain_id']);
                $table->dropColumn('komplain_id');
            });
        }

        if (Schema::hasTable('project_mp_details') && Schema::hasColumn('project_mp_details', 'komplain_id')) {
            Schema::table('project_mp_details', function (Blueprint $table) {
                $table->dropForeign(['komplain_id']);
                $table->dropColumn('komplain_id');
            });
        }
    }
};
