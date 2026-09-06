<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabang;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ProduksiProduk extends Model
{
    use BelongsToCabang;

    public $table = 'produksi_produks';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $guarded = [];

    public function getUserAttribute()
    {
        $user = User::find(($this->attributes['user_id']) ?? 0);

        if ($user) {
            return substr($user->email, 0, 5);
        } else {
            return null;
        }
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id')->with('produkModel');
    }

    public function belanja()
    {
        return $this->belongsToMany(Belanja::class, 'produk_produksi_belanja', 'produksi_id', 'belanja_id');
    }

    public function hitungBiaya()
    {
        $biaya = $this->belanja()->sum('total');
        $stok = $this->bahan()->sum(DB::raw('jumlah * hpp'));

        $this->update(['biaya' => $biaya + $stok]);
    }

    public function bahan()
    {
        return $this->hasMany(ProduksiBahan::class, 'produksi_id');
    }

    public function hasilStok()
    {
        return $this->hasMany(ProdukStok::class, 'detail_id', 'id')->where('kode', 'hasilProduksi')->orderBy('id', 'desc');
    }

    public function hasilProduksi()
    {
        return $this->hasMany(ProdukProduksiHasil::class, 'produksi_id');
    }

    public function getHasilProdukAttribute()
    {
        if ($this->hasilProduksi()->count()) {
            return $this->hasilProduksi()->get()->map(function ($item) {
                return $item->produk->nama_lengkap;
            })->implode(', ');
        } else {
            return $this->produk ? $this->produk->nama_lengkap : '-';
        }
    }

    public function cekkomplit()
    {

        $jumlahVarian = $this->hasilProduksi()->groupBy('produk_id')->get()->count();
        $satuan = false;

        foreach ($this->hasilProduksi as $item) {
            if (empty($item->jumlah)) {
                return false;
            }

            if ($jumlahVarian > 1 and empty($item->produkProduksi->perbandingan)) {
                return false;
            }
            if (!$satuan)
                $satuan = $item->produkProduksi->satuan;
            else if ($satuan != $item->produkProduksi->satuan) {
                return false;
            }
        }
        return true;
    }

    /**
     * Cek apakah ada hasil produksi yang masih proses
     */
    public function hasPendingHasil()
    {
        return $this->hasilProduksi()->where('status', 'proses')->exists();
    }

    /**
     * Hitung jumlah hasil produksi yang masih proses
     */
    public function countPendingHasil()
    {
        return $this->hasilProduksi()->where('status', 'proses')->count();
    }

    /**
     * Cek apakah semua hasil produksi sudah selesai
     */
    public function allHasilSelesai()
    {
        return $this->hasilProduksi()->count() > 0 && $this->hasilProduksi()->where('status', 'proses')->count() == 0;
    }

    public function hitungHpp()
    {
        if ($this->cekkomplit()) {

            $biaya = $this->biaya;

            $jumlahVarian = $this->hasilProduksi()->groupBy('produk_id')->get()->count();

            if ($jumlahVarian > 1) {
                $totalPerbandingan = $this->hasilProduksi()->join('produk_produksis', 'produk_produksis.produk_id', '=', 'produk_produksi_hasils.produk_id')->sum(DB::raw('produk_produksis.perbandingan * jumlah'));

                if ($totalPerbandingan > 0) {
                    foreach ($this->hasilProduksi as $item) {
                        $item->hpp = $biaya / $totalPerbandingan * $item->produkProduksi->perbandingan;
                        $item->saveQuietly();
                    }
                }
            } else {
                $totalJumlah = $this->hasilProduksi()->sum('jumlah');

                if ($totalJumlah > 0) {
                    $hpp = $biaya / $totalJumlah;

                    foreach ($this->hasilProduksi as $item) {
                        $item->hpp = $hpp;
                        $item->saveQuietly();
                    }
                }
            }
        }
    }
}
