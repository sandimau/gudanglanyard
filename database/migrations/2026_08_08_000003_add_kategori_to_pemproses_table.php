<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pemproses', 'kategori')) {
            Schema::table('pemproses', function (Blueprint $table) {
                $table->string('kategori', 20)->default('utama')->after('warna');
            });
        }

        // Data lama default utama; URG pastikan utama
        DB::table('pemproses')
            ->whereNull('kategori')
            ->orWhere('kategori', '')
            ->update(['kategori' => 'utama']);

        DB::table('pemproses')
            ->whereRaw('UPPER(TRIM(nama)) = ?', ['URG'])
            ->update(['kategori' => 'utama']);
    }

    public function down(): void
    {
        if (!Schema::hasColumn('pemproses', 'kategori')) {
            return;
        }

        Schema::table('pemproses', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }
};
