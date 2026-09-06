<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabang;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kontak extends Model
{
    use SoftDeletes, HasFactory, BelongsToCabang;

    public $table = 'kontaks';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function getTotalOrderAttribute()
    {
        return $this->order()->get()->count();
    }

    public function getAllOmzetAttribute()
    {
        $total = array();
        $order = $this->order()->get();
        foreach ($order as $value) {
            $total[] = $value->total;
        }
        return array_sum($total);
    }

    public function getMonthOmzetAttribute()
    {
        $total = array();
        $order = $this->order()->get();
        foreach ($order as $value) {
            $total[] = $value->total;
        }
        $allTotal = array_sum($total);

        $bulan = $this->created_at->diffInMonths();
        if ($bulan == 0) {
            $bulan = 1;
        }

        return floor($allTotal / $bulan);
    }

    public function getBergabungAttribute()
    {
        $bulan = $this->created_at->diffInMonths();
        if ($bulan == 0) {
            return 'baru';
        }
        return $this->created_at->diffInMonths();
    }

    public function getLastOrderAttribute()
    {
        $terakhir = $this->order()->latest('created_at')->first();
        if (!empty($terakhir)) {
            if ($terakhir->created_at->diffInMonths() == 0) {
                return "baru";
            }
            return $terakhir->created_at->diffInMonths();
        } else {
            return 'belum';
        }
    }

    public function getJenisAttribute($value)
    {
        if ($this->attributes['konsumen'] == 1) {
            $konsumen = 'konsumen';
        } else {
            $konsumen = null;
        }
        if ($this->attributes['supplier'] == 1) {
            $supplier = 'supplier';
        } else {
            $supplier = null;
        }
        if ($this->attributes['marketplace'] == 1) {
            $marketplace = 'marketplace';
        } else {
            $marketplace = null;
        }
        $test = '';
        if ($konsumen) {
            $test .= '<li>'.$konsumen.'</li>';
        }
        if ($supplier) {
            $test .= '<li>'.$supplier.'</li>';
        }
        if ($marketplace) {
            $test .= '<li>'.$marketplace.'</li>';
        }
        return $test;
    }

    public function order()
    {
        return $this->hasMany(Order::class);
    }

    public function ar()
    {
        return $this->belongsTo(Ar::class);
    }
}
