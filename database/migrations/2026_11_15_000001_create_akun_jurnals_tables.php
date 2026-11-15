<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('akun_jurnals')) {
            Schema::create('akun_jurnals', function (Blueprint $table) {
                $table->id();
                $table->string('nomor', 50)->unique();
                $table->date('tanggal');
                $table->string('source_type', 100)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('source_batch_filter', 100)->nullable();
                $table->bigInteger('total')->default(0);
                $table->text('keterangan')->nullable();
                $table->unsignedBigInteger('cabang_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('status', 30)->default('posted');
                $table->timestamp('posted_at')->nullable();
                $table->unsignedBigInteger('reversed_by_jurnal_id')->nullable();
                $table->unsignedBigInteger('reverses_jurnal_id')->nullable();
                $table->string('idempoten_kunci', 150)->nullable()->unique();
                $table->boolean('idempoten_aktif')->default(true);
                $table->timestamps();

                $table->index(['source_type', 'source_id']);
                $table->index('cabang_id');
            });
        }

        if (! Schema::hasTable('akun_jurnal_audit_logs')) {
            Schema::create('akun_jurnal_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('jurnal_id');
                $table->string('action', 50);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->timestamps();

                $table->index('jurnal_id');
            });
        }

        if (Schema::hasTable('buku_besars') && ! Schema::hasColumn('buku_besars', 'jurnal_id')) {
            Schema::table('buku_besars', function (Blueprint $table) {
                $table->unsignedBigInteger('jurnal_id')->nullable()->after('detail_id');
                $table->index('jurnal_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('buku_besars') && Schema::hasColumn('buku_besars', 'jurnal_id')) {
            Schema::table('buku_besars', function (Blueprint $table) {
                $table->dropIndex(['jurnal_id']);
                $table->dropColumn('jurnal_id');
            });
        }

        Schema::dropIfExists('akun_jurnal_audit_logs');
        Schema::dropIfExists('akun_jurnals');
    }
};
