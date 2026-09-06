<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cabangs')) {
            Schema::create('cabangs', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('kode')->nullable()->unique();
                $table->text('alamat')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        $pusatId = DB::table('cabangs')->where('kode', 'PUSAT')->value('id');
        if (!$pusatId) {
            $pusatId = DB::table('cabangs')->insertGetId([
                'nama' => 'Pusat',
                'kode' => 'PUSAT',
                'alamat' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tablesNeedColumn = [
            'produk_kategori_utamas',
            'produk_kategoris',
            'produk_models',
            'produks',
            'orders',
            'belanjas',
            'produk_po',
        ];

        foreach ($tablesNeedColumn as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'cabang_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('cabang_id')->nullable()->after('id');
                });
            }

            DB::table($table)->whereNull('cabang_id')->update(['cabang_id' => $pusatId]);
        }

        $tablesExistingCabang = [
            'produk_pakais',
            'produksi_produks',
            'produk_produksi_bahans',
        ];

        foreach ($tablesExistingCabang as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'cabang_id')) {
                continue;
            }

            DB::table($table)->whereNull('cabang_id')->update(['cabang_id' => $pusatId]);
            DB::table($table)
                ->whereNotNull('cabang_id')
                ->whereNotIn('cabang_id', function ($query) {
                    $query->select('id')->from('cabangs');
                })
                ->update(['cabang_id' => $pusatId]);
        }

        $allTables = array_merge($tablesNeedColumn, $tablesExistingCabang);

        foreach ($allTables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'cabang_id')) {
                continue;
            }

            $indexName = $table . '_cabang_id_foreign';
            $indexes = collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]));
            if ($indexes->isEmpty()) {
                try {
                    Schema::table($table, function (Blueprint $blueprint) use ($table) {
                        $blueprint->foreign('cabang_id')
                            ->references('id')
                            ->on('cabangs')
                            ->onUpdate('cascade')
                            ->onDelete('restrict');
                    });
                } catch (\Throwable $e) {
                    // FK mungkin sudah ada dengan nama berbeda
                }
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'produk_kategori_utamas',
            'produk_kategoris',
            'produk_models',
            'produks',
            'orders',
            'belanjas',
            'produk_po',
            'produk_pakais',
            'produksi_produks',
            'produk_produksi_bahans',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'cabang_id')) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropForeign(['cabang_id']);
                });
            } catch (\Throwable $e) {
                // ignore
            }

            if (in_array($table, [
                'produk_kategori_utamas',
                'produk_kategoris',
                'produk_models',
                'produks',
                'orders',
                'belanjas',
                'produk_po',
            ], true)) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('cabang_id');
                });
            }
        }

        Schema::dropIfExists('cabangs');
    }
};
