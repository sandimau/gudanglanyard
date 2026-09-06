<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabang;
use Illuminate\Database\Eloquent\Model;

class Po extends Model
{
    use BelongsToCabang;

    protected $guarded = [];
    protected $table = "produk_po";

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            if (auth()->check()) {
                $model->user_id = auth()->user()->id;
            }
        });
    }

    public function scopeProses($query)
    {
        return $query->where('status', 'proses');
    }

    public function scopeFinish($query)
    {
        return $query->where('status', 'finish');
    }

    public function getProdukAttribute()
    {
        $yy = array();

        foreach ($this->poDetail as $item) {
            if ($item->produk) {
                $yy[$item->produk_id] = $item->produk->namaLengkap;
            }
        }
        if (empty($yy)) {
            return 'belum diset';
        } else {
            return implode(', ', $yy);
        }
    }

    public function belanja()
    {
        return $this->belongsToMany(Belanja::class, 'produk_po_belanja', 'po_id', 'belanja_id');
    }

    public function kontak()
    {
        return $this->belongsTo(Kontak::class);
    }

    public function poDetail()
    {
        return $this->hasMany(PoDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hutang()
    {
        return $this->belongsToMany(Hutang::class, 'po_deposit', 'po_id', 'hutang_id');
    }

    public function getTotalHutangBelanjaAttribute()
    {
        return $this->belanja->sum('hutang');
    }

        public function getStatusHutangBelanjaAttribute()
    {
        $totalHutang = $this->total_hutang_belanja;

        if ($totalHutang > 0) {
            return 'ada_hutang';
        }

        return 'lunas';
    }

    public function getTotalBayarBelanjaAttribute()
    {
        return $this->belanja->sum('total_bayar');
    }

    public function getTotalHutangAwalBelanjaAttribute()
    {
        return $this->belanja->sum('total');
    }

    public function getPersentaseBayarBelanjaAttribute()
    {
        $totalBayar = $this->total_bayar_belanja;
        $totalHutang = $this->total_hutang_belanja;
        $totalAwal = $totalBayar + $totalHutang;

        if ($totalAwal > 0) {
            return ($totalBayar / $totalAwal) * 100;
        }

        return 0;
    }
}
