<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produksi extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'produksis';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'nama',
        'grup',
        'warna',
        'urutan',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function orderDetail()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function projectMpDetail()
    {
        return $this->hasMany(ProjectMpDetail::class);
    }

    public static function ambilFlow($grup)
    {
        return self::where('nama', $grup)->first()->id;
    }

    public static function flowItems()
    {
        return self::orderBy('grup')->orderBy('urutan')->get()
            ->groupBy('grup')
            ->sortKeysUsing(function ($a, $b) {
                if ($a === 'batal') {
                    return 1;
                }
                if ($b === 'batal') {
                    return -1;
                }

                return strcmp($a ?? '', $b ?? '');
            })
            ->flatten()
            ->filter(fn ($produksi) => ! in_array($produksi->nama, ['finish', 'batal']))
            ->values();
    }

    public function nextInFlow(): ?self
    {
        $flow = static::flowItems();
        $index = $flow->search(fn ($produksi) => $produksi->id === $this->id);

        return $index !== false ? $flow->get($index + 1) : null;
    }
}
