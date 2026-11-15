<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunJurnalAuditLog extends Model
{
    use HasFactory;

    public $table = 'akun_jurnal_audit_logs';

    protected $guarded = [];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
    ];

    public function jurnal()
    {
        return $this->belongsTo(AkunJurnal::class, 'jurnal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
