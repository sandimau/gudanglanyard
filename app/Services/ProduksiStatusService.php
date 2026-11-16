<?php

namespace App\Services;

use App\Models\OrderDetail;
use App\Models\Produksi;
use App\Models\ProdukCustomResep;
use App\Models\ProjectMpDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProduksiStatusService
{
    /** Berlaku untuk perubahan status manual offline maupun marketplace. */
    public function pastikanBahanDiambil(OrderDetail|ProjectMpDetail $detail, Produksi $tujuan): void
    {
        if (! str_starts_with(mb_strtolower(trim((string) $tujuan->grup)), 'produksi')) {
            return;
        }

        $detail->loadMissing('produk.produkModel');
        if ((int) ($detail->produk?->produkModel?->stok ?? 0) === 1) {
            if (! $detail->pengambilan()->exists()) {
                throw ValidationException::withMessages([
                    'produksi_id' => 'Produk stok belum diambil. Konfirmasi pengambilan di halaman Ambil Barang sebelum masuk grup produksi.',
                ]);
            }
            return;
        }

        if ($detail->bahanCustom()->exists()) {
            if ($detail->bahanCustom()->whereNull('ambil_barang_id')->exists()) {
                throw ValidationException::withMessages([
                    'produksi_id' => 'Bahan resep produk custom belum seluruhnya diambil. Konfirmasi di halaman Ambil Barang sebelum masuk grup produksi.',
                ]);
            }
            return;
        }

        // Detail lama tanpa snapshot tidak diberi resep retroaktif ketika master diubah.
        if ($detail->material_ids === null || $detail->custom_resep_disalin_at
            || ! $detail->produk || ! app(ProdukCustomService::class)->isCustom((int) $detail->produk_id)
            || ! ProdukCustomResep::where('produk_id', $detail->produk_id)->where('jenis', 'bahan')->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'produksi_id' => 'Bahan resep produk custom belum disiapkan. Periksa resep dan SKU pesanan sebelum masuk grup produksi.',
        ]);
    }

    /** Mutasi stok penjualan hanya terjadi saat konfirmasi Ambil Barang. */
    public function apply(ProjectMpDetail $detail, int $produksiId): void
    {
        DB::transaction(function () use ($detail, $produksiId) {
            $detail = ProjectMpDetail::whereKey($detail->id)->lockForUpdate()->firstOrFail();
            $tujuan = Produksi::findOrFail($produksiId);
            $this->pastikanBahanDiambil($detail, $tujuan);
            $detail->loadMissing(['produk.produkModel', 'projectMp.marketplace']);

            $detail->update([
                'produksi_id' => $produksiId,
                'hpp' => app(ProdukCustomService::class)->isCustom((int) $detail->produk_id)
                    ? $detail->hpp : $detail->produk?->hpp,
            ]);
        });
    }
}
