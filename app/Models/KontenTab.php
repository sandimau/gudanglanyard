<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KontenTab extends Model
{
    protected $fillable = ['nama', 'grup', 'warna', 'urutan'];

    public function kontens(): HasMany
    {
        return $this->hasMany(Konten::class);
    }

    public static function ordered()
    {
        return static::orderBy('grup')->orderBy('urutan')->orderBy('id')->get();
    }
}
