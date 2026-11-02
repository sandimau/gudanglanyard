<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            ['tali isu 1CM', 24500],
            ['tali isu 1,5CM', 33500],
            ['tali isu 2CM Grade A', 36000],
            ['tali isu 2,5CM', 49000],
            ['tali isu 3CM', 55000],
            ['tali isu 4CM', 70500],
        ] as $urutan => [$nama, $modal]) {
            if (DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', $nama)->exists()) {
                continue;
            }
            DB::table('hpp_referensis')->insert([
                'jenis' => 'tali', 'nama' => $nama,
                'biaya' => json_encode(['modal' => $modal, 'bagi' => 45]),
                'urutan' => $urutan, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Jangan hapus perhitungan yang mungkin sudah diedit pengguna.
    }
};
