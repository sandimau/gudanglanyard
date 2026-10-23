<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konten_tabs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('grup');
            $table->string('warna', 7)->default('#0d6efd');
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();
        });

        Schema::create('kontens', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('topik');
            $table->string('platform');
            $table->time('waktu_posting');
            $table->text('tujuan');
            $table->foreignId('konten_tab_id')->nullable()->constrained('konten_tabs')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'waktu_posting']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontens');
        Schema::dropIfExists('konten_tabs');
    }
};
