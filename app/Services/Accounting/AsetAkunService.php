<?php

namespace App\Services\Accounting;

use App\Models\AkunDetail;
use App\Models\AkunKategori;
use RuntimeException;

class AsetAkunService
{
    public function asetTetap(): AkunDetail
    {
        return $this->resolve(
            ['12101 Aset Tetap', 'Aset Tetap', 'Aset tetap', 'aset tetap'],
            'aset tetap',
            3,
            '12101 Aset Tetap'
        );
    }

    public function akumulasiPenyusutan(): AkunDetail
    {
        return $this->resolve(
            [
                '12201 Akumulasi Penyusutan Aset Tetap',
                'Akumulasi Penyusutan Aset Tetap',
                'Akumulasi Penyusutan',
            ],
            'aset tetap',
            3,
            '12201 Akumulasi Penyusutan Aset Tetap'
        );
    }

    public function bebanPenyusutan(): AkunDetail
    {
        return $this->resolve(
            [
                '52301 Beban Penyusutan Aset Tetap',
                'Beban Penyusutan Aset Tetap',
                'Beban Penyusutan',
            ],
            'operasional',
            7,
            '52301 Beban Penyusutan Aset Tetap'
        );
    }

    public function hutangUsaha(): AkunDetail
    {
        return $this->resolve(
            ['21101 Hutang Usaha', 'Hutang Usaha', 'Hutang usaha', 'hutang usaha'],
            'hutang',
            4,
            '21101 Hutang Usaha'
        );
    }

    public function keuntunganPenjualan(): AkunDetail
    {
        return $this->resolve(
            [
                '42101 Keuntungan Penjualan Aset',
                'Keuntungan Penjualan Aset',
                'Keuntungan Penjualan',
            ],
            'pendapatan',
            6,
            '42101 Keuntungan Penjualan Aset'
        );
    }

    public function kerugianPenjualan(): AkunDetail
    {
        return $this->resolve(
            [
                '55101 Kerugian Penjualan Aset',
                'Kerugian Penjualan Aset',
                'Kerugian Penjualan',
            ],
            'operasional',
            7,
            '55101 Kerugian Penjualan Aset'
        );
    }

    private function resolve(array $aliases, string $kategoriNama, int $kategoriFallbackId, string $namaDefault): AkunDetail
    {
        $akun = AkunDetail::query()
            ->whereIn('nama', $aliases)
            ->orderBy('id')
            ->first();

        if ($akun) {
            return $akun;
        }

        $kategori = AkunKategori::query()
            ->where('nama', $kategoriNama)
            ->orderBy('id')
            ->first()
            ?? AkunKategori::find($kategoriFallbackId);

        if (! $kategori) {
            throw new RuntimeException("Kategori akun '{$kategoriNama}' belum tersedia.");
        }

        return AkunDetail::create([
            'nama' => $namaDefault,
            'saldo' => 0,
            'akun_kategori_id' => $kategori->id,
        ]);
    }
}
