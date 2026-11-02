<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $items = [
            ['Offset A3 0.3', 3000, null],
            ['Offset A4 Lem 0.15', 2100, 5],
            ['Pvc Laser A3 0.2', 4000, null],
            ['Inkjet A3 0.35', 5800, 10],
            ['Inkjet Silver a3', 5775, null],
            ['Overlay A4 0.1', 1100, 5],
            ['Print Epson', 25, null],
            ['Print Laser', 130, null],
            ['Print Ofset Jkt', 75, null],
            ['Inley S50 Fudan Sc', 12500, null],
            ['Inlay Em4100/Proximity', 11750, null],
            ['Press', 50, null],
            ['Plong', 100, null],
            ['Emboss', 200, null],
            ['Resin', 153, null],
            ['Pisau Pond / Cm', null, null],
            ['Lubang Pisau Pond', null, null],
        ];

        foreach ($items as $urutan => [$nama, $modal, $bagi]) {
            if (DB::table('hpp_referensis')->where('jenis', 'idcard_satuan')->where('nama', $nama)->exists()) {
                continue;
            }
            DB::table('hpp_referensis')->insert([
                'jenis' => 'idcard_satuan', 'nama' => $nama,
                'biaya' => json_encode(compact('modal', 'bagi')),
                'urutan' => $urutan, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Jangan hapus nilai yang mungkin sudah diedit pengguna.
    }
};
