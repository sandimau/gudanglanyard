<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesainGroup extends Model
{
    protected $fillable = ['nama', 'urutan'];

    public function tabs(): HasMany
    {
        return $this->hasMany(DesainTab::class, 'grup', 'nama');
    }

    public static function ordered()
    {
        return static::orderBy('urutan')->orderBy('nama')->get();
    }
}
