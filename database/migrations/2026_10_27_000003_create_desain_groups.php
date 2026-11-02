<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desain_groups', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();
        });

        $groups = DB::table('desain_tabs')
            ->select('grup')
            ->distinct()
            ->orderBy('grup')
            ->pluck('grup');

        foreach ($groups as $index => $nama) {
            DB::table('desain_groups')->insert([
                'nama' => $nama,
                'urutan' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('desain_groups');
    }
};
