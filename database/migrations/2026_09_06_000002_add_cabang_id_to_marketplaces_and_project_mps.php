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

        if (Schema::hasTable('marketplaces') && !Schema::hasColumn('marketplaces', 'cabang_id')) {
            Schema::table('marketplaces', function (Blueprint $table) {
                $table->unsignedBigInteger('cabang_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('project_mps') && !Schema::hasColumn('project_mps', 'cabang_id')) {
            Schema::table('project_mps', function (Blueprint $table) {
                $table->unsignedBigInteger('cabang_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('marketplaces') && Schema::hasColumn('marketplaces', 'cabang_id')) {
            DB::table('marketplaces')->whereNull('cabang_id')->update(['cabang_id' => $pusatId]);
        }

        if (Schema::hasTable('project_mps') && Schema::hasColumn('project_mps', 'cabang_id')) {
            // Isi dari marketplace jika ada, sisanya Pusat
            DB::statement("
                UPDATE project_mps pm
                LEFT JOIN marketplaces m ON m.id = pm.marketplace_id
                SET pm.cabang_id = COALESCE(m.cabang_id, ?)
                WHERE pm.cabang_id IS NULL
            ", [$pusatId]);
        }

        foreach (['marketplaces', 'project_mps'] as $table) {
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
    }

    public function down(): void
    {
        foreach (['project_mps', 'marketplaces'] as $table) {
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

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('cabang_id');
            });
        }
    }
};
