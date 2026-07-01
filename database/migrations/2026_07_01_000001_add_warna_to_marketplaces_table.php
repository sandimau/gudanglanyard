<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplaces') || Schema::hasColumn('marketplaces', 'warna')) {
            return;
        }

        Schema::table('marketplaces', function (Blueprint $table) {
            $table->string('warna', 100)->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplaces') || ! Schema::hasColumn('marketplaces', 'warna')) {
            return;
        }

        Schema::table('marketplaces', function (Blueprint $table) {
            $table->dropColumn('warna');
        });
    }
};
