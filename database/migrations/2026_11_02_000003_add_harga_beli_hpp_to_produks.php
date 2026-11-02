<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            $table->decimal('harga_beli_hpp', 18, 8)->nullable();
        });

        foreach (DB::table('hpp_referensis')->where('jenis', 'tali')->whereNotNull('produk_id_tali')->orderBy('id')->get() as $row) {
            $biaya = json_decode($row->biaya, true);
            if (isset($biaya['modal'], $biaya['bagi']) && (float) $biaya['bagi'] > 0) {
                DB::table('produks')->where('id', $row->produk_id_tali)->update([
                    'harga_beli_hpp' => round((float) $biaya['modal'] / (float) $biaya['bagi'], 8),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            $table->dropColumn('harga_beli_hpp');
        });
    }
};
