<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $groups = [
            'awal' => [
                'Persiapan' => 1,
                'File' => 2,
                'Salah_Order' => 3,
            ],
            'Desain' => [
                'DESAIN' => 1,
                'ACC' => 2,
            ],
            'Setting' => [
                'Setting_IDCARD' => 1,
                'Setting_Lanyard' => 2,
            ],
            'Produksi ID Card' => [
                'PRINT_IDCARD' => 1,
                'PRESS_IDCARD' => 2,
                'PLONG_IDCARD' => 3,
                'Finishing_IDCARD' => 4,
            ],
            'Produksi Lanyard' => [
                'PRINT_LANYARD' => 1,
                'PRESS_LANYARD' => 2,
                'Finishing_LANYARD' => 3,
            ],
            'Selesai' => [
                'Packing' => 1,
                'Beres' => 2,
                'finish' => 99,
            ],
        ];

        foreach ($groups as $grup => $items) {
            foreach ($items as $nama => $urutan) {
                DB::table('produksis')
                    ->where('nama', $nama)
                    ->update([
                        'grup' => $grup,
                        'urutan' => $urutan,
                    ]);
            }
        }
    }

    public function down(): void
    {
        $legacy = [
            'awal' => [
                'Persiapan' => 1,
                'File' => 1,
                'Salah_Order' => 3,
            ],
            'produksi' => [
                'DESAIN' => 1,
                'ACC' => 1,
                'Setting_IDCARD' => 2,
                'Setting_Lanyard' => 2,
                'PRINT_IDCARD' => 4,
                'PRINT_LANYARD' => 4,
                'PRESS_IDCARD' => 5,
                'PRESS_LANYARD' => 5,
                'PLONG_IDCARD' => 6,
                'Finishing_IDCARD' => 7,
                'Finishing_LANYARD' => 7,
            ],
            'selesai' => [
                'finish' => 5,
                'Packing' => 8,
                'Beres' => 9,
            ],
        ];

        foreach ($legacy as $grup => $items) {
            foreach ($items as $nama => $urutan) {
                DB::table('produksis')
                    ->where('nama', $nama)
                    ->update([
                        'grup' => $grup,
                        'urutan' => $urutan,
                    ]);
            }
        }
    }
};
