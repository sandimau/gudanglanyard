<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Tabel historis dari pencatatan per-order. Tidak dipakai oleh menu Pakai Bahan;
    // migrasi tetap disimpan agar instalasi yang sudah pernah menjalankannya aman.
    public function up(): void
    {
        Schema::create('order_bahans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('project_mp_id')->nullable()->constrained('project_mps')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produks');
            $table->unsignedBigInteger('cabang_id')->nullable();
            $table->unsignedInteger('jumlah');
            $table->decimal('hpp', 15, 2);
            $table->decimal('harga', 15, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['created_at', 'produk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_bahans');
    }
};
