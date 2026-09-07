<?php

use App\Models\Cabang;
use App\Support\CabangContext;
use Illuminate\Support\Facades\Session;

if (!function_exists('cabang_id')) {
    function cabang_id(): ?int
    {
        $override = CabangContext::get();
        if ($override) {
            return $override;
        }

        if (cabang_semua()) {
            return null;
        }

        $id = Session::get('cabang_id');

        return $id ? (int) $id : null;
    }
}

if (!function_exists('cabang_semua')) {
    /**
     * Mode gabungan semua cabang (tanpa filter cabang_id).
     */
    function cabang_semua(): bool
    {
        return Session::get('cabang_id') === 'all';
    }
}

if (!function_exists('resolve_cabang_id')) {
    /**
     * Cabang efektif untuk mutasi/saldo stok.
     * Urutan: argumen eksplisit → session/override → fallback PUSAT.
     */
    function resolve_cabang_id(?int $cabangId = null): int
    {
        if ($cabangId) {
            return $cabangId;
        }

        if (cabang_id()) {
            return (int) cabang_id();
        }

        static $fallback = null;
        if ($fallback === null) {
            $fallback = (int) (Cabang::where('kode', 'PUSAT')->value('id')
                ?? Cabang::aktif()->orderBy('id')->value('id')
                ?? 0);
        }

        if (!$fallback) {
            throw new RuntimeException('Cabang belum dikonfigurasi.');
        }

        return $fallback;
    }
}

if (!function_exists('cabang_aktif')) {
    function cabang_aktif(): ?Cabang
    {
        $id = cabang_id();
        if (!$id) {
            return null;
        }

        return Cabang::find($id);
    }
}

if (!function_exists('with_cabang')) {
    function with_cabang(?int $cabangId, callable $callback)
    {
        return CabangContext::run($cabangId, $callback);
    }
}

if (!function_exists('pusat_cabang_id')) {
    function pusat_cabang_id(): ?int
    {
        static $pusatId = false;
        if ($pusatId === false) {
            $pusatId = Cabang::where('kode', 'PUSAT')->value('id');
            $pusatId = $pusatId ? (int) $pusatId : null;
        }

        return $pusatId;
    }
}

if (!function_exists('apply_cabang_constraint')) {
    /**
     * Filter kolom cabang_id sesuai cabang aktif.
     * Data legacy (cabang_id NULL) ikut tampil hanya saat cabang aktif = PUSAT.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    function apply_cabang_constraint($query, string $column, ?int $cabangId = null): void
    {
        $cabangId = $cabangId ?? cabang_id();
        if (!$cabangId) {
            return;
        }

        $pusatId = pusat_cabang_id();
        if ($pusatId && (int) $cabangId === (int) $pusatId) {
            $query->where(function ($q) use ($column, $cabangId) {
                $q->where($column, $cabangId)->orWhereNull($column);
            });

            return;
        }

        $query->where($column, $cabangId);
    }
}

if (!function_exists('cabang_sql_constraint')) {
    /**
     * Fragmen SQL AND untuk raw query (cabang_id di-cast int — aman dari injection).
     * Contoh: " AND (o.cabang_id = 1 OR o.cabang_id IS NULL)"
     */
    function cabang_sql_constraint(string $column, ?int $cabangId = null): string
    {
        $cabangId = $cabangId ?? cabang_id();
        if (!$cabangId) {
            return '';
        }

        $cabangId = (int) $cabangId;
        $pusatId = pusat_cabang_id();
        if ($pusatId && $cabangId === (int) $pusatId) {
            return " AND ({$column} = {$cabangId} OR {$column} IS NULL)";
        }

        return " AND {$column} = {$cabangId}";
    }
}

if (!function_exists('cabang_sql_predicate')) {
    /**
     * Predicate SQL + bindings untuk filter cabang di query mentah.
     * Data legacy (NULL) ikut saat cabang aktif = PUSAT.
     *
     * @return array{0: string, 1: array}
     */
    function cabang_sql_predicate(string $column, ?int $cabangId = null): array
    {
        $cabangId = $cabangId ?? cabang_id();
        if (!$cabangId) {
            return ['1=1', []];
        }

        $pusatId = pusat_cabang_id();
        if ($pusatId && (int) $cabangId === (int) $pusatId) {
            return ["({$column} = ? OR {$column} IS NULL)", [$cabangId]];
        }

        return ["{$column} = ?", [$cabangId]];
    }
}
