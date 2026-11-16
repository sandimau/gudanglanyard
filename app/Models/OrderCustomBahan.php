<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCustomBahan extends Model
{
    protected $fillable = ['sumber', 'detail_id', 'produk_id', 'jumlah', 'hpp', 'ambil_barang_id'];

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function pengambilan()
    {
        return $this->belongsTo(AmbilBarang::class, 'ambil_barang_id');
    }
}
