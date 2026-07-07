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
        return $query->whereExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('marketplace_buffers')
                ->whereColumn('marketplace_buffers.project_id', 'project_mp_details.project_id')
                ->where('marketplace_buffers.custom', 1)
                ->whereIn('marketplace_buffers.status', ['PROCESSED', 'READY_TO_SHIP', 'UNPAID']);
        });
    }
}
