<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabang;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use SoftDeletes, HasFactory, BelongsToCabang;

    public $table = 'orders';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        Order::saving(function ($model) {

            $total = 0;

            $batal = Produksi::where('nama', 'batal')->first()->id;

            foreach ($model->orderDetail as $detail) {

                if ($detail->produksi_id != $batal) {
                    $total += $detail->jumlah * $detail->harga;
                }
            }
            $model->total = $total - $model->diskon + $model->ongkir;

        });
    }

    public function getKekuranganAttribute()
    {
        return $this->total - $this->bayar;
    }

    public function kontak()
    {
        // Relasi by FK — tetap load meskipun scope cabang kontak berbeda
        return $this->belongsTo(Kontak::class, 'kontak_id')->withoutGlobalScope('cabang');
    }

    public function pemproses()
    {
        return $this->belongsTo(Pemproses::class, 'pemproses_id');
    }

    public function orderDetail()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function projectMp()
    {
        return $this->hasOne(ProjectMp::class, 'order_id');
    }

    public function scopeBelumLunas($query)
    {
        $query->where(function($q) {
            $q->where(function($subq) {
                $subq->whereNull('marketplace')
                    ->whereRaw('total > bayar');
            });
        });
        $query->orderBy('id','desc');
        return $query;
    }

    public function getListprodukAttribute()
    {
        $yy = array();
        foreach ($this->orderDetail as $item) {
            $nama_produk = '';
            $nama_produk .= $item->produk->namaLengkap;
            $yy[] = $nama_produk;
        }
        return implode(', ', $yy);
    }

    public function scopeOmzetTahun($query)
    {
        $query->select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('SUM(total) as sum')
        );
        $query->where('total', '>', 0);
        $query->groupBy(DB::raw('YEAR(created_at)'));
        $query->orderBy(DB::raw('YEAR(created_at)'));
        return $query;
    }

    public function scopeOmzetBulan($query)
    {
        $query->select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('EXTRACT(YEAR_MONTH FROM created_at) as month'),
            DB::raw('MONTHNAME(created_at) as monthname'),
            DB::raw('SUM(total) as omzet')
        );
        $query->where('total', '>', 0);
        $query->groupBy(
            DB::raw('YEAR(created_at)'),
            DB::raw('EXTRACT(YEAR_MONTH FROM created_at)'),
            DB::raw('MONTHNAME(created_at)')
        );
        $query->orderBy(DB::raw('EXTRACT(YEAR_MONTH FROM created_at)'));
        return $query;
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }
}
