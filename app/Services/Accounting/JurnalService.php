<?php

namespace App\Services\Accounting;

use App\Models\AkunDetail;
use App\Models\AkunJurnal;
use App\Models\AkunJurnalAuditLog;
use App\Models\BukuBesar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JurnalService
{
    /**
     * Simpan satu jurnal beserta baris buku besar. Bila idempoten_kunci sudah
     * pernah dipakai, jurnal lama dikembalikan tanpa membuat baris baru.
     */
    public function post(array $header, array $lines): AkunJurnal
    {
        $this->validasiBaris($lines);

        $kunci = $header['idempoten_kunci'] ?? null;

        if ($kunci) {
            $existing = AkunJurnal::where('idempoten_kunci', $kunci)->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($header, $lines, $kunci) {
            if ($kunci) {
                $existing = AkunJurnal::where('idempoten_kunci', $kunci)->lockForUpdate()->first();
                if ($existing) {
                    return $existing;
                }
            }

            $tanggal = isset($header['tanggal'])
                ? Carbon::parse($header['tanggal'])
                : now();

            $totalDebet = collect($lines)->sum(fn ($line) => (int) ($line['debet'] ?? 0));
            $totalKredit = collect($lines)->sum(fn ($line) => (int) ($line['kredit'] ?? 0));

            if ($totalDebet !== $totalKredit || $totalDebet <= 0) {
                throw new RuntimeException('Jurnal tidak seimbang: total debet harus sama dengan total kredit dan lebih dari nol.');
            }

            $jurnal = AkunJurnal::create([
                'nomor' => $header['nomor'] ?? $this->nomorBaru($tanggal),
                'tanggal' => $tanggal->toDateString(),
                'source_type' => $header['source_type'] ?? null,
                'source_id' => $header['source_id'] ?? null,
                'source_batch_filter' => $header['source_batch_filter'] ?? null,
                'total' => $header['total'] ?? $totalDebet,
                'keterangan' => $header['keterangan'] ?? null,
                'cabang_id' => $header['cabang_id'] ?? null,
                'user_id' => $header['user_id'] ?? auth()->id(),
                'status' => $header['status'] ?? 'posted',
                'posted_at' => $header['posted_at'] ?? now(),
                'reverses_jurnal_id' => $header['reverses_jurnal_id'] ?? null,
                'idempoten_kunci' => $kunci,
                'idempoten_aktif' => $header['idempoten_aktif'] ?? true,
            ]);

            foreach ($lines as $line) {
                BukuBesar::create([
                    'akun_detail_id' => $line['akun_detail_id'],
                    'kode' => $line['kode'] ?? 'ast',
                    'ket' => $line['ket'] ?? $header['keterangan'] ?? null,
                    'debet' => (int) ($line['debet'] ?? 0),
                    'kredit' => (int) ($line['kredit'] ?? 0),
                    'detail_id' => $line['detail_id'] ?? $header['source_id'] ?? null,
                    'jurnal_id' => $jurnal->id,
                ]);
            }

            $this->catatAudit($jurnal, 'posted', null, $jurnal->fresh('bukuBesar')->toArray());

            return $jurnal;
        });
    }

    /**
     * Buat jurnal pembalik untuk jurnal yang sudah diposting.
     */
    public function balik(AkunJurnal $jurnal, ?string $keterangan = null): AkunJurnal
    {
        if ($jurnal->reversed_by_jurnal_id) {
            return $jurnal->reversedBy;
        }

        $jurnal->loadMissing('bukuBesar');

        $lines = $jurnal->bukuBesar->map(fn (BukuBesar $baris) => [
            'akun_detail_id' => $baris->akun_detail_id,
            'kode' => 'rvs',
            'ket' => 'Pembalik jurnal ' . $jurnal->nomor,
            'debet' => (int) $baris->kredit,
            'kredit' => (int) $baris->debet,
            'detail_id' => $baris->detail_id,
        ])->all();

        $pembalik = $this->post([
            'tanggal' => now()->toDateString(),
            'source_type' => 'reversal',
            'source_id' => $jurnal->id,
            'keterangan' => $keterangan ?? ('Pembalik jurnal ' . $jurnal->nomor),
            'cabang_id' => $jurnal->cabang_id,
            'reverses_jurnal_id' => $jurnal->id,
            'idempoten_kunci' => 'reversal-' . $jurnal->id,
        ], $lines);

        $jurnal->update([
            'status' => 'reversed',
            'reversed_by_jurnal_id' => $pembalik->id,
        ]);

        $this->catatAudit($jurnal, 'reversed', $jurnal->toArray(), $pembalik->toArray());

        return $pembalik;
    }

    private function validasiBaris(array $lines): void
    {
        if (count($lines) < 2) {
            throw new RuntimeException('Jurnal membutuhkan minimal dua baris.');
        }

        $semuaAkun = collect($lines)->pluck('akun_detail_id');

        if ($semuaAkun->contains(fn ($id) => blank($id))) {
            throw new RuntimeException('Setiap baris jurnal harus memiliki akun.');
        }

        $akunIds = $semuaAkun->unique();
        $ditemukan = AkunDetail::whereIn('id', $akunIds)->count();

        if ($ditemukan !== $akunIds->count()) {
            throw new RuntimeException('Ada akun jurnal yang tidak ditemukan.');
        }
    }

    private function nomorBaru(Carbon $tanggal): string
    {
        $prefix = 'JRN-' . $tanggal->format('Ymd') . '-';
        $terakhir = AkunJurnal::query()
            ->where('nomor', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('nomor')
            ->value('nomor');

        $urutan = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix . str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }

    private function catatAudit(AkunJurnal $jurnal, string $action, ?array $before, ?array $after): void
    {
        AkunJurnalAuditLog::create([
            'jurnal_id' => $jurnal->id,
            'action' => $action,
            'user_id' => auth()->id(),
            'before_state' => $before,
            'after_state' => $after,
        ]);
    }
}
