<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hpp_referensis', function (Blueprint $table) {
            $table->foreignId('produk_id_1s')->nullable()->constrained('produks')->nullOnDelete();
            $table->foreignId('produk_id_2s')->nullable()->constrained('produks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hpp_referensis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('produk_id_1s');
            $table->dropConstrainedForeignId('produk_id_2s');
        });
    }
};
