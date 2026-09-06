<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pusatId = DB::table('cabangs')->where('kode', 'PUSAT')->value('id')
            ?? DB::table('cabangs')->orderBy('id')->value('id');

        if (!$pusatId) {
            $pusatId = DB::table('cabangs')->insertGetId([
                'nama' => 'Pusat',
                'kode' => 'PUSAT',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('produk_stoks') && !Schema::hasColumn('produk_stoks', 'cabang_id')) {
            Schema::table('produk_stoks', function (Blueprint $table) {
                $table->unsignedBigInteger('cabang_id')->nullable()->after('produk_id');
            });
        }

        if (Schema::hasTable('produk_stoks') && Schema::hasColumn('produk_stoks', 'cabang_id')) {
            DB::table('produk_stoks')->whereNull('cabang_id')->update(['cabang_id' => $pusatId]);
        }

        if (Schema::hasTable('produk_last_stoks') && !Schema::hasColumn('produk_last_stoks', 'cabang_id')) {
            Schema::table('produk_last_stoks', function (Blueprint $table) {
                $table->unsignedBigInteger('cabang_id')->nullable()->after('produk_id');
            });
        }

        if (Schema::hasTable('produk_last_stoks') && Schema::hasColumn('produk_last_stoks', 'cabang_id')) {
            DB::table('produk_last_stoks')->whereNull('cabang_id')->update(['cabang_id' => $pusatId]);

            try {
                Schema::table('produk_last_stoks', function (Blueprint $table) {
                    $table->dropUnique('produk_last_stoks_produk_tahun_unique');
                });
            } catch (\Throwable $e) {
                // index mungkin belum ada / nama berbeda
            }

            $indexName = 'produk_last_stoks_produk_cabang_tahun_unique';
            $indexes = collect(DB::select("SHOW INDEX FROM produk_last_stoks WHERE Key_name = ?", [$indexName]));
            if ($indexes->isEmpty()) {
                Schema::table('produk_last_stoks', function (Blueprint $table) use ($indexName) {
                    $table->unique(['produk_id', 'cabang_id', 'tahun'], $indexName);
                });
            }
        }

        if (Schema::hasTable('kontaks') && !Schema::hasColumn('kontaks', 'cabang_id')) {
            Schema::table('kontaks', function (Blueprint $table) {
                $table->unsignedBigInteger('cabang_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('kontaks') && Schema::hasColumn('kontaks', 'cabang_id')) {
            DB::table('kontaks')->whereNull('cabang_id')->update(['cabang_id' => $pusatId]);
        }

        foreach (['produk_stoks', 'produk_last_stoks', 'kontaks'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'cabang_id')) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreign('cabang_id')
                        ->references('id')
                        ->on('cabangs')
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
                });
            } catch (\Throwable $e) {
                // FK mungkin sudah ada
            }
        }

        // Index bantu saldo per cabang
        if (Schema::hasTable('produk_stoks') && Schema::hasColumn('produk_stoks', 'cabang_id')) {
            $indexName = 'produk_stoks_produk_cabang_created_index';
            $indexes = collect(DB::select("SHOW INDEX FROM produk_stoks WHERE Key_name = ?", [$indexName]));
            if ($indexes->isEmpty()) {
                Schema::table('produk_stoks', function (Blueprint $table) use ($indexName) {
                    $table->index(['produk_id', 'cabang_id', 'created_at'], $indexName);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('produk_stoks') && Schema::hasColumn('produk_stoks', 'cabang_id')) {
            try {
                Schema::table('produk_stoks', function (Blueprint $table) {
                    $table->dropIndex('produk_stoks_produk_cabang_created_index');
                });
            } catch (\Throwable $e) {
            }

            try {
                Schema::table('produk_stoks', function (Blueprint $table) {
                    $table->dropForeign(['cabang_id']);
                });
            } catch (\Throwable $e) {
            }

            Schema::table('produk_stoks', function (Blueprint $table) {
                $table->dropColumn('cabang_id');
            });
        }

        if (Schema::hasTable('produk_last_stoks') && Schema::hasColumn('produk_last_stoks', 'cabang_id')) {
            try {
                Schema::table('produk_last_stoks', function (Blueprint $table) {
                    $table->dropUnique('produk_last_stoks_produk_cabang_tahun_unique');
                });
            } catch (\Throwable $e) {
            }

            try {
                Schema::table('produk_last_stoks', function (Blueprint $table) {
                    $table->dropForeign(['cabang_id']);
                });
            } catch (\Throwable $e) {
            }

            Schema::table('produk_last_stoks', function (Blueprint $table) {
                $table->dropColumn('cabang_id');
            });

            try {
                Schema::table('produk_last_stoks', function (Blueprint $table) {
                    $table->unique(['produk_id', 'tahun'], 'produk_last_stoks_produk_tahun_unique');
                });
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasTable('kontaks') && Schema::hasColumn('kontaks', 'cabang_id')) {
            try {
                Schema::table('kontaks', function (Blueprint $table) {
                    $table->dropForeign(['cabang_id']);
                });
            } catch (\Throwable $e) {
            }

            Schema::table('kontaks', function (Blueprint $table) {
                $table->dropColumn('cabang_id');
            });
        }
    }
};
