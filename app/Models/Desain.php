<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desain extends Model
{
    protected $fillable = ['nama_konsumen', 'produk_id', 'tema', 'tipe', 'keterangan', 'desain_tab_id'];

    public function tab(): BelongsTo
    {
        return $this->belongsTo(DesainTab::class, 'desain_tab_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function getProdukLabelAttribute(): string
    {
        if (!$this->produk) {
            return '-';
        }

        return collect([
            $this->produk->produkModel?->kategori?->nama,
            $this->produk->produkModel?->nama,
            $this->produk->nama,
        ])->filter(fn ($value) => $value !== null && $value !== '')->implode(' - ');
    }

    public function getTipeLabelAttribute(): string
    {
        return match ($this->tipe) {
            'online' => 'Online',
            'offline' => 'Offline',
            default => '-',
        };
    }
}
