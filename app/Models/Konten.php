<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Konten extends Model
{
    protected $fillable = ['tanggal', 'topik', 'platform', 'waktu_posting', 'tujuan', 'konten_tab_id'];

    protected $casts = ['tanggal' => 'date'];

    public function tab(): BelongsTo
    {
        return $this->belongsTo(KontenTab::class, 'konten_tab_id');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
