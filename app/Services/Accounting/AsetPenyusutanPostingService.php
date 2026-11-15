<?php

namespace App\Services\Accounting;

use App\Models\AkunJurnal;
use App\Models\ProdukAsetDetail;
use RuntimeException;

class AsetPenyusutanPostingService
{
    public function __construct(
        private JurnalService $jurnalService,
        private AsetAkunService $akunService
    ) {
    }

    public function post(ProdukAsetDetail $detail): AkunJurnal
    {
        $detail->loadMissing('aset');

        $nilai = (int) $detail->kredit;

        if ($nilai <= 0) {
            throw new RuntimeException('Nilai penyusutan harus lebih dari nol.');
        }

        $akunBeban = $this->akunService->bebanPenyusutan();
        $akunAkumulasi = $this->akunService->akumulasiPenyusutan();
        $keterangan = 'Penyusutan aset ' . ($detail->aset?->nama_produk ?? '-');

        $jurnal = $this->jurnalService->post([
            'tanggal' => $detail->tanggal,
            'source_type' => 'aset_penyusutan',
            'source_id' => $detail->id,
            'total' => $nilai,
            'keterangan' => $keterangan,
            'cabang_id' => $detail->aset?->cabang_id,
            'idempoten_kunci' => 'aset-susut-' . $detail->id,
        ], [
            [
                'akun_detail_id' => $akunBeban->id,
                'debet' => $nilai,
                'kredit' => 0,
                'kode' => 'sst',
                'ket' => $keterangan,
                'detail_id' => $detail->id,
            ],
            [
                'akun_detail_id' => $akunAkumulasi->id,
                'debet' => 0,
                'kredit' => $nilai,
                'kode' => 'sst',
                'ket' => $keterangan,
                'detail_id' => $detail->id,
            ],
        ]);

        $detail->forceFill(['jurnal_id' => $jurnal->id])->saveQuietly();

        return $jurnal;
    }
}
