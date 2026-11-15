<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukAset extends Model
{
    use HasFactory, BelongsToCabang;

    public $table = 'produk_asets';

    protected $guarded = [];

    protected $casts = [
        'tanggal_beli' => 'date',
        'waktuPenyusutan' => 'integer',
        'harga' => 'integer',
        'jumlah' => 'integer',
        'saldo' => 'integer',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function kontak()
    {
        return $this->belongsTo(Kontak::class, 'kontak_id');
    }

    public function akunDetail()
    {
        return $this->belongsTo(AkunDetail::class, 'akun_detail_id');
    }

    public function jurnal()
    {
        return $this->belongsTo(AkunJurnal::class, 'jurnal_id');
    }

    public function details()
    {
        return $this->hasMany(ProdukAsetDetail::class, 'aset_id')->orderBy('id');
    }

    public function scopeAktif($query)
    {
        return $query->where('saldo', '>', 0);
    }

    public function scopeHabis($query)
    {
        return $query->where('saldo', '<=', 0);
    }

    public function getNilaiAwalAttribute(): int
    {
        return (int) $this->harga * (int) $this->jumlah;
    }

    public function getPenyusutanBulananAttribute(): int
    {
        $waktu = (int) $this->waktuPenyusutan;

        if ($waktu <= 0 || $this->nilai_awal <= 0) {
            return 0;
        }

        return (int) round($this->nilai_awal / ($waktu * 12));
    }

    public function getTotalPenyusutanAttribute(): int
    {
        return (int) $this->details()->where('kode', 'sst')->sum('kredit');
    }

    public function getTotalPenambahanAttribute(): int
    {
        return (int) $this->details()->where('kode', 'pls')->sum('debet');
    }

    public function getNamaProdukAttribute(): string
    {
        $produk = $this->produk;

        if (! $produk) {
            return '-';
        }

        $model = $produk->produkModel;
        $kategori = $model?->kategori?->nama;
        $nama = $model?->nama ?? $produk->nama;
        $varian = $model && $produk->nama ? ' (' . $produk->nama . ')' : '';

        return trim(($kategori ? $kategori . ' - ' : '') . $nama . $varian);
    }

    public function saldoDetail(): int
    {
        return (int) $this->details()
            ->selectRaw('COALESCE(SUM(COALESCE(debet, 0) - COALESCE(kredit, 0)), 0) as saldo')
            ->value('saldo');
    }

    public function recalculateSaldo(): int
    {
        $saldo = $this->saldoDetail();

        $this->forceFill(['saldo' => $saldo])->saveQuietly();

        return $saldo;
    }
}
