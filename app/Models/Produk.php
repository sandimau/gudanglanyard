<?php

namespace App\Models;

use App\Services\StokService;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    public $table = 'produks';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = ['nama', 'hpp', 'status', 'produk_model_id'];

    public function getNamaLengkapAttribute()
    {
        if ($this->nama) {
            return $this->produkModel->kategori->nama . ' - ' . $this->produkModel->nama . ' (' . $this->nama . ')';
        } else {
            return $this->produkModel->kategori->nama . ' - ' . $this->produkModel->nama;
        }
    }

    public function akunDetail()
    {
        return $this->belongsTo(AkunDetail::class, 'akun_detail_id');
    }

    public function produkLastStoks()
    {
        return $this->hasMany(ProdukLastStok::class, 'produk_id');
    }

    public function LastStokRecord($cabang_id = null)
    {
        $produkId = $this->produk_id ?? $this->id;

        return app(StokService::class)->saldoTersedia($produkId, $cabang_id);
    }

    public function produkModel()
    {
        return $this->belongsTo(ProdukModel::class);
    }

    public function updateHpp($harga, $jumlah, $cabang_id = null)
    {
        $total = ProdukStok::lastStok($this->id, $cabang_id);
        if ($total > 0) {
            $hpp = (($total * $this->hpp) + ($harga * $jumlah)) / ($jumlah + $total);
        } else {
            $hpp = $harga;
        }
        $this->update(['hpp' => $hpp]);
    }
}
