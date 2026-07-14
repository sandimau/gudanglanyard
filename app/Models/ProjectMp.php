<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectMp extends Model
{
    use HasFactory;

    public $table = 'project_mps';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $guarded = [];

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function details()
    {
        return $this->hasMany(ProjectMpDetail::class, 'project_id');
    }

    public function buffer()
    {
        return $this->hasOne(MarketplaceBuffer::class, 'project_id')->latestOfMany();
    }

    public function chats()
    {
        return $this->hasMany(Chat::class, 'project_mp_id');
    }

    public function scopeOmzetTahun($query)
    {
        $query->select(DB::raw('YEAR(created_at) as year'), DB::raw('SUM(total) as sumMp'));
        $query->whereRaw('total');
        $query->groupBy('year');
        return $query;
    }

    public function scopeOmzetBulan($query)
    {
        $query->select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('EXTRACT(YEAR_MONTH FROM created_at) as month'),
            DB::raw('MONTHNAME(created_at) as monthname'),
            DB::raw('SUM(total) as omzetMp')
        );
        $query->whereRaw('total');
        $query->groupBy('month');
        $query->orderBy('created_at');
        return $query;
    }

    public function getListprodukAttribute()
    {
        $yy = array();
        foreach ($this->details as $item) {
            $nama_produk = '';
            $nama_produk .= $item->produk->namaLengkap ?? '-';
            $yy[] = $nama_produk;
        }
        return implode(', ', $yy);
    }
}
