<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap')->nullable();
            $table->date('tgl_masuk')->nullable();
            $table->date('tgl_keluar')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->string('alamat')->nullable();
            $table->string('no_telp')->nullable();
            $table->char('tgl_gajian',10)->nullable();
            $table->string('no_rek')->nullable();
            $table->string('status')->nullable();
            // Kolom ini dipakai untuk membedakan karyawan/freelance (scopeAktif/scopeFreelance),
            // tidak ada migration penambahnya sehingga ditambahkan di sini agar fresh install lengkap.
            $table->string('jenis')->default('karyawan');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
