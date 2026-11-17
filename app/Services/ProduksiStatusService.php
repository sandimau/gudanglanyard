<?php

namespace App\Services;

use App\Models\OrderDetail;
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
     *
     * Order marketplace dengan produk berstok sudah dipotong saat order masuk
     * dari webhook, jadi perubahan status tidak memotongnya lagi. Produk custom
     * memotong stok bahan resepnya karena produk jadi tidak mencatat stok.
     */
    public function apply(OrderDetail|ProjectMpDetail $detail, int $produksiId): void
    {
        DB::transaction(function () use ($detail, $produksiId) {
            $detail = $detail instanceof OrderDetail
                ? OrderDetail::whereKey($detail->id)->lockForUpdate()->firstOrFail()
                : ProjectMpDetail::whereKey($detail->id)->lockForUpdate()->firstOrFail();

            $detail->loadMissing([
                'produk.produkModel',
                'bahanCustom',
                $detail instanceof OrderDetail ? 'order.kontak' : 'projectMp.marketplace',
            ]);

            $from = Produksi::find($detail->produksi_id);
            $to = Produksi::find($produksiId);

            if ($from && $to) {
                $this->sesuaikanStok($detail, $from, $to);
            }

            $detail->update([
                'produksi_id' => $produksiId,
                'hpp' => app(ProdukCustomService::class)->isCustom((int) $detail->produk_id)
                    ? $detail->hpp : $detail->produk?->hpp,
            ]);
        });
    }

    private function sesuaikanStok(OrderDetail|ProjectMpDetail $detail, Produksi $from, Produksi $to): void
    {
        [$cabangId, $parentId, $keterangan] = $this->konteksMutasi($detail);

        if ($detail->bahanCustom->isNotEmpty()) {
            if (Produksi::shouldDeductStock($from, $to)) {
                foreach ($detail->bahanCustom as $bahan) {
                    $this->stokService->kurang(
                        (int) $bahan->produk_id,
                        $cabangId,
                        (int) $bahan->jumlah,
                        'jual',
                        'bahan custom ' . $keterangan,
                        $parentId,
                        [],
                        false
                    );
                }
            }

            if (Produksi::shouldRestoreStock($from, $to)) {
                foreach ($detail->bahanCustom as $bahan) {
                    $this->stokService->tambah(
                        (int) $bahan->produk_id,
                        $cabangId,
                        (int) $bahan->jumlah,
                        'btl',
                        'bahan custom dikembalikan dari ' . $keterangan,
                        $parentId
                    );
                }
            }

            return;
        }

        if (! Produksi::produkTracksStock($detail)) {
            return;
        }

        // Stok produk marketplace sudah dipotong saat webhook membuat detail.
        if ($detail instanceof ProjectMpDetail) {
            return;
        }

        if (Produksi::shouldDeductStock($from, $to)) {
            $this->stokService->kurang(
                (int) $detail->produk->id,
                $cabangId,
                (int) $detail->jumlah,
                'jual',
                'barang dijual ke ' . $keterangan,
                $parentId,
                [],
                false
            );
        }

        if (Produksi::shouldRestoreStock($from, $to)) {
            $this->stokService->tambah(
                (int) $detail->produk->id,
                $cabangId,
                (int) $detail->jumlah,
                'btl',
                'barang dikembalikan dari ' . $keterangan,
                $parentId
            );
        }
    }

    /** @return array{0:int,1:int,2:string} cabang, id dokumen induk, label keterangan */
    private function konteksMutasi(OrderDetail|ProjectMpDetail $detail): array
    {
        if ($detail instanceof OrderDetail) {
            $order = $detail->order;
            $cabangId = resolve_cabang_id($order?->cabang_id ?? $order?->kontak?->cabang_id);
            $username = $order?->konsumen_detail ? '(' . $order->konsumen_detail . ')' : '';

            return [
                $cabangId,
                (int) $detail->order_id,
                ($order?->kontak?->nama ?? '-') . ' ' . $username,
            ];
        }

        $projectMp = $detail->projectMp;
        $cabangId = resolve_cabang_id($projectMp?->cabang_id ?? $projectMp?->marketplace?->cabang_id);
        $username = $projectMp?->konsumen ? '(' . $projectMp->konsumen . ')' : '';

        return [
            $cabangId,
            (int) $detail->project_id,
            ($projectMp?->marketplace?->nama ?? '-') . ' ' . $username,
        ];
    }
}
