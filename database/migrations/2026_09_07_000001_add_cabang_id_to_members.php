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

        if (Schema::hasTable('members') && !Schema::hasColumn('members', 'cabang_id')) {
            Schema::table('members', function (Blueprint $table) {
                $table->unsignedBigInteger('cabang_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('members') && Schema::hasColumn('members', 'cabang_id')) {
            DB::table('members')->whereNull('cabang_id')->update(['cabang_id' => $pusatId]);

            try {
                Schema::table('members', function (Blueprint $blueprint) {
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
        if (!Schema::hasTable('members') || !Schema::hasColumn('members', 'cabang_id')) {
            return;
        }

        try {
            Schema::table('members', function (Blueprint $blueprint) {
                $blueprint->dropForeign(['cabang_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('members', function (Blueprint $blueprint) {
            $blueprint->dropColumn('cabang_id');
        });
    }
};
