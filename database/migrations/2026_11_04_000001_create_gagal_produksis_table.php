<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gagal_produksis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_detail_id');
            $table->date('tanggal');
            $table->unsignedInteger('jumlah');
            $table->string('tahap')->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['tanggal', 'order_detail_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gagal_produksis');
    }
};
