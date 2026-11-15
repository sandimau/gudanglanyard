<?php

namespace App\Services\Accounting;

use App\Models\ProdukAset;
use App\Models\ProdukAsetDetail;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AsetPenyusutanService
{
    public function __construct(private AsetPenyusutanPostingService $postingService)
    {
    }

    /**
     * Jalankan penyusutan untuk bulan berjalan. Tidak ada prorata tanggal beli
     * dan tidak ada pengejaran bulan yang terlewat, sama seperti jadwal bulanan.
     *
     * @return array{diproses:int, dilewati:int, gagal:int}
     */
    public function jalankan(?CarbonInterface $periode = null): array
    {
        $tanggal = $periode
            ? Carbon::parse($periode)->startOfMonth()
            : now()->startOfMonth();

        $tahun = (int) $tanggal->year;
        $bulan = (int) $tanggal->month;

        $diproses = 0;
        $dilewati = 0;
        $gagal = 0;

        ProdukAset::query()
            ->where('saldo', '>', 0)
            ->orderBy('id')
            ->chunkById(100, function ($asets) use ($tanggal, $tahun, $bulan, &$diproses, &$dilewati, &$gagal) {
                foreach ($asets as $aset) {
                    $sudahAdaSst = $aset->details()
                        ->where('kode', 'sst')
                        ->whereYear('tanggal', $tahun)
                        ->whereMonth('tanggal', $bulan)
                        ->exists();

                    if ($sudahAdaSst) {
                        $dilewati++;
                        continue;
                    }

                    $waktu = (int) $aset->waktuPenyusutan;
                    $nilaiAwal = (int) $aset->harga * (int) $aset->jumlah;

                    if ($waktu <= 0 || $nilaiAwal <= 0) {
                        $dilewati++;
                        continue;
                    }

                    $saldo = $aset->saldoDetail();

                    if ($saldo <= 0) {
                        $saldo = (int) $aset->saldo;
                    }

                    if ($saldo <= 0) {
                        $dilewati++;
                        continue;
                    }

                    $penyusutan = (int) round($nilaiAwal / ($waktu * 12));

                    if ($penyusutan <= 0) {
                        $dilewati++;
                        continue;
                    }

                    if ($penyusutan > $saldo) {
                        $penyusutan = $saldo;
                    }

                    // Sisa di bawah Rp100 ikut dihabiskan pada bulan ini.
                    if ($saldo - $penyusutan < 100) {
                        $penyusutan = $saldo;
                    }

                    try {
                        $detail = DB::transaction(function () use ($aset, $tanggal, $penyusutan) {
                            return ProdukAsetDetail::create([
                                'aset_id' => $aset->id,
                                'tanggal' => $tanggal->toDateString(),
                                'kode' => 'sst',
                                'keterangan' => 'Penyusutan ' . $tanggal->format('m/Y'),
                                'debet' => 0,
                                'kredit' => $penyusutan,
                            ]);
                        });
                    } catch (\Throwable $exception) {
                        $gagal++;
                        Log::error('Gagal membuat detail penyusutan aset.', [
                            'aset_id' => $aset->id,
                            'periode' => $tanggal->format('Y-m'),
                            'exception' => $exception,
                        ]);
                        continue;
                    }

                    $diproses++;

                    try {
                        $this->postingService->post($detail);
                    } catch (\Throwable $exception) {
                        // Detail sst tetap disimpan agar tidak dicoba ulang pada
                        // bulan yang sama, sesuai alur penjadwalan lama.
                        $gagal++;
                        Log::error('Gagal posting jurnal penyusutan aset.', [
                            'aset_id' => $aset->id,
                            'detail_id' => $detail->id,
                            'periode' => $tanggal->format('Y-m'),
                            'exception' => $exception,
                        ]);
                    }
                }
            });

        return [
            'diproses' => $diproses,
            'dilewati' => $dilewati,
            'gagal' => $gagal,
        ];
    }
}
