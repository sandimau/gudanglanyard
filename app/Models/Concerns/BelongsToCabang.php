<?php

namespace App\Models\Concerns;

use App\Models\Cabang;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCabang
{
    public static function bootBelongsToCabang(): void
    {
        static::addGlobalScope('cabang', function (Builder $builder) {
            $cabangId = cabang_id();
            if ($cabangId) {
                $builder->where($builder->getModel()->getTable() . '.cabang_id', $cabangId);
            }
        });

        static::creating(function ($model) {
            if (!empty($model->cabang_id)) {
                return;
            }

            if (cabang_id()) {
                $model->cabang_id = cabang_id();
                return;
            }

            $fallback = Cabang::where('kode', 'PUSAT')->value('id')
                ?? Cabang::aktif()->orderBy('id')->value('id');

            if ($fallback) {
                $model->cabang_id = $fallback;
            }
        });
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }
}
