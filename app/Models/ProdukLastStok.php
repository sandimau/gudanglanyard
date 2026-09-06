<?php

namespace App\Models;

use App\Services\StokService;
use Illuminate\Database\Eloquent\Model;

class ProdukLastStok extends Model
{
    public $table = 'produk_last_stoks';

    protected $guarded = [];

    public static function latestPerProdukSubquery(?int $cabang_id = null): string
    {
        $cabang_id = (int) resolve_cabang_id($cabang_id);

        return "(
            SELECT pls.produk_id, pls.cabang_id, pls.saldo, pls.tahun
            FROM produk_last_stoks pls
            INNER JOIN (
                SELECT produk_id, cabang_id, MAX(tahun) as max_tahun
                FROM produk_last_stoks
                WHERE cabang_id = {$cabang_id}
                GROUP BY produk_id, cabang_id
            ) sub ON pls.produk_id = sub.produk_id
                AND pls.cabang_id = sub.cabang_id
                AND pls.tahun = sub.max_tahun
        )";
    }

    public static function stok($produk_id, $cabang_id = null): int
    {
        return app(StokService::class)->saldoTersedia($produk_id, $cabang_id);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }
}
