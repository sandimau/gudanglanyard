<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMpDetail extends Model
{
    use HasFactory;

    public $table = 'project_mp_details';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $guarded = [];

    public function projectMp()
    {
        return $this->belongsTo(ProjectMp::class, 'project_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function produksi()
    {
        return $this->belongsTo(Produksi::class);
    }

    public function pemproses()
    {
        return $this->belongsTo(Pemproses::class, 'pemproses_id');
    }

    public function scopeForDashboardCustom($query)
    {
        // Pakai status buffer terbaru (MAX id) per project.
        // Duplikat nota tanpa unique index bisa menyisakan baris PROCESSED lama
        // meski Shopee sudah SHIPPED/TO_CONFIRM_RECEIVE, sehingga order "nempel" di dashboard.
        return $query->whereExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('marketplace_buffers as mb')
                ->whereColumn('mb.project_id', 'project_mp_details.project_id')
                ->whereIn('mb.status', ['PROCESSED', 'READY_TO_SHIP', 'UNPAID'])
                ->whereRaw('mb.id = (
                    SELECT MAX(latest.id)
                    FROM marketplace_buffers latest
                    WHERE latest.project_id = mb.project_id
                )')
                ->whereExists(function ($custom) {
                    $custom->selectRaw('1')
                        ->from('marketplace_buffers as mc')
                        ->whereColumn('mc.project_id', 'mb.project_id')
                        ->where('mc.custom', 1);
                });
        });
    }
}
