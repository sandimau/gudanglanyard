<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'members';

    protected $dates = [
        'tgl_masuk',
        'tgl_keluar',
        'tgl_lahir',
        'tgl_gajian',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function scopeFreelance($query)
    {
        $query->where('jenis', 'freelance');
        $query->where('status', 1);
        return $query;
    }

    public function scopeAktif($query)
    {
        $query->where('status', 1);
        $query->where('jenis', 'karyawan');
        return $query;
    }

    public function scopeNonaktif($query, $jenis = 'karyawan')
    {
        $query->where('status', 0);
        $query->where('jenis', $jenis);
        return $query;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ar()
    {
        return $this->hasMany(Ar::class);
    }

    public function cuti()
    {
        return $this->hasMany(Cuti::class);
    }

    public function kasbon()
    {
        return $this->hasMany(Kasbon::class);
    }

    public function lembur()
    {
        return $this->hasMany(Lembur::class);
    }

    public function tunjangan()
    {
        return $this->hasMany(Tunjangan::class);
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }

    public function freelanceTagihans()
    {
        return $this->hasMany(FreelanceTagihan::class, 'member_id');
    }

    public function getUmurAttribute()
    {
        $now = Carbon::now();
        $b_day = Carbon::parse($this->attributes['tgl_lahir']);
        $umur = $b_day->diffInYears($now);
        return $umur . " tahun";
    }

    public function getCountCutiAttribute()
    {
        $tahun = date('Y');
        $cuti = $this->cuti()
            ->where("tanggal", '>=', $tahun . "-01-01")
            ->where("tanggal", '<=', $tahun . "-12-31")
            ->where("cuti", 1)
            ->get()->count();
        return $cuti;
    }

    public function getCountIjinAttribute()
    {
        $tahun = date('Y');
        $ijin = $this->cuti()
            ->where("tanggal", '>=', $tahun . "-01-01")
            ->where("tanggal", '<=', $tahun . "-12-31")
            ->where("cuti", 0)
            ->get()->count();
        return $ijin;
    }

    public function getLamaKerjaAttribute()
    {
        $tglMasuk = $this->attributes['tgl_masuk'];
        $now = date('Y-m-d');

        $diff = abs(strtotime($now) - strtotime($tglMasuk));

        $years = floor($diff / (365 * 60 * 60 * 24));
        $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));

        $lama_bekerja = '';
        if ($years > 0) {
            $lama_bekerja = $years . " tahun ";
        }
        $lama_bekerja .= $months . " bulan";

        return $lama_bekerja;
    }

    public function getCountKasbonAttribute()
    {
        $kasbon = $this->kasbon()->where('member_id', $this->id)
        ->orderBy('id', 'DESC')->first();
        isset($kasbon->saldo) ? $saldo = $kasbon->saldo : $saldo = 0;
        return $saldo;
    }

    public function getCountLemburAttribute()
    {
        $tahun = date('Y');
        $lembur = $this->lembur()
            ->where("tahun", '=', $tahun)
            ->sum('jam');
        return $lembur;
    }

    public function getCountTunjanganAttribute()
    {
        $tunjangan = $this->tunjangan()->where('member_id', $this->id)->whereYear('created_at', '=', Carbon::now()->year)
        ->orderBy('id', 'DESC')->first();
        isset($tunjangan->saldo) ? $saldo = $tunjangan->saldo : $saldo = 0;
        return $saldo;
    }
}
