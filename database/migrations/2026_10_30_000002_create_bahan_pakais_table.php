<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Migrasi historis dari menu Pakai Bahan yang kini digantikan Pemakaian.
    // Dibiarkan agar instalasi yang sudah menjalankannya tidak kehilangan data.
    public function up(): void
    {
        Schema::create('bahan_pakais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produks');
            $table->unsignedBigInteger('cabang_id');
            $table->foreignId('produk_stok_id')->nullable()->constrained('produk_stoks');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('jumlah');
            $table->decimal('hpp', 15, 2);
            $table->decimal('harga', 15, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['created_at', 'produk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_pakais');
    }
};
