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

        $id = Session::get('cabang_id');

        return $id ? (int) $id : null;
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
