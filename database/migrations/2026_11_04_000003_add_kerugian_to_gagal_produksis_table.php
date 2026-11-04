<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gagal_produksis', function (Blueprint $table) {
            $table->unsignedBigInteger('hpp_satuan')->default(0);
            $table->unsignedBigInteger('kerugian')->default(0);
        });

        // Catatan yang sudah dibuat sebelum kolom ini tersedia memakai HPP order saat ini.
        DB::table('gagal_produksis')->orderBy('id')->chunkById(200, function ($catatan) {
            $hpp = DB::table('order_details')
                ->whereIn('id', $catatan->pluck('order_detail_id'))
                ->pluck('hpp', 'id');

            foreach ($catatan as $item) {
                $satuan = max(0, (int) ($hpp[$item->order_detail_id] ?? 0));
                DB::table('gagal_produksis')->where('id', $item->id)->update([
                    'hpp_satuan' => $satuan,
                    'kerugian' => $satuan * $item->jumlah,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('gagal_produksis', function (Blueprint $table) {
            $table->dropColumn(['hpp_satuan', 'kerugian']);
        });
    }
};
