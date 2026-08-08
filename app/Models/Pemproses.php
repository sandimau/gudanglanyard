<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemproses extends Model
{
    use HasFactory;

    public const KATEGORI_UTAMA = 'utama';
    public const KATEGORI_SETTING = 'setting';

    public $table = 'pemproses';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'nama',
        'warna',
        'kategori',
        'created_at',
        'updated_at',
    ];

    public function scopeUtama($query)
    {
        return $query->where('kategori', self::KATEGORI_UTAMA);
    }

    public function scopeSetting($query)
    {
        return $query->where('kategori', self::KATEGORI_SETTING);
    }

    public static function kategoriOptions(): array
    {
        return [
            self::KATEGORI_UTAMA => 'Utama',
            self::KATEGORI_SETTING => 'Setting',
        ];
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
