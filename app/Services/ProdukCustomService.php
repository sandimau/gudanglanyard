<?php

namespace App\Services;

use App\Models\Produk;
use App\Models\ProdukCustomResep;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProdukCustomService
{
    public function isCustom(int $produkId): bool
    {
        return DB::table('produks as p')
            ->join('produk_models as m', 'm.id', '=', 'p.produk_model_id')
            ->join('produk_kategoris as k', 'k.id', '=', 'm.kategori_id')
            ->join('produk_kategori_utamas as u', 'u.id', '=', 'k.kategori_utama_id')
            ->where('p.id', $produkId)->where('u.is_custom', 1)->where('m.stok', 0)->exists();
    }

    /** @return array{produk_id:int,material_ids:array<int,int>,paket:int} */
    public function parseSku(string $sku): array
    {
        $sku = trim($sku);
        if (! preg_match('/^(?:CUSTOM_)?([1-9][0-9]*)(?:-([1-9][0-9]*(?:-[1-9][0-9]*)*))?(?:_([1-9][0-9]*))?$/', $sku, $match)) {
            throw ValidationException::withMessages(['produk_id' => "SKU {$sku} tidak valid."]);
        }
        $produkId = (int) $match[1];
        $materials = ! empty($match[2]) ? array_map('intval', explode('-', $match[2])) : [];
        if ($materials && ! $this->isCustom($produkId)) {
            throw ValidationException::withMessages(['produk_id' => 'SKU komposit hanya boleh untuk produk custom.']);
        }
        if ($this->isCustom($produkId)) {
            $this->resolve($produkId, $materials);
        }

        return ['produk_id' => $produkId, 'material_ids' => $materials, 'paket' => (int) ($match[3] ?? 1)];
    }

    /** @return array<int,array{produk_id:int,qty:float}> */
    public function resolve(int $customId, array $materials): array
    {
        $resep = ProdukCustomResep::where('produk_id', $customId)->where('jenis', 'bahan')->orderBy('id')->get();
        $models = DB::table('produks')->whereIn('id', $materials)->where('status', 1)
            ->pluck('produk_model_id', 'id')->mapWithKeys(fn ($id, $key) => [(int) $key => (int) $id])->all();
        if (count($materials) !== count(array_unique($materials)) || count($models) !== count($materials)) {
            throw ValidationException::withMessages(['produk_id' => 'Varian bahan tidak valid atau berulang.']);
        }
        $out = [];
        foreach ($resep as $baris) {
            $varian = DB::table('produks')->where('produk_model_id', $baris->komponen_model_id)
                ->where('status', 1)->orderBy('id')->limit(2)->pluck('id');
            if ($varian->isEmpty()) {
                throw ValidationException::withMessages(['produk_id' => 'Komponen resep tidak memiliki varian aktif.']);
            }
            if ($varian->count() === 1) {
                $id = (int) $varian->first();
            } else {
                $id = null;
                foreach ($models as $candidate => $modelId) {
                    if ($modelId === (int) $baris->komponen_model_id) {
                        $id = $candidate;
                        unset($models[$candidate]);
                        break;
                    }
                }
                if ($id === null) {
                    throw ValidationException::withMessages(['produk_id' => 'Pilih varian untuk semua komponen resep.']);
                }
            }
            $out[] = ['produk_id' => $id, 'qty' => (float) $baris->jumlah];
        }
        if ($models) {
            throw ValidationException::withMessages(['produk_id' => 'Varian bahan tidak sesuai dengan resep produk custom.']);
        }

        return $out;
    }

    /** Kombinasi SKU untuk disalin ke Shopee (hanya slot ber-varian banyak). */
    public function skuOptions(int $produkId): array
    {
        if (! $this->isCustom($produkId) || ! ProdukCustomResep::where('produk_id', $produkId)->exists()) {
            return [];
        }
        $slots = [];
        foreach (ProdukCustomResep::where('produk_id', $produkId)->where('jenis', 'bahan')->orderBy('id')->get() as $baris) {
            $variants = Produk::where('produk_model_id', $baris->komponen_model_id)->where('status', 1)->orderBy('id')->get();
            if ($variants->count() > 1) {
                $slots[] = $variants;
            }
        }
        $combinations = [['ids' => [], 'label' => '']];
        foreach ($slots as $variants) {
            $next = [];
            foreach ($combinations as $combo) {
                foreach ($variants as $variant) {
                    if (in_array($variant->id, $combo['ids'], true)) continue;
                    $next[] = ['ids' => [...$combo['ids'], $variant->id],
                        'label' => trim($combo['label'] . ' ' . ($variant->nama ?: '#' . $variant->id))];
                }
            }
            $combinations = $next;
        }
        return array_map(fn ($combo) => ['sku' => 'CUSTOM_' . $produkId
            . ($combo['ids'] ? '-' . implode('-', $combo['ids']) : ''), 'label' => $combo['label']], $combinations);
    }

    public function hpp(int $produkId, int $orderQty = 1, array $materials = []): int
    {
        $bahan = $this->resolve($produkId, $materials);
        $cost = 0;
        foreach ($bahan as $row) {
            $cost += (float) (Produk::find($row['produk_id'])?->hpp ?? 0) * $row['qty'];
        }
        foreach (ProdukCustomResep::where('produk_id', $produkId)->where('jenis', 'internal')->get() as $row) {
            $cost += (float) $row->biaya * ($row->per_order ? 1 / max(1, $orderQty) : (float) $row->jumlah);
        }
        return (int) round($cost);
    }

    /** Estimasi per unit untuk katalog; biaya sekali per order baru dialokasikan pada detail order. */
    public function estimasiHpp(int $produkId): int
    {
        $cost = 0;
        foreach (ProdukCustomResep::where('produk_id', $produkId)->get() as $row) {
            if ($row->jenis === 'internal') {
                if (! $row->per_order) $cost += (float) $row->biaya * (float) $row->jumlah;
                continue;
            }
            $rata = (float) Produk::where('produk_model_id', $row->komponen_model_id)
                ->where('status', 1)->avg('hpp');
            $cost += $rata * (float) $row->jumlah;
        }
        return (int) round($cost);
    }

    /** Snapshot sekali per detail, termasuk order yang masuk lewat bulk insert webhook/CSV. */
    public function snapshot(string $sumber, int $detailId): void
    {
        DB::transaction(function () use ($sumber, $detailId) {
            $table = $sumber === 'order' ? 'order_details' : 'project_mp_details';
            $detail = DB::table($table)->where('id', $detailId)->lockForUpdate()->first();
            if (! $detail || ! $this->isCustom((int) $detail->produk_id)
                || ! ProdukCustomResep::where('produk_id', $detail->produk_id)->exists()
                || $detail->custom_resep_disalin_at) {
                return;
            }
            $materials = json_decode($detail->material_ids ?? '[]', true) ?: [];
            $rows = $this->resolve((int) $detail->produk_id, $materials);
            $grouped = [];
            foreach ($rows as $row) {
                $grouped[$row['produk_id']] = ($grouped[$row['produk_id']] ?? 0) + $row['qty'] * (int) $detail->jumlah;
            }
            foreach ($grouped as $produkId => $qty) {
                DB::table('order_custom_bahans')->insert([
                    'sumber' => $sumber, 'detail_id' => $detailId, 'produk_id' => $produkId,
                    'jumlah' => (int) ceil($qty), 'hpp' => (int) (Produk::find($produkId)?->hpp ?? 0),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table($table)->where('id', $detailId)->update([
                'hpp' => $this->hpp((int) $detail->produk_id, (int) $detail->jumlah, $materials),
                'custom_resep_disalin_at' => now(),
            ]);
        });
    }
}
