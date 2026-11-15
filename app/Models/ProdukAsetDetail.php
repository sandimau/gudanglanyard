<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukAsetDetail extends Model
{
    use HasFactory;

    public $table = 'produk_aset_details';

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'debet' => 'integer',
        'kredit' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $detail) {
            $detail->aset?->recalculateSaldo();
        });

        static::deleted(function (self $detail) {
            $detail->aset?->recalculateSaldo();
        });
    }

    public function aset()
    {
        return $this->belongsTo(ProdukAset::class, 'aset_id');
    }

    public function jurnal()
    {
        return $this->belongsTo(AkunJurnal::class, 'jurnal_id');
    }

    public function getKodeLabelAttribute(): string
    {
        return match ($this->kode) {
            'sld' => 'Saldo Awal',
            'sst' => 'Penyusutan/Penyesuaian',
            'pls' => 'Penambahan',
            'jual' => 'Penjualan',
            default => strtoupper((string) $this->kode),
        };
    }
}
