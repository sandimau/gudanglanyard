<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Belanja extends Model
{
    use SoftDeletes, HasFactory, BelongsToCabang;

    public $table = 'belanjas';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $guarded = [];

    public function akun_detail()
    {
        return $this->belongsTo(AkunDetail::class, 'akun_detail_id');
    }

    public function kontak()
    {
        return $this->belongsTo(Kontak::class);
    }

    public function belanjaDetail()
    {
        return $this->hasMany(BelanjaDetail::class);
    }

    public function getProdukAttribute()
    {
        $yy = array();

        foreach ($this->belanjaDetail as $item) {
            if ($item->produk) {
                $nama_produk = '';
                $nama_produk .= $item->produk->namaLengkap;
                $yy[$item->produk_id] = $nama_produk;
            }
        }
        if (empty($yy)) {
            return 'belum diset';
        } else {
            return implode(', ', $yy);
        }
    }

    public function produksi()
    {
        return $this->belongsToMany(ProduksiProduk::class, 'produk_produksi_belanja', 'belanja_id', 'produksi_id');
    }

    public function po()
    {
        return $this->belongsToMany(Po::class, 'produk_po_belanja', 'belanja_id', 'po_id');
    }

    public function relasiHutang()
    {
        return $this->hasOne(Hutang::class, 'kontak_id', 'kontak_id')
            ->where('jenis', 'belanja')
            ->where('jumlah', $this->total)
            ->where('tanggal', $this->tanggal_beli)
            ->with('details');
    }

    public function getHutangAttribute()
    {
        $hutang = $this->relasiHutang;
        if ($hutang && $hutang->sisa > 0) {
            return $hutang->sisa;
        }
        return 0;
    }

    public function getStatusHutangAttribute()
    {
        $hutang = $this->relasiHutang;
        if ($hutang && $hutang->sisa > 0) {
            return 'belum_lunas';
        }
        return 'lunas';
    }

    public function getTotalBayarAttribute()
    {
        $hutang = $this->relasiHutang;
        if ($hutang) {
            return $hutang->total_bayar;
        }
        return 0;
    }

    public function getSisaHutangAttribute()
    {
        $hutang = $this->relasiHutang;
        if ($hutang) {
            return $hutang->sisa;
        }
        return 0;
    }

    public function getPersentaseBayarAttribute()
    {
        $totalBayar = $this->total_bayar;
        $totalAwal = $this->total;
        if ($totalAwal > 0) {
            return ($totalBayar / $totalAwal) * 100;
        }
        return 0;
    }
}
