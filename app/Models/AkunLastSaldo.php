<?php

namespace App\Models;

use App\Services\BukuBesarService;
use Illuminate\Database\Eloquent\Model;

class AkunLastSaldo extends Model
{
    public $table = 'akun_last_saldos';

    protected $guarded = [];

    public static function latestPerAkunSubquery(): string
    {
        return "(
            SELECT als.akun_detail_id, als.saldo, als.tahun
            FROM akun_last_saldos als
            INNER JOIN (
                SELECT akun_detail_id, MAX(tahun) as max_tahun
                FROM akun_last_saldos
                GROUP BY akun_detail_id
            ) sub ON als.akun_detail_id = sub.akun_detail_id AND als.tahun = sub.max_tahun
        )";
    }

    public static function saldo($akun_detail_id): float
    {
        return app(BukuBesarService::class)->saldoTersedia($akun_detail_id);
    }

    public function akunDetail()
    {
        return $this->belongsTo(AkunDetail::class, 'akun_detail_id');
    }
}
