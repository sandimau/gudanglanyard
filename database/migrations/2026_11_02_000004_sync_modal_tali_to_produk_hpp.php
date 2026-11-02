<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('hpp_referensis')->where('jenis', 'tali')->whereNotNull('produk_id_tali')->orderBy('id')->get() as $row) {
            $biaya = json_decode($row->biaya, true);
            if (!isset($biaya['modal'])) {
                continue;
            }

            $modal = (float) $biaya['modal'];
            DB::table('produks')->where('id', $row->produk_id_tali)->update([
                'harga_beli_hpp' => $modal,
                'hpp' => (int) round($modal),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Nilai HPP sebelumnya tidak dapat dipulihkan tanpa mengubah riwayat belanja.
    }
};
