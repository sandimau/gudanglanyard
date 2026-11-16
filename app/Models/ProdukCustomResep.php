<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukCustomResep extends Model
{
    protected $fillable = ['produk_id', 'jenis', 'komponen_model_id', 'jumlah', 'keterangan', 'biaya', 'per_order'];

    public function komponenModel()
    {
        return $this->belongsTo(ProdukModel::class, 'komponen_model_id');
    }
}
