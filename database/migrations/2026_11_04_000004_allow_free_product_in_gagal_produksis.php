<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gagal_produksis', function (Blueprint $table) {
            $table->unsignedBigInteger('order_detail_id')->nullable()->change();
            $table->string('nama_produk')->nullable();
            $table->unsignedBigInteger('cabang_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        // Catatan tanpa order tetap dipertahankan agar tidak kehilangan data.
    }
};
