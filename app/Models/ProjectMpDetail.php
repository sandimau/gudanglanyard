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
}
