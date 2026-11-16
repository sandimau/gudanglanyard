<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hutang extends Model
{
    protected $fillable = [
        'kontak_id',
        'tanggal',
        'jumlah',
        'keterangan',
        'jenis',
        'akun_detail_id',
        'detail_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jenis' => 'string'
    ];

    public function kontak()
    {
        // Hutang tidak dibatasi cabang; nama kontak transaksi lama tetap perlu terlihat
        // meski cabang aktif berbeda dari cabang kontak tersebut.
        return $this->belongsTo(Kontak::class)->withoutGlobalScope('cabang')->withTrashed();
    }

    public function akun_detail()
    {
        return $this->belongsTo(AkunDetail::class);
    }

    public function details()
    {
        return $this->hasMany(HutangDetail::class);
    }

    public function getTotalBayarAttribute()
    {
        return $this->details->sum('jumlah');
    }

    public function getSisaAttribute()
    {
        return $this->jumlah - $this->total_bayar;
    }

    public function getStatusAttribute()
    {
        return $this->sisa <= 0 ? 'lunas' : 'belum_lunas';
    }
}
