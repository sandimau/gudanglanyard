<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCustomBahan extends Model
{
    protected $fillable = ['sumber', 'detail_id', 'produk_id', 'jumlah', 'hpp'];

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
}
