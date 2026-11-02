<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desain_tabs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('grup');
            $table->string('warna', 7)->default('#0d6efd');
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();
        });

        Schema::create('desains', function (Blueprint $table) {
            $table->id();
            $table->string('nama_konsumen');
            $table->foreignId('produk_id')->nullable()->constrained('produks')->nullOnDelete();
            $table->string('tema')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('desain_tab_id')->nullable()->constrained('desain_tabs')->nullOnDelete();
            $table->timestamps();

            $table->index('nama_konsumen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desains');
        Schema::dropIfExists('desain_tabs');
    }
};
