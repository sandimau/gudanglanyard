<?php

namespace App\Services\Accounting;

use App\Models\AkunJurnal;
use App\Models\ProdukAset;
use App\Models\ProdukAsetDetail;

class AsetJualPostingService
{
    public function __construct(
        private JurnalService $jurnalService,
        private AsetAkunService $akunService
    ) {
    }

    public function post(
        ProdukAset $aset,
        ProdukAsetDetail $detail,
        int $hargaJual,
        int $akunKasId,
        int $totalPenyusutan,
        int $nilaiAset,
        int $nilaiBuku
    ): AkunJurnal {
        $akunAset = $this->akunService->asetTetap();
        $akunAkumulasi = $this->akunService->akumulasiPenyusutan();
        $selisih = $hargaJual - $nilaiBuku;

        $keterangan = 'Penjualan aset ' . $aset->nama_produk;

        $lines = [
            [
                'akun_detail_id' => $akunKasId,
                'debet' => $hargaJual,
                'kredit' => 0,
                'kode' => 'jual',
                'ket' => $keterangan,
                'detail_id' => $detail->id,
            ],
            [
                'akun_detail_id' => $akunAkumulasi->id,
                'debet' => $totalPenyusutan,
                'kredit' => 0,
                'kode' => 'jual',
                'ket' => $keterangan,
                'detail_id' => $detail->id,
            ],
            [
                'akun_detail_id' => $akunAset->id,
                'debet' => 0,
                'kredit' => $nilaiAset,
                'kode' => 'jual',
                'ket' => $keterangan,
                'detail_id' => $detail->id,
            ],
        ];

        if ($selisih > 0) {
            $lines[] = [
                'akun_detail_id' => $this->akunService->keuntunganPenjualan()->id,
                'debet' => 0,
                'kredit' => $selisih,
                'kode' => 'jual',
                'ket' => 'Keuntungan penjualan aset ' . $aset->nama_produk,
                'detail_id' => $detail->id,
            ];
        } elseif ($selisih < 0) {
            $lines[] = [
                'akun_detail_id' => $this->akunService->kerugianPenjualan()->id,
                'debet' => abs($selisih),
                'kredit' => 0,
                'kode' => 'jual',
                'ket' => 'Kerugian penjualan aset ' . $aset->nama_produk,
                'detail_id' => $detail->id,
            ];
        }

        $jurnal = $this->jurnalService->post([
            'tanggal' => $detail->tanggal,
            'source_type' => 'aset_penjualan',
            'source_id' => $detail->id,
            'keterangan' => $keterangan,
            'cabang_id' => $aset->cabang_id,
            'idempoten_kunci' => 'aset-jual-' . $detail->id,
        ], $lines);

        $detail->forceFill(['jurnal_id' => $jurnal->id])->saveQuietly();

        return $jurnal;
    }
}
