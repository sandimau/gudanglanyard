<?php

namespace App\Services;

use App\Models\BukuBesar;
use Illuminate\Support\Facades\DB;

class BukuBesarService
{
    public function debet(int $akun_detail_id, $jumlah, string $kode, ?string $ket = null, ?int $detail_id = null, array $extra = []): BukuBesar
    {
        return $this->buatMutasi(array_merge([
            'akun_detail_id' => $akun_detail_id,
            'debet' => $jumlah,
            'kredit' => 0,
            'kode' => $kode,
            'ket' => $ket,
            'detail_id' => $detail_id,
        ], $extra));
    }

    public function kredit(int $akun_detail_id, $jumlah, string $kode, ?string $ket = null, ?int $detail_id = null, array $extra = []): BukuBesar
    {
        return $this->buatMutasi(array_merge([
            'akun_detail_id' => $akun_detail_id,
            'debet' => 0,
            'kredit' => $jumlah,
            'kode' => $kode,
            'ket' => $ket,
            'detail_id' => $detail_id,
        ], $extra));
    }

    public function updateLastSaldo($akun_detail_id): void
    {
        if (!$akun_detail_id || !DB::table('akun_details')->where('id', $akun_detail_id)->exists()) {
            return;
        }

        $tahun = (int) date('Y');

        $saldoTahunLalu = (float) (DB::table('akun_last_saldos')
            ->where('akun_detail_id', $akun_detail_id)
            ->where('tahun', '<', $tahun)
            ->orderBy('tahun', 'desc')
            ->value('saldo') ?? 0);

        $mutasiTahunIni = (float) (DB::table('buku_besars')
            ->where('akun_detail_id', $akun_detail_id)
            ->whereYear('created_at', $tahun)
            ->selectRaw('COALESCE(SUM(COALESCE(debet, 0) - COALESCE(kredit, 0)), 0) as saldo')
            ->value('saldo') ?? 0);

        $saldo = $saldoTahunLalu + $mutasiTahunIni;

        $payload = [
            'saldo' => $saldo,
            'updated_at' => now(),
        ];

        $exists = DB::table('akun_last_saldos')
            ->where('akun_detail_id', $akun_detail_id)
            ->where('tahun', $tahun)
            ->exists();

        if ($exists) {
            DB::table('akun_last_saldos')
                ->where('akun_detail_id', $akun_detail_id)
                ->where('tahun', $tahun)
                ->update($payload);
        } else {
            DB::table('akun_last_saldos')->insert(array_merge($payload, [
                'akun_detail_id' => $akun_detail_id,
                'tahun' => $tahun,
                'created_at' => now(),
            ]));
        }

        DB::table('akun_details')
            ->where('id', $akun_detail_id)
            ->update(['saldo' => $saldo]);
    }

    public function saldoTersedia($akun_detail_id): float
    {
        $tahun = (int) date('Y');

        $saldoTahunLalu = (float) (DB::table('akun_last_saldos')
            ->where('akun_detail_id', $akun_detail_id)
            ->where('tahun', '<', $tahun)
            ->orderBy('tahun', 'desc')
            ->value('saldo') ?? 0);

        $mutasiTahunIni = (float) (DB::table('buku_besars')
            ->where('akun_detail_id', $akun_detail_id)
            ->whereYear('created_at', $tahun)
            ->selectRaw('COALESCE(SUM(COALESCE(debet, 0) - COALESCE(kredit, 0)), 0) as saldo')
            ->value('saldo') ?? 0);

        return $saldoTahunLalu + $mutasiTahunIni;
    }

    private function buatMutasi(array $data): BukuBesar
    {
        return BukuBesar::create($data);
    }
}
