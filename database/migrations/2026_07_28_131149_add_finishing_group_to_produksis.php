<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('produksis')
            ->where('nama', 'Finishing_IDCARD')
            ->update([
                'nama' => 'finishing_idcard',
                'grup' => 'FINISHING',
                'urutan' => 1,
            ]);

        DB::table('produksis')
            ->where('nama', 'Finishing_LANYARD')
            ->update([
                'nama' => 'finishing_lanyard',
                'grup' => 'FINISHING',
                'urutan' => 2,
            ]);

        if (! DB::table('produksis')->where('nama', 'makloon')->exists()) {
            DB::table('produksis')->insert([
                'nama' => 'makloon',
                'grup' => 'FINISHING',
                'warna' => '#FFA800',
                'urutan' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('produksis')->where('nama', 'makloon')->delete();

        DB::table('produksis')
            ->where('nama', 'finishing_idcard')
            ->update([
                'nama' => 'Finishing_IDCARD',
                'grup' => 'Produksi ID Card',
                'urutan' => 4,
            ]);

        DB::table('produksis')
            ->where('nama', 'finishing_lanyard')
            ->update([
                'nama' => 'Finishing_LANYARD',
                'grup' => 'Produksi Lanyard',
                'urutan' => 3,
            ]);
    }
};
