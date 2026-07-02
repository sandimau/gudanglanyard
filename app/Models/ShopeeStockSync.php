<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeStockSync extends Model
{
    protected $table = 'shopee_stock_syncs';

    protected $guarded = [];

    protected $casts = [
        'dirty_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'synced_marketplaces' => 'array',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function needsSync(): bool
    {
        return $this->dirty_at !== null;
    }
}
