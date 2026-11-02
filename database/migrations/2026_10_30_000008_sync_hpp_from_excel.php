<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Koreksi hanya nilai bawaan lama. Biaya yang telah diedit pengguna tidak ditimpa.
        $corrections = [
            '1,5 CM' => ['tali' => [744, 33500 / 45]],
            '2 CM' => ['stopper' => [127, 15250 / 120]],
            '2,5 CM' => ['tali' => [1089, 49000 / 45]],
            '03 CM' => ['tali' => [1222, 55000 / 45]],
            '04 CM' => ['tali' => [1567, 70500 / 45]],
            '2 cm satin lanyard' => ['print1' => [338, 337.5], 'print2' => [338, 337.5]],
            '1,5 CM (varian kedua)' => ['tali' => [622, 28000 / 45]],
            '2 CM (varian kedua)' => ['tali' => [667, 30000 / 45]],
        ];
        foreach ($corrections as $nama => $fields) {
            $row = DB::table('hpp_referensis')->where('jenis', 'bahan')->where('nama', $nama)->first();
            if (!$row) continue;
            $biaya = json_decode($row->biaya, true);
            foreach ($fields as $key => [$old, $new]) {
                if (($biaya[$key] ?? null) === $old) $biaya[$key] = round($new, 8);
            }
            DB::table('hpp_referensis')->where('id', $row->id)->update(['biaya' => json_encode($biaya), 'updated_at' => now()]);
        }

        $next = (int) DB::table('hpp_referensis')->max('urutan') + 1;
        $idcards = [
            ['K NAMA EPSON 2 muka', 840, 25, 220, 0, 100, 50, 50],
            ['K NAMA LASER 2 muka', 400, 110, 200, 0, 100, 50, 50],
            ['K NAMA EPSON 1 muka', 300, 25, 200, 0, 100, 50, 50],
            ['K NAMA LASER 1 muka', 155, 130, 200, 0, 100, 50, 50],
            ['STICKER PVC', 225, 130, 200, 0, 100, 50, 50],
            ['STICKER PVC COLD PRESS', 450, 130, 0, 0, 111, 50, 0],
            ['Hologram K-POP', 941, 110, 216, 81, 100, 50, 0],
            ['Logo Hologram UK 1,5cm', 0, 81, 0, 0, 0, 0, 0],
            ['Glitter Sticker (Laser) 10pcs', 500, 200, 350, 207, 100, 50, 0],
            ['Glitter Sticker (EPSON) 10pcs', 540, 200, 350, 0, 100, 50, 0],
            ['Glitter Sticker (Offset) 10pcs', 351, 78, 350, 81, 100, 50, 0],
        ];
        foreach ($idcards as [$nama, $kertas, $print, $overlay, $penebal, $plong, $press, $finishing]) {
            $this->insertIfMissing('idcard', $nama, compact('kertas', 'print', 'overlay', 'penebal', 'plong', 'press', 'finishing'), $next++);
        }
        foreach ([
            ['RESIN A4_1 pcs', 3150, 600, 197, 2000, 150],
            ['RESIN A4_10 pcs', 315, 60, 197, 200, 150],
            ['RESIN A4_126 pcs', 25, 5, 197, 15, 150],
        ] as [$nama, $stiker, $cetak, $resin, $cutting, $makloon]) {
            $this->insertIfMissing('resin', $nama, compact('stiker', 'cetak', 'resin', 'cutting', 'makloon'), $next++);
        }
        foreach ([
            ['1,5 CM (sablon)', 622, 750, 750, 0, 585, 77, 113, 400],
            ['02 CM (sablon)', 666, 750, 750, 0, 630, 77, 135, 400],
        ] as [$nama, $tali, $print1, $print2, $press, $cantolan, $mataItik, $stopper, $finishing]) {
            $this->insertIfMissing('bahan', $nama, compact('tali', 'print1', 'print2', 'press', 'cantolan', 'mataItik', 'stopper', 'finishing'), $next++);
        }
    }

    private function insertIfMissing(string $jenis, string $nama, array $biaya, int $urutan): void
    {
        if (DB::table('hpp_referensis')->where('jenis', $jenis)->where('nama', $nama)->exists()) return;
        DB::table('hpp_referensis')->insert([
            'jenis' => $jenis, 'nama' => $nama, 'biaya' => json_encode($biaya),
            'urutan' => $urutan, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Jangan hapus data HPP yang mungkin sudah diedit pengguna.
    }
};
