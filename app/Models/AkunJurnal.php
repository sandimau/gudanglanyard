<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunJurnal extends Model
{
    use HasFactory;

    public $table = 'akun_jurnals';

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'posted_at' => 'datetime',
        'total' => 'integer',
        'idempoten_aktif' => 'boolean',
    ];

    public function bukuBesar()
    {
        return $this->hasMany(BukuBesar::class, 'jurnal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AkunJurnalAuditLog::class, 'jurnal_id');
    }

    public function reversedBy()
    {
        return $this->belongsTo(self::class, 'reversed_by_jurnal_id');
    }

    public function reverses()
    {
        return $this->belongsTo(self::class, 'reverses_jurnal_id');
    }
}
