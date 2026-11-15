<?php

namespace App\Services\Accounting;

use App\Models\AkunDetail;
use App\Models\AkunJurnal;
use App\Models\ProdukAset;
use RuntimeException;

class AsetPostingService
{
    public function __construct(
        private JurnalService $jurnalService,
        private AsetAkunService $akunService
    ) {
    }

    public function postPembelian(ProdukAset $aset): AkunJurnal
    {
        $aset->loadMissing(['akunDetail', 'produk.produkModel.kategori']);

        $nilai = (int) $aset->harga * (int) $aset->jumlah;

        if ($nilai <= 0) {
            throw new RuntimeException('Nilai aset harus lebih dari nol.');
        }

        $akunAset = $this->akunService->asetTetap();

        if ($aset->akun_detail_id) {
            $akunKredit = AkunDetail::find($aset->akun_detail_id);

            if (! $akunKredit) {
                throw new RuntimeException('Akun kas pembayaran aset tidak ditemukan.');
            }
        } else {
            $akunKredit = $this->akunService->hutangUsaha();
        }

        $keterangan = 'Pembelian aset ' . $aset->nama_produk;

        $jurnal = $this->jurnalService->post([
            'tanggal' => $aset->tanggal_beli,
            'source_type' => 'aset_pembelian',
            'source_id' => $aset->id,
            'total' => $nilai,
            'keterangan' => $keterangan,
            'cabang_id' => $aset->cabang_id,
            'idempoten_kunci' => 'aset-beli-' . $aset->id,
        ], [
            [
                'akun_detail_id' => $akunAset->id,
                'debet' => $nilai,
                'kredit' => 0,
                'kode' => 'ast',
                'ket' => $keterangan,
                'detail_id' => $aset->id,
            ],
            [
                'akun_detail_id' => $akunKredit->id,
                'debet' => 0,
                'kredit' => $nilai,
                'kode' => 'ast',
                'ket' => $keterangan,
                'detail_id' => $aset->id,
            ],
        ]);

        $aset->forceFill(['jurnal_id' => $jurnal->id])->saveQuietly();

        return $jurnal;
    }
}
