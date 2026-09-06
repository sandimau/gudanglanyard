<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukPakai extends Model
{
    use HasFactory, BelongsToCabang;

    public $table = 'produk_pakais';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'produk_id',
        'jumlah',
        'keterangan',
        'cabang_id',
        'user_id',
        'hpp',
        'produk_stok_id',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function produkStok()
    {
        return $this->belongsTo(ProdukStok::class, 'produk_stok_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

