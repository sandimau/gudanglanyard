<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bahan_pakais') || !Schema::hasTable('produk_pakais')) {
            return;
        }

        // Mutasi stok sudah dibuat saat catatan lama disimpan: jangan kurangi stok lagi.
        DB::table('bahan_pakais')->orderBy('id')->chunkById(200, function ($records) {
            foreach ($records as $record) {
                if ($record->produk_stok_id && DB::table('produk_pakais')
                    ->where('produk_stok_id', $record->produk_stok_id)->exists()) {
                    continue;
                }

                DB::table('produk_pakais')->insert([
                    'produk_id' => $record->produk_id,
                    'cabang_id' => $record->cabang_id,
                    'produk_stok_id' => $record->produk_stok_id,
                    'user_id' => $record->user_id,
                    'jumlah' => $record->jumlah,
                    'hpp' => $record->hpp,
                    'harga' => $record->harga,
                    'keterangan' => $record->keterangan,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Tidak menghapus catatan pemakaian yang sudah dipindahkan.
    }
};
