<?php

namespace App\Models;

use App\Services\BukuBesarService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BukuBesar extends Model
{
    use HasFactory;

    public $table = 'buku_besars';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        BukuBesar::creating(function ($model) {
            if (auth()->check()) {
                $model->user_id = auth()->user()->id;
            }
        });

        BukuBesar::saved(function ($model) {
            app(BukuBesarService::class)->updateLastSaldo($model->akun_detail_id);
        });

        BukuBesar::deleted(function ($model) {
            app(BukuBesarService::class)->updateLastSaldo($model->akun_detail_id);
        });
    }

    public function scopeSaldoBerjalan($query)
    {
        return $query->select('buku_besars.*')
            ->selectRaw('(SELECT COALESCE(SUM(COALESCE(b2.debet, 0) - COALESCE(b2.kredit, 0)), 0)
                FROM buku_besars b2
                WHERE b2.akun_detail_id = buku_besars.akun_detail_id
                AND b2.id <= buku_besars.id) AS saldo');
    }

    public function scopeSaldoAkun($query, array $saldo)
    {
        $anchor = (float) $saldo['saldo'];

        return $query->select(
            'buku_besars.id',
            'buku_besars.akun_detail_id',
            'buku_besars.created_at',
            'buku_besars.kode',
            'buku_besars.ket',
            'buku_besars.debet',
            'buku_besars.kredit',
            'buku_besars.detail_id',
            'buku_besars.user_id',
            DB::raw("{$anchor} - (SELECT COALESCE(SUM(COALESCE(b2.debet, 0) - COALESCE(b2.kredit, 0)), 0)
                FROM buku_besars b2
                WHERE b2.akun_detail_id = buku_besars.akun_detail_id
                AND b2.id > buku_besars.id) AS saldo")
        );
    }

    public static function lastSaldo($akun_detail_id): float
    {
        return app(BukuBesarService::class)->saldoTersedia($akun_detail_id);
    }

    public function akunDetail()
    {
        return $this->belongsTo(AkunDetail::class, 'akun_detail_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
