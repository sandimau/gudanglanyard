<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabang;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProduksiBahan extends Model
{
    use BelongsToCabang;

    protected $guarded = [];
    protected $table = "produk_produksi_bahans";

    protected static function boot()
    {
        parent::boot();

        ProduksiBahan::saving(function ($model) {
            $model->hpp = $model->produk->hpp ?? 0;
        });
    }

    public function produkStok()
    {
        return $this->hasOne(ProdukStok::class, 'id', 'produk_stok_id');
    }

    public function produksi()
    {
        return $this->belongsTo(ProduksiProduk::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

}
