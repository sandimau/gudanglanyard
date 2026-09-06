<?php

namespace App\Http\Controllers\Traits;

use App\Services\StokService;
use Illuminate\Support\Facades\DB;

trait MarketplaceTriger
{
    use ShopeeApi;

    public function mpBeli($sku, $marketplace, $jumlah, $id)
    {
        $projectMp = DB::table('project_mps')->find($id);
        $nota = $projectMp->nota ?? '';
        $cabangId = resolve_cabang_id($marketplace->cabang_id ?? $projectMp->cabang_id ?? null);

        app(StokService::class)->mpBeli(
            $sku,
            $cabangId,
            $jumlah,
            'dibeli ' . $marketplace->nama . '(' . $nota . ')',
            $id
        );
    }

    public function getLastStok($produk_id, $cabang_id = null)
    {
        return app(StokService::class)->saldoTersedia($produk_id, $cabang_id);
    }

    public function updateLastStok($produk_id, $cabang_id = null, $saldo = null)
    {
        app(StokService::class)->updateLastStok($produk_id, resolve_cabang_id($cabang_id));
    }

    public function updateStokMp($produk_id, $cabang_id = null)
    {
        $this->updateLastStok($produk_id, $cabang_id);
    }
}
