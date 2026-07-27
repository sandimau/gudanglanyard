<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AkunDetail extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'akun_details';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function akun_kategori()
    {
        return $this->belongsTo(AkunKategori::class, 'akun_kategori_id');
    }

    public static function TotalKas()
    {
        return self::selectRaw('SUM(saldo) as saldo')
            ->whereHas('akun_kategori', function($q) {
                $q->whereIn('id', [1, 8]);
            })
            ->first()
            ->saldo ?? 0;
    }

    public static function modal()
    {
        return self::selectRaw('SUM(saldo) as saldo')
            ->whereHas('akun_kategori', function($q) {
                $q->whereIn('id', [5]);
            })
            ->first()
            ->saldo ?? 0;
    }

    public function scopeKas($query)
    {
        return $query->whereHas('akun_kategori', function($q) {
            $q->whereIn('id', [1]);
        });
    }

    public function buku_besars()
    {
        return $this->hasMany(BukuBesar::class);
    }

    public function akunLastSaldos()
    {
        return $this->hasMany(AkunLastSaldo::class, 'akun_detail_id');
    }

}
