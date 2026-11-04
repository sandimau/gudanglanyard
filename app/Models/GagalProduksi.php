<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GagalProduksi extends Model
{
    protected $table = 'gagal_produksis';

    protected $fillable = ['order_detail_id', 'produk_id', 'nama_produk', 'cabang_id', 'tanggal', 'jumlah', 'hpp_satuan', 'kerugian', 'tahap', 'keterangan', 'user_id'];

    protected $casts = ['tanggal' => 'date'];

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class)->withTrashed();
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
