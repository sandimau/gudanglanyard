<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmbilBarang extends Model
{
    protected $table = 'ambil_barangs';

    public $timestamps = false;

    protected $guarded = [];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
