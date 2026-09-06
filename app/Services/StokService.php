<?php

namespace App\Services;

use App\Models\ProdukStok;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StokService
{
    public function tambah(int $produk_id, int $cabang_id, int $jumlah, string $kode, ?string $keterangan = null, ?int $detail_id = null, array $extra = []): ProdukStok
    {
        $this->pastikanJumlahPositif($produk_id, $jumlah);

        return $this->buatMutasi(array_merge([
            'produk_id' => $produk_id,
            'cabang_id' => $cabang_id,
            'tambah' => $jumlah,
            'kurang' => 0,
            'kode' => $kode,
            'keterangan' => $keterangan,
            'detail_id' => $detail_id,
        ], $extra));
    }

    public function kurang(int $produk_id, int $cabang_id, int $jumlah, string $kode, ?string $keterangan = null, ?int $detail_id = null, array $extra = [], bool $validasiStok = true): ProdukStok
    {
        $this->pastikanJumlahPositif($produk_id, $jumlah);

        if ($validasiStok) {
            $this->pastikanStokCukup($produk_id, $cabang_id, $jumlah);
        }

        return $this->buatMutasi(array_merge([
            'produk_id' => $produk_id,
            'cabang_id' => $cabang_id,
            'tambah' => 0,
            'kurang' => $jumlah,
            'kode' => $kode,
            'keterangan' => $keterangan,
            'detail_id' => $detail_id,
        ], $extra));
    }

    public function opname(int $produk_id, int $cabang_id, int $tambah, int $kurang, ?string $keterangan = null, array $extra = []): ProdukStok
    {
        if ($tambah <= 0 && $kurang <= 0) {
            throw ValidationException::withMessages([
                'jumlah' => 'Tambah atau kurang harus lebih dari 0.',
            ]);
        }

        if ($kurang > 0) {
            $this->pastikanStokCukup($produk_id, $cabang_id, $kurang);
        }

        return $this->buatMutasi(array_merge([
            'produk_id' => $produk_id,
            'cabang_id' => $cabang_id,
            'tambah' => max(0, $tambah),
            'kurang' => max(0, $kurang),
            'kode' => 'opn',
            'keterangan' => $keterangan,
        ], $extra));
    }

    public function mpBeli(int $produk_id, int $cabang_id, int $jumlah, string $keterangan, int $detail_id): ProdukStok
    {
        return $this->kurang($produk_id, $cabang_id, $jumlah, 'shp', $keterangan, $detail_id, [], false);
    }

    public function updateLastStok($produk_id, $cabang_id): void
    {
        $cabang_id = resolve_cabang_id($cabang_id);

        if (!DB::table('produks')->where('id', $produk_id)->exists()) {
            return;
        }

        $tahun = (int) date('Y');
        $awalTahun = $tahun . '-01-01 00:00:00';
        $awalTahunDepan = ($tahun + 1) . '-01-01 00:00:00';

        $saldoTahunLalu = (int) DB::table('produk_last_stoks')
            ->where('produk_id', $produk_id)
            ->where('cabang_id', $cabang_id)
            ->where('tahun', '<', $tahun)
            ->orderBy('tahun', 'desc')
            ->value('saldo') ?? 0;

        $mutasiTahunIni = (int) DB::table('produk_stoks')
            ->where('produk_id', $produk_id)
            ->where('cabang_id', $cabang_id)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $awalTahun)
            ->where('created_at', '<', $awalTahunDepan)
            ->selectRaw('COALESCE(SUM(COALESCE(tambah, 0) - COALESCE(kurang, 0)), 0) as saldo')
            ->value('saldo') ?? 0;

        $kunci = [
            'produk_id' => $produk_id,
            'cabang_id' => $cabang_id,
            'tahun' => $tahun,
        ];

        $payload = [
            'saldo' => $saldoTahunLalu + $mutasiTahunIni,
            'updated_at' => now(),
        ];

        $terpengaruh = DB::table('produk_last_stoks')->where($kunci)->update($payload);

        if ($terpengaruh === 0) {
            DB::table('produk_last_stoks')->insertOrIgnore(array_merge($kunci, $payload, [
                'created_at' => now(),
            ]));
        }
    }

    public function saldoTersedia($produk_id, $cabang_id = null): int
    {
        $cabang_id = resolve_cabang_id($cabang_id);
        $tahun = (int) date('Y');
        $awalTahun = $tahun . '-01-01 00:00:00';
        $awalTahunDepan = ($tahun + 1) . '-01-01 00:00:00';

        $saldoTahunLalu = (int) DB::table('produk_last_stoks')
            ->where('produk_id', $produk_id)
            ->where('cabang_id', $cabang_id)
            ->where('tahun', '<', $tahun)
            ->orderBy('tahun', 'desc')
            ->value('saldo') ?? 0;

        $mutasiTahunIni = (int) DB::table('produk_stoks')
            ->where('produk_id', $produk_id)
            ->where('cabang_id', $cabang_id)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $awalTahun)
            ->where('created_at', '<', $awalTahunDepan)
            ->selectRaw('COALESCE(SUM(COALESCE(tambah, 0) - COALESCE(kurang, 0)), 0) as saldo')
            ->value('saldo') ?? 0;

        return $saldoTahunLalu + $mutasiTahunIni;
    }

    public function cekStokCukup(array $kebutuhanPerProduk, $cabang_id = null): array
    {
        $cabang_id = resolve_cabang_id($cabang_id);
        $errors = [];

        foreach ($kebutuhanPerProduk as $produk_id => $jumlah) {
            if ($jumlah <= 0) {
                continue;
            }

            $tersedia = $this->saldoTersedia($produk_id, $cabang_id);

            if ($jumlah > $tersedia) {
                $errors[] = 'Stok tidak cukup untuk ' . $this->labelProduk($produk_id)
                    . '. Tersedia ' . $tersedia . ', diminta ' . $jumlah . '.';
            }
        }

        return $errors;
    }

    private function buatMutasi(array $data): ProdukStok
    {
        $data['cabang_id'] = resolve_cabang_id($data['cabang_id'] ?? null);

        return ProdukStok::create($data);
    }

    private function pastikanJumlahPositif(int $produk_id, int $jumlah): void
    {
        if ($jumlah <= 0) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah untuk ' . $this->labelProduk($produk_id)
                    . ' harus lebih dari 0 (diisi ' . $jumlah . ').',
            ]);
        }
    }

    private function pastikanStokCukup(int $produk_id, int $cabang_id, int $jumlah): void
    {
        if ($jumlah <= 0) {
            return;
        }

        $tersedia = $this->saldoTersedia($produk_id, $cabang_id);

        if ($jumlah > $tersedia) {
            throw ValidationException::withMessages([
                'jumlah' => 'Stok tidak cukup untuk ' . $this->labelProduk($produk_id)
                    . '. Tersedia ' . $tersedia . ', diminta ' . $jumlah . '.',
            ]);
        }
    }

    private function labelProduk(int $produk_id): string
    {
        try {
            $nama = trim((string) DB::table('produks')
                ->leftJoin('produk_models', 'produk_models.id', '=', 'produks.produk_model_id')
                ->where('produks.id', $produk_id)
                ->selectRaw("CONCAT(COALESCE(produk_models.nama, ''), ' ', COALESCE(produks.nama, '')) as label")
                ->value('label'));

            if ($nama !== '') {
                return $nama;
            }
        } catch (\Throwable $e) {
            // abaikan — pakai fallback id
        }

        return 'produk #' . $produk_id;
    }
}
