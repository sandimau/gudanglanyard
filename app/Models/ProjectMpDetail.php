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

    public function komplain()
    {
        return $this->belongsTo(Komplain::class, 'komplain_id');
    }

    public function getIsKomplainAttribute(): bool
    {
        return !empty($this->komplain_id);
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
        // Penggantian komplain tetap harus diproduksi meski order lama sudah
        // tidak punya buffer marketplace (atau status pengirimannya sudah selesai).
        return $query->where(function ($query) {
            $query->whereNotNull('project_mp_details.komplain_id')
                ->orWhereExists(function ($sub) {
                    // Pakai status buffer terbaru (MAX id) per project.
                    // SHIPPED tetap masuk jika produk custom masih perlu diproses.
                    $sub->selectRaw('1')
                        ->from('marketplace_buffers as mb')
                        ->whereColumn('mb.project_id', 'project_mp_details.project_id')
                        ->whereIn('mb.status', ['PROCESSED', 'READY_TO_SHIP', 'UNPAID', 'SHIPPED'])
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
        });
    }
}
