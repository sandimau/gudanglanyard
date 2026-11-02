<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (DB::table('hpp_referensis')->where('jenis', 'tali')->whereNotNull('produk_id_tali')->orderBy('id')->get() as $row) {
            $harga = DB::table('belanja_details')->where('produk_id', $row->produk_id_tali)
                ->whereNull('deleted_at')->orderByDesc('id')->value('harga');
            $biaya = json_decode($row->biaya, true);
            $biaya['modal'] = $harga === null ? null : (float) $harga;
            DB::table('hpp_referensis')->where('id', $row->id)->update(['biaya' => json_encode($biaya), 'updated_at' => now()]);

            // Hapus override harga beli dari migrasi sebelumnya; kembalikan tampilan ke data belanja.
            DB::table('produks')->where('id', $row->produk_id_tali)->update(['harga_beli_hpp' => null]);
            if ($harga !== null) {
                // HPP sebelumnya sempat ditimpa Modal; harga beli terakhir adalah nilai belanja yang tersedia untuk pemulihan.
                DB::table('produks')->where('id', $row->produk_id_tali)->update(['hpp' => (int) round((float) $harga)]);
            }
            if ($row->bahan_id_tali && $harga !== null && (float) ($biaya['bagi'] ?? 0) > 0) {
                $bahan = DB::table('hpp_referensis')->where('id', $row->bahan_id_tali)->first();
                if ($bahan && $bahan->jenis === 'bahan') {
                    $rincian = json_decode($bahan->biaya, true);
                    $rincian['tali'] = round((float) $harga / (float) $biaya['bagi'], 8);
                    DB::table('hpp_referensis')->where('id', $bahan->id)->update(['biaya' => json_encode($rincian), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        // Nilai override lama tidak dapat dipulihkan secara aman.
    }
};
