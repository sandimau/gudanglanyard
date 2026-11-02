<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hpp_referensis', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);
            $table->string('nama');
            $table->json('biaya');
            $table->unsignedInteger('urutan');
            $table->timestamps();
            $table->index(['jenis', 'urutan']);
        });

        $now = now();
        $rows = [];
        $bahan = [
            ['1,5 CM', round(33500 / 45, 8), 675, 675, 400, 625, 72, 113, 400],
            ['2 CM', 800, 844, 844, 400, 625, 76, round(15250 / 120, 8), 400],
            ['2,5 CM', round(49000 / 45, 8), 964, 964, 400, 675, 72, 142, 400],
            ['03 CM', round(55000 / 45, 8), 1125, 1125, 400, 0, 0, 0, 0],
            ['04 CM', round(70500 / 45, 8), 1350, 1350, 400, 0, 0, 0, 0],
            ['2 cm gelang', 66, 281, 281, 133, 0, 0, 500, 0],
            ['2 cm satin lanyard', 190, 337.5, 337.5, 400, 585, 77, 113, 400],
            ['1,5 CM (varian kedua)', round(28000 / 45, 8), 675, 675, 400, 625, 72, 113, 400],
            ['2 CM (varian kedua)', round(30000 / 45, 8), 844, 844, 400, 625, 72, 135, 400],
        ];
        foreach ($bahan as $i => [$nama, $tali, $print1, $print2, $press, $cantolan, $mataItik, $stopper, $finishing]) {
            $rows[] = ['jenis' => 'bahan', 'nama' => $nama, 'biaya' => json_encode(compact('tali', 'print1', 'print2', 'press', 'cantolan', 'mataItik', 'stopper', 'finishing')), 'urutan' => $i, 'created_at' => $now, 'updated_at' => $now];
        }
        $idcards = [
            ['OFFSET 10rb', 300, 70, 200, 125, 100, 50, 50],
            ['OFFSET 20rb', 300, 55, 200, 125, 100, 50, 50],
            ['KONIKA 10 Pcs', 400, 130, 200, 150, 100, 50, 50],
            ['EPSON 10pcs', 580, 25, 220, 0, 100, 50, 50],
            ['RFID 10pcs / KONIKA', 400, 130, 200, 1300, 100, 50, 50],
            ['PROXY 10pcs / KONIKA', 400, 130, 200, 1300, 100, 50, 50],
            ['SILVER 10pcs', 577, 25, 200, 125, 100, 50, 50],
        ];
        foreach ($idcards as $i => [$nama, $kertas, $print, $overlay, $penebal, $plong, $press, $finishing]) {
            $rows[] = ['jenis' => 'idcard', 'nama' => $nama, 'biaya' => json_encode(compact('kertas', 'print', 'overlay', 'penebal', 'plong', 'press', 'finishing')), 'urutan' => $i, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (['Emboss' => 200, 'Panel' => 100, 'Gliter' => 100, 'Foil' => 100, 'Magnetik' => 200] as $nama => $harga) {
            $rows[] = ['jenis' => 'aksesori', 'nama' => $nama, 'biaya' => json_encode(['harga' => $harga]), 'urutan' => count($rows), 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('hpp_referensis')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('hpp_referensis');
    }
};
