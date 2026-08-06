<?php

namespace App\Services;

use App\Models\Produksi;
use App\Models\ProjectMpDetail;
use Illuminate\Support\Facades\DB;

class ProduksiStatusService
{
    public function __construct(private StokService $stokService)
    {
    }

    /**
     * Pindahkan status produksi sebuah detail order, termasuk penyesuaian
     * stok (kurang saat keluar dari grup "awal", tambah saat dibatalkan
     * dari grup "Selesai").
     */
    public function apply(ProjectMpDetail $detail, int $produksiId): void
    {
        DB::transaction(function () use ($detail, $produksiId) {
            $detail->loadMissing(['produk.produkModel', 'projectMp.marketplace']);

            $from = Produksi::find($detail->produksi_id);
            $to = Produksi::find($produksiId);

            if (Produksi::produkTracksStock($detail) && $from && $to) {
                if ($detail->projectMp?->konsumen) {
                    $username = '(' . $detail->projectMp->konsumen . ')';
                } else {
                    $username = '';
                }

                if (Produksi::shouldDeductStock($from, $to)) {
                    $this->stokService->kurang(
                        $detail->produk->id,
                        $detail->jumlah,
                        'jual',
                        'barang dijual ke ' . ($detail->projectMp?->marketplace?->nama ?? '-') . ' ' . $username,
                        $detail->projectMp?->id,
                        [],
                        false
                    );
                }

                if (Produksi::shouldRestoreStock($from, $to)) {
                    $this->stokService->tambah(
                        $detail->produk->id,
                        $detail->jumlah,
                        'btl',
                        'barang dikembalikan dari ' . ($detail->projectMp?->kontak?->nama ?? '-') . ' ' . $username,
                        $detail->projectMp?->id
                    );
                }
            }

            $detail->update([
                'produksi_id' => $produksiId,
                'hpp' => $detail->produk?->hpp,
            ]);
        });
    }
}
