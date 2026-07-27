<?php

/**
 * Jika tidak bisa `php artisan migrate` (mis. Hostinger tanpa SSH),
 * jalankan SQL manual di phpMyAdmin:
 *   database/sql/2026_07_21_refactor_buku_besar_saldo.sql
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            if (!Schema::hasTable('akun_last_saldos')) {
                Schema::create('akun_last_saldos', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('akun_detail_id')->nullable();
                    $table->foreign('akun_detail_id')
                        ->references('id')
                        ->on('akun_details')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
                    $table->decimal('saldo', 15, 2)->nullable();
                    $table->integer('tahun')->nullable();
                    $table->timestamps();
                    $table->unique(['akun_detail_id', 'tahun'], 'akun_last_saldos_akun_tahun_unique');
                });
            }

            $this->rebuildAkunLastSaldos();

            if (Schema::hasColumn('buku_besars', 'saldo')) {
                Schema::table('buku_besars', function (Blueprint $table) {
                    $table->dropColumn('saldo');
                });
            }
        });
    }

    private function rebuildAkunLastSaldos(): void
    {
        DB::table('akun_last_saldos')->truncate();

        $akunIds = DB::table('buku_besars')
            ->distinct()
            ->pluck('akun_detail_id')
            ->filter();

        $now = now();

        foreach ($akunIds as $akunId) {
            $years = DB::table('buku_besars')
                ->where('akun_detail_id', $akunId)
                ->selectRaw('DISTINCT YEAR(created_at) as tahun')
                ->orderBy('tahun')
                ->pluck('tahun');

            $saldoAkumulasi = 0;

            foreach ($years as $year) {
                $mutasi = (float) DB::table('buku_besars')
                    ->where('akun_detail_id', $akunId)
                    ->whereYear('created_at', $year)
                    ->selectRaw('COALESCE(SUM(COALESCE(debet, 0) - COALESCE(kredit, 0)), 0) as saldo')
                    ->value('saldo');

                $saldoAkumulasi += $mutasi;

                DB::table('akun_last_saldos')->insert([
                    'akun_detail_id' => $akunId,
                    'saldo' => $saldoAkumulasi,
                    'tahun' => (int) $year,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('akun_details')
                ->where('id', $akunId)
                ->update(['saldo' => $saldoAkumulasi]);
        }
    }

    public function down(): void
    {
        Schema::table('buku_besars', function (Blueprint $table) {
            if (!Schema::hasColumn('buku_besars', 'saldo')) {
                $table->integer('saldo')->nullable()->after('kredit');
            }
        });

        Schema::dropIfExists('akun_last_saldos');
    }
};
