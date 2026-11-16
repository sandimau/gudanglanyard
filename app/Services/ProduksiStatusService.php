<?php

namespace App\Services;

use App\Models\ProjectMpDetail;
use Illuminate\Support\Facades\DB;

class ProduksiStatusService
{
    /** Mutasi stok penjualan hanya terjadi saat konfirmasi Ambil Barang. */
    public function apply(ProjectMpDetail $detail, int $produksiId): void
    {
        DB::transaction(function () use ($detail, $produksiId) {
            $detail->loadMissing(['produk.produkModel', 'projectMp.marketplace']);

            $detail->update([
                'produksi_id' => $produksiId,
                'hpp' => $detail->produk?->hpp,
            ]);
        });
    }
}
