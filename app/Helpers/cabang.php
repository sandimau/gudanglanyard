<?php

use App\Models\Cabang;
use App\Models\Gaji;
use App\Models\Member;
use App\Support\CabangContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

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

if (!function_exists('order_edit_roles')) {
    /**
     * Role yang boleh edit order (offline/online), termasuk lintas cabang.
     * Superadmin, Manager, SPV, Marketing, CS_Online, Setting.
     *
     * @return list<string>
     */
    function order_edit_roles(): array
    {
        return [
            'super',
            'manager',
            'supervisor',
            'spv',
            'marketing',
            'cs_online',
            'setting',
        ];
    }
}

if (!function_exists('can_edit_order_role')) {
    /**
     * User punya salah satu role yang diizinkan edit order.
     */
    function can_edit_order_role(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $allowed = array_map('strtolower', order_edit_roles());

        return $user->roles->contains(
            fn ($role) => in_array(strtolower((string) $role->name), $allowed, true)
        );
    }
}

if (!function_exists('can_edit_lintas_cabang')) {
    /**
     * Role yang boleh edit/ubah status lintas cabang = role edit order.
     */
    function can_edit_lintas_cabang(): bool
    {
        return can_edit_order_role();
    }
}

if (!function_exists('user_has_role_insensitive')) {
    function user_has_role_insensitive(string ...$names): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $normalized = array_map('strtolower', $names);

        return $user->roles->contains(
            fn ($role) => in_array(strtolower((string) $role->name), $normalized, true)
        );
    }
}

if (!function_exists('is_marketing_only')) {
    /**
     * Role Marketing tanpa role edit penuh lain.
     * Boleh edit order (header), tidak boleh ubah status produksi / edit order detail.
     */
    function is_marketing_only(): bool
    {
        if (!user_has_role_insensitive('marketing')) {
            return false;
        }

        return !user_has_role_insensitive(
            'supervisor',
            'spv',
            'super',
            'manager',
            'cs_online',
            'setting'
        );
    }
}

if (!function_exists('is_status_advance_only')) {
    /**
     * Hanya boleh pindah ke status berikutnya (bukan pilih bebas).
     * Setting ikut mode ini, tapi tetap lintas cabang via order_edit_roles.
     */
    function is_status_advance_only(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        // Setting: next-only seperti produksi, lintas cabang tetap via can_edit_order_role()
        if (user_has_role_insensitive('setting')) {
            return true;
        }

        if (can_edit_order_role()) {
            return false;
        }

        if (user_has_role_insensitive('produksi')) {
            return true;
        }

        $member = Member::where('user_id', Auth::id())->first();
        if (!$member) {
            return false;
        }

        $gaji = Gaji::with(['bagian', 'level'])->where('member_id', $member->id)->orderByDesc('id')->first();
        $bagianNama = strtolower((string) ($gaji?->bagian?->nama ?? ''));
        $levelNama = strtolower((string) ($gaji?->level?->nama ?? ''));

        return $bagianNama === 'produksi' || $levelNama === 'produksi';
    }
}

if (!function_exists('member_cabang_id')) {
    /**
     * Cabang member user login (abaikan scope session cabang).
     */
    function member_cabang_id(): ?int
    {
        if (!Auth::check()) {
            return null;
        }

        static $cache = [];
        $userId = Auth::id();

        if (!array_key_exists($userId, $cache)) {
            $cabangId = Member::withoutGlobalScope('cabang')
                ->where('user_id', $userId)
                ->value('cabang_id');

            $cache[$userId] = $cabangId ? (int) $cabangId : null;
        }

        return $cache[$userId];
    }
}

if (!function_exists('can_edit_cabang_record')) {
    /**
     * Boleh edit record bila role lintas-cabang, atau cabang record = cabang member.
     * Record tanpa cabang dianggap PUSAT.
     */
    function can_edit_cabang_record(?int $recordCabangId): bool
    {
        if (can_edit_lintas_cabang()) {
            return true;
        }

        $memberCabangId = member_cabang_id();
        if (!$memberCabangId) {
            return false;
        }

        $recordCabangId = $recordCabangId ? (int) $recordCabangId : pusat_cabang_id();

        return $recordCabangId && (int) $recordCabangId === (int) $memberCabangId;
    }
}

if (!function_exists('abort_unless_can_edit_cabang')) {
    function abort_unless_can_edit_cabang(?int $recordCabangId, string $message = 'Tidak boleh mengubah data cabang lain.'): void
    {
        abort_unless(
            can_edit_cabang_record($recordCabangId),
            Response::HTTP_FORBIDDEN,
            $message
        );
    }
}
