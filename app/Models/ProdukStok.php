<?php

namespace App\Models;

use App\Services\ShopeeStockSyncService;
use App\Services\StokService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ProdukStok extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'produk_stoks';

    protected $dates = [
        'tanggal',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        ProdukStok::saving(function ($model) {
            $model->hpp = $model->produk?->hpp ?? 0;
        });

        ProdukStok::creating(function ($model) {
            if (auth()->check()) {
                $model->user_id = auth()->user()->id;
            }
        });

        ProdukStok::saved(function ($model) {
            app(StokService::class)->updateLastStok($model->produk_id);
            app(ShopeeStockSyncService::class)->markDirty((int) $model->produk_id);
        });

        ProdukStok::deleted(function ($model) {
            app(StokService::class)->updateLastStok($model->produk_id);
            app(ShopeeStockSyncService::class)->markDirty((int) $model->produk_id);
        });
    }

    public function scopeSaldoBerjalan($query)
    {
        return $query->select('produk_stoks.*')
            ->selectRaw('(SELECT COALESCE(SUM(COALESCE(s2.tambah, 0) - COALESCE(s2.kurang, 0)), 0)
                FROM produk_stoks s2
                WHERE s2.produk_id = produk_stoks.produk_id
                AND s2.id <= produk_stoks.id
                AND s2.deleted_at IS NULL) AS saldo');
    }

    public function scopeSaldoStok($query, array $saldo)
    {
        $anchor = (int) $saldo['saldo'];

        return $query->select(
            'produk_stoks.id',
            'produk_stoks.produk_id',
            'produk_stoks.created_at',
            'produk_stoks.tambah',
            'produk_stoks.kurang',
            'produk_stoks.keterangan',
            'produk_stoks.kode',
            'produk_stoks.hpp',
            'produk_stoks.user_id',
            'produk_stoks.detail_id',
            'produk_stoks.status',
            DB::raw("{$anchor} - (SELECT COALESCE(SUM(COALESCE(t2.tambah, 0) - COALESCE(t2.kurang, 0)), 0)
                FROM produk_stoks t2
                WHERE t2.produk_id = produk_stoks.produk_id
                AND t2.id > produk_stoks.id
                AND t2.deleted_at IS NULL) AS saldo")
        );
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public static function lastStok($produk)
    {
        return app(StokService::class)->saldoTersedia($produk);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
