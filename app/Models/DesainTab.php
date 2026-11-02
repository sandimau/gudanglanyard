<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DesainTab extends Model
{
    protected $fillable = ['nama', 'grup', 'warna', 'urutan'];

    public function desains(): HasMany
    {
        return $this->hasMany(Desain::class);
    }

    public static function ordered()
    {
        return static::query()
            ->leftJoin('desain_groups', 'desain_groups.nama', '=', 'desain_tabs.grup')
            ->orderByRaw('COALESCE(desain_groups.urutan, 999999)')
            ->orderBy('desain_tabs.urutan')
            ->orderBy('desain_tabs.id')
            ->select('desain_tabs.*')
            ->get();
    }

    public static function visibleForProses()
    {
        return static::ordered()
            ->reject(fn (self $tab) => $tab->isHiddenFromProses())
            ->values();
    }

    public function isHiddenFromProses(): bool
    {
        return Str::lower(trim((string) $this->grup)) === 'akhir'
            && in_array(Str::lower(trim((string) $this->nama)), ['finish', 'selesai'], true);
    }
}
