<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDetail extends Model
{
    use SoftDeletes;

    public $table = 'order_details';

    protected $appends = [
        'gambar',
    ];

    protected $dates = [
        'deadline',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        OrderDetail::saved(function ($model) {
            $model->order->update([]);
        });

        OrderDetail::deleted(function ($model) {
            $model->order->update([]);
        });
    }

    public function spek()
    {
        return $this->belongsToMany(Spek::class, 'order_speks', 'order_detail_id', 'spek_id')->withPivot('keterangan');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function proses()
    {
        return $this->belongsTo(Proses::class, 'proses_id');
    }

    public function produksi()
    {
        return $this->belongsTo(Produksi::class, 'produksi_id');
    }

    public function pemproses()
    {
        return $this->belongsTo(Pemproses::class, 'pemproses_id');
    }

    public function scopeForDashboard($query)
    {
        return $query->whereExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('orders')
                ->whereColumn('orders.id', 'order_details.order_id');
        });
    }

    public function jadwal()
    {
        return $this->belongsToMany(Produksi::class, 'order_jadwals', 'order_detail_id', 'produksi_id')->withPivot('deathline');
    }
}
