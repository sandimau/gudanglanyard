<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('produk_pakais')) {
            return;
        }

        if (!Schema::hasColumn('produk_pakais', 'cabang_id')) {
            Schema::table('produk_pakais', function (Blueprint $table) {
                $table->unsignedBigInteger('cabang_id')->nullable()->after('id');
            });
        }

        if (!Schema::hasTable('cabangs')) {
            return;
        }

        $pusatId = DB::table('cabangs')->where('kode', 'PUSAT')->value('id');
        if (!$pusatId) {
            return;
        }

        if (Schema::hasTable('produk_stoks') && Schema::hasColumn('produk_stoks', 'cabang_id')) {
            DB::table('produk_pakais')->whereNull('cabang_id')->update([
                'cabang_id' => DB::raw('COALESCE((SELECT ps.cabang_id FROM produk_stoks ps WHERE ps.id = produk_pakais.produk_stok_id), ' . (int) $pusatId . ')'),
            ]);
        } else {
            DB::table('produk_pakais')->whereNull('cabang_id')->update(['cabang_id' => $pusatId]);
        }
    }

    public function down(): void
    {
        // Tidak menghapus kolom karena catatan baru sudah menggunakannya.
    }
};
