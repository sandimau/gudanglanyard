<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Komplain extends Model
{
    use SoftDeletes, HasFactory, BelongsToCabang;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public $table = 'komplains';

    protected $casts = [
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id')->withoutGlobalScope('cabang');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id')->withTrashed();
    }

    public function projectMp()
    {
        return $this->belongsTo(ProjectMp::class, 'project_mp_id')->withoutGlobalScope('cabang');
    }

    public function projectMpDetail()
    {
        return $this->belongsTo(ProjectMpDetail::class, 'project_mp_detail_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function kontak()
    {
        // Relasi by FK, tetap tampil meskipun scope cabang kontak berbeda.
        return $this->belongsTo(Kontak::class, 'kontak_id')->withoutGlobalScope('cabang');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function replacementOrderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'replacement_order_detail_id')->withTrashed();
    }

    public function replacementProjectMpDetail()
    {
        return $this->belongsTo(ProjectMpDetail::class, 'replacement_project_mp_detail_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function getTipeLabelAttribute(): string
    {
        return strtolower($this->tipe) === 'online' ? 'Online' : 'Offline';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Menunggu',
        };
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getHppSatuanAttribute(): int
    {
        return abs((int) ($this->orderDetail?->hpp ?: $this->projectMpDetail?->hpp ?: $this->produk?->hpp ?: 0));
    }
}
