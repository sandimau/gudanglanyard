<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komplains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('order_detail_id')->nullable();
            $table->unsignedBigInteger('project_mp_id')->nullable();
            $table->unsignedBigInteger('project_mp_detail_id')->nullable();
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->unsignedBigInteger('kontak_id')->nullable();
            $table->string('nama_konsumen')->nullable();
            $table->string('nota')->nullable();
            $table->string('tipe', 15)->default('offline');
            $table->unsignedInteger('jumlah')->default(1);
            $table->string('bagian_salah');
            $table->text('keterangan')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('catatan_admin')->nullable();
            $table->unsignedBigInteger('kerugian')->default(0);
            $table->unsignedBigInteger('replacement_order_detail_id')->nullable();
            $table->unsignedBigInteger('replacement_project_mp_detail_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cabang_id')->references('id')->on('cabangs')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('order_id')->references('id')->on('orders')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('order_detail_id')->references('id')->on('order_details')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('project_mp_id')->references('id')->on('project_mps')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('project_mp_detail_id')->references('id')->on('project_mp_details')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('produk_id')->references('id')->on('produks')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('kontak_id')->references('id')->on('kontaks')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('replacement_order_detail_id')->references('id')->on('order_details')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('replacement_project_mp_detail_id')->references('id')->on('project_mp_details')->onUpdate('cascade')->onDelete('set null');

            $table->index(['status', 'tipe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komplains');
    }
};
