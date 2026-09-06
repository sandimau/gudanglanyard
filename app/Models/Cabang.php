<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    public $table = 'cabangs';

    protected $fillable = [
        'nama',
        'kode',
        'alamat',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function scopeAktif($query)
    {
        return $query->where('status', true);
    }

    public function isAktif(): bool
    {
        return (bool) $this->status;
    }
}
