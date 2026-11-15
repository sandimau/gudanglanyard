<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('produk_asets')) {
            Schema::create('produk_asets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cabang_id')->nullable();
                $table->unsignedBigInteger('produk_id');
                $table->date('tanggal_beli');
                $table->integer('waktuPenyusutan')->default(1);
                $table->bigInteger('harga')->default(0);
                $table->integer('jumlah')->default(1);
                $table->bigInteger('saldo')->default(0);
                $table->text('keterangan')->nullable();
                $table->unsignedBigInteger('kontak_id')->nullable();
                $table->unsignedBigInteger('akun_detail_id')->nullable();
                $table->unsignedBigInteger('jurnal_id')->nullable();
                $table->timestamps();

                $table->foreign('cabang_id')->references('id')->on('cabangs')->nullOnDelete();
                $table->foreign('produk_id')->references('id')->on('produks')->restrictOnDelete();
                $table->foreign('kontak_id')->references('id')->on('kontaks')->nullOnDelete();
                $table->foreign('akun_detail_id')->references('id')->on('akun_details')->nullOnDelete();
                $table->foreign('jurnal_id')->references('id')->on('akun_jurnals')->nullOnDelete();

                $table->index(['saldo', 'tanggal_beli']);
            });
        }

        if (! Schema::hasTable('produk_aset_details')) {
            Schema::create('produk_aset_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('aset_id');
                $table->date('tanggal');
                $table->char('kode', 10);
                $table->string('keterangan')->nullable();
                $table->bigInteger('debet')->default(0);
                $table->bigInteger('kredit')->default(0);
                $table->unsignedBigInteger('jurnal_id')->nullable();
                $table->timestamps();

                $table->foreign('aset_id')->references('id')->on('produk_asets')->cascadeOnDelete();
                $table->foreign('jurnal_id')->references('id')->on('akun_jurnals')->nullOnDelete();

                $table->index(['aset_id', 'kode']);
                $table->index(['aset_id', 'tanggal']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_aset_details');
        Schema::dropIfExists('produk_asets');
    }
};
