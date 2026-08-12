<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Absensi extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'absensis';

    protected $dates = [
        'tanggal',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function freelanceTagihan()
    {
        return $this->hasOne(FreelanceTagihan::class, 'absensi_id');
    }

    /**
     * Jenis yang mengurangi tunjangan kehadiran karyawan (bukan cuti)
     */
    public static function jenisYangMengurangiTunjangan(): array
    {
        return ['sakit', 'ijin', 'terlambat', 'alpha'];
    }

    public function scopeMengurangiTunjangan($query)
    {
        return $query->whereIn('jenis', self::jenisYangMengurangiTunjangan());
    }
}
