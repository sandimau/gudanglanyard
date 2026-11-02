<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('desains', 'tipe')) {
            Schema::table('desains', function (Blueprint $table) {
                $table->string('tipe', 10)->default('offline');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('desains', 'tipe')) {
            Schema::table('desains', function (Blueprint $table) {
                $table->dropColumn('tipe');
            });
        }
    }
};
