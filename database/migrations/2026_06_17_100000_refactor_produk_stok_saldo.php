<?php

/**
 * Jika tidak bisa `php artisan migrate` (mis. Hostinger tanpa SSH),
 * jalankan SQL manual di phpMyAdmin:
 *   database/sql/2026_06_17_refactor_produk_stok_saldo.sql
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catatan: DDL (create/alter/drop) memicu implicit commit di MySQL/MariaDB,
        // jadi blok ini tidak dibungkus DB::transaction agar tidak error
        // "There is no active transaction".
        DB::statement("
            DELETE s1 FROM produk_stoks s1
            INNER JOIN produk_stoks s2
                ON s1.produk_id <=> s2.produk_id
                AND s1.kode <=> s2.kode
                AND s1.detail_id <=> s2.detail_id
                AND s1.tambah <=> s2.tambah
                AND s1.kurang <=> s2.kurang
                AND DATE(s1.created_at) = DATE(s2.created_at)
                AND s1.deleted_at IS NULL
                AND s2.deleted_at IS NULL
                AND s1.id > s2.id
        ");

        if (!Schema::hasColumn('produk_last_stoks', 'tahun')) {
            Schema::table('produk_last_stoks', function (Blueprint $table) {
                $table->integer('tahun')->nullable()->after('saldo');
            });
        }

        DB::table('produk_last_stoks')->whereNull('tahun')->update([
            'tahun' => (int) date('Y'),
        ]);

        $this->rebuildProdukLastStoks();

        if (Schema::hasColumn('produk_stoks', 'saldo')) {
            Schema::table('produk_stoks', function (Blueprint $table) {
                $table->dropColumn('saldo');
            });
        }

        $indexName = 'produk_last_stoks_produk_tahun_unique';
        $indexes = collect(DB::select("SHOW INDEX FROM produk_last_stoks WHERE Key_name = ?", [$indexName]));
        if ($indexes->isEmpty()) {
            Schema::table('produk_last_stoks', function (Blueprint $table) {
                $table->unique(['produk_id', 'tahun'], 'produk_last_stoks_produk_tahun_unique');
            });
        }
    }

    private function rebuildProdukLastStoks(): void
    {
        DB::table('produk_last_stoks')->truncate();

        $produkIds = DB::table('produk_stoks')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('produk_id')
            ->filter();

        $now = now();

        foreach ($produkIds as $produkId) {
            $years = DB::table('produk_stoks')
                ->where('produk_id', $produkId)
                ->whereNull('deleted_at')
                ->selectRaw('DISTINCT YEAR(created_at) as tahun')
                ->orderBy('tahun')
                ->pluck('tahun');

            $saldoAkumulasi = 0;

            foreach ($years as $year) {
                $mutasi = (int) DB::table('produk_stoks')
                    ->where('produk_id', $produkId)
                    ->whereNull('deleted_at')
                    ->whereYear('created_at', $year)
                    ->selectRaw('COALESCE(SUM(COALESCE(tambah, 0) - COALESCE(kurang, 0)), 0) as saldo')
                    ->value('saldo');

                $saldoAkumulasi += $mutasi;

                DB::table('produk_last_stoks')->insert([
                    'produk_id' => $produkId,
                    'saldo' => $saldoAkumulasi,
                    'tahun' => (int) $year,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('produk_stoks', function (Blueprint $table) {
            if (!Schema::hasColumn('produk_stoks', 'saldo')) {
                $table->integer('saldo')->nullable()->after('kurang');
            }
        });

        Schema::table('produk_last_stoks', function (Blueprint $table) {
            $table->dropUnique('produk_last_stoks_produk_tahun_unique');
            if (Schema::hasColumn('produk_last_stoks', 'tahun')) {
                $table->dropColumn('tahun');
            }
        });
    }
};
