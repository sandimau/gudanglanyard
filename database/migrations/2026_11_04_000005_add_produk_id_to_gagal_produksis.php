<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gagal_produksis', function (Blueprint $table) {
            $table->unsignedBigInteger('produk_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('gagal_produksis', function (Blueprint $table) {
            $table->dropIndex(['produk_id']);
            $table->dropColumn('produk_id');
        });
    }
};
