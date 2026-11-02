<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hpp_referensis', function (Blueprint $table) {
            $table->foreignId('bahan_id_tali')->nullable()->constrained('hpp_referensis')->nullOnDelete();
        });

        foreach ([
            'tali isu 1,5CM' => '1,5 CM',
            'tali isu 2CM Grade A' => '2 CM',
            'tali isu 2,5CM' => '2,5 CM',
            'tali isu 3CM' => '03 CM',
            'tali isu 4CM' => '04 CM',
        ] as $tali => $bahan) {
            $bahanId = DB::table('hpp_referensis')->where('jenis', 'bahan')->where('nama', $bahan)->value('id');
            if ($bahanId) {
                foreach (DB::table('hpp_referensis')->where('jenis', 'tali')->where('nama', $tali)->get() as $item) {
                    DB::table('hpp_referensis')->where('id', $item->id)->update(['bahan_id_tali' => $bahanId]);
                    $rincian = json_decode($item->biaya, true);
                    if (!isset($rincian['modal'], $rincian['bagi']) || (float) $rincian['bagi'] <= 0) {
                        continue;
                    }
                    $bahanRow = DB::table('hpp_referensis')->where('id', $bahanId)->first();
                    $biaya = json_decode($bahanRow->biaya, true);
                    $biaya['tali'] = round((float) $rincian['modal'] / (float) $rincian['bagi'], 8);
                    DB::table('hpp_referensis')->where('id', $bahanId)->update(['biaya' => json_encode($biaya), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('hpp_referensis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bahan_id_tali');
        });
    }
};
