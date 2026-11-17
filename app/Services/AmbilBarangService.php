<?php

namespace App\Services;

use App\Models\AmbilBarang;
use App\Models\OrderDetail;
use App\Models\Produk;
use App\Models\ProjectMpDetail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AmbilBarangService
{
    public function kebutuhan()
    {
        $projects = ProjectMpDetail::query()
            ->whereHas('projectMp')
            ->whereHas('produk.produkModel', fn ($q) => $q->where('stok', 1))
            ->whereDoesntHave('pengambilan')
            ->whereDoesntHave('produksi', fn ($q) => $q->where('nama', 'batal'));

        $orders = OrderDetail::query()
            ->whereHas('order', function ($q) {
                $q->whereNull('marketplace')->whereNull('deleted_at');
                apply_cabang_constraint($q, 'orders.cabang_id');
            })
            ->whereHas('produk.produkModel', fn ($q) => $q->where('stok', 1))
            ->whereDoesntHave('pengambilan')
            ->whereDoesntHave('produksi', fn ($q) => $q->whereRaw("LOWER(TRIM(nama)) IN ('batal', 'finish')"));
        $projects->whereHas('projectMp', function ($q) {
            apply_cabang_constraint($q, 'project_mps.cabang_id');
        });

        return $orders->with(['order.kontak', 'produk.produkModel', 'produksi'])->get()
            ->filter(fn ($d) => $d->order && can_edit_cabang_record($d->order->cabang_id))
            ->map(fn ($d) => $this->ringkas($d, 'order'))
            ->concat($projects->with(['projectMp.marketplace', 'projectMp.buffer', 'produk.produkModel', 'produksi'])->get()
                ->filter(fn ($d) => $d->projectMp && can_edit_cabang_record($d->projectMp->cabang_id))
                ->map(fn ($d) => $this->ringkas($d, 'project')))
            ->groupBy(fn ($item) => $item['cabang_id'] . ':' . $item['produk_id'])
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'cabang_id' => $first['cabang_id'],
                    'produk_id' => $first['produk_id'],
                    'produk' => $first['produk'],
                    'stok' => app(StokService::class)->saldoTersedia($first['produk_id'], $first['cabang_id']),
                    'jumlah' => $items->sum('jumlah'),
                    'items' => $items->values(),
                ];
            })->values();
    }

    private function ringkas($detail, string $sumber): array
    {
        $parent = $sumber === 'order' ? $detail->order : $detail->projectMp;

        return [
            'key' => $sumber . ':' . $detail->id,
            'sumber' => $sumber,
            'detail_id' => $detail->id,
            'produk_id' => (int) $detail->produk_id,
            'produk' => $detail->produk->namaLengkap,
            'cabang_id' => resolve_cabang_id($parent->cabang_id),
            'jumlah' => (int) $detail->jumlah,
            'nota' => $parent->nota,
            'pembeli' => $sumber === 'order' ? ($parent->kontak?->nama ?? '-') : ($parent->konsumen ?? '-'),
            'jenis' => $sumber === 'order' ? 'Offline' : ($parent->buffer?->custom ? 'Proses' : 'Produk'),
            'shipping' => trim((string) ($sumber === 'order' ? $parent->pengiriman : $parent->shipping)),
            'tanggal_order' => $parent->created_at,
            'url' => $sumber === 'order' ? route('order.detail', $parent->id) : route('projectmp.detail', $parent->id),
        ];
    }

    public function arsip(int $page = 1): LengthAwarePaginator
    {
        $query = AmbilBarang::with('produk.produkModel')->orderByDesc('id');
        if (! can_edit_lintas_cabang()) {
            $cabangId = member_cabang_id();
            $query->where('cabang_id', $cabangId ?: 0);
        }

        $pagination = $query->paginate(20, ['*'], 'page', max(1, $page));
        $records = $pagination->getCollection();
        $orderDetails = OrderDetail::withTrashed()->with('order.kontak')
            ->whereIn('id', $records->where('sumber', 'order')->pluck('detail_id'))->get()->keyBy('id');
        $projectDetails = ProjectMpDetail::with('projectMp')
            ->whereIn('id', $records->where('sumber', 'project')->pluck('detail_id'))->get()->keyBy('id');
        $totals = DB::table('ambil_barangs')
            ->whereIn('produk_id', $records->pluck('produk_id')->unique())
            ->whereIn('cabang_id', $records->pluck('cabang_id')->unique())
            ->selectRaw('cabang_id, produk_id, SUM(jumlah) AS total')
            ->groupBy('cabang_id', 'produk_id')->get()
            ->keyBy(fn ($row) => $row->cabang_id . ':' . $row->produk_id);

        return $pagination->setCollection($records->map(function ($ambil) use ($orderDetails, $projectDetails, $totals) {
                $parent = $ambil->sumber === 'order'
                    ? $orderDetails->get($ambil->detail_id)?->order
                    : $projectDetails->get($ambil->detail_id)?->projectMp;

                return [
                    'cabang_id' => (int) $ambil->cabang_id,
                    'produk_id' => (int) $ambil->produk_id,
                    'produk' => $ambil->produk?->namaLengkap ?? 'Produk #' . $ambil->produk_id,
                    'jumlah' => (int) $ambil->jumlah,
                    'total_produk' => (int) ($totals->get($ambil->cabang_id . ':' . $ambil->produk_id)?->total ?? $ambil->jumlah),
                    'nota' => $ambil->nota ?: ($parent?->nota ?? '-'),
                    'pembeli' => $ambil->pembeli ?: ($ambil->sumber === 'project' ? $parent?->konsumen : $parent?->kontak?->nama) ?: '-',
                    'jenis' => $ambil->jenis ?: ($ambil->sumber === 'project' ? 'Produk' : 'Offline'),
                    'shipping' => $ambil->shipping ?: ($ambil->sumber === 'project' ? $parent?->shipping : $parent?->pengiriman) ?: '',
                    'tanggal_order' => $ambil->tanggal_order ?: $parent?->created_at,
                    'diambil_pada' => $ambil->created_at,
                    'penandaan_migrasi' => $ambil->produk_stok_id === null,
                    'url' => $parent
                        ? ($ambil->sumber === 'project' ? route('projectmp.detail', $parent->id) : route('order.detail', $parent->id))
                        : null,
                ];
            })->values());
    }

    public function ambil(array $keys): int
    {
        $keys = array_values(array_unique($keys));
        sort($keys);

        return DB::transaction(function () use ($keys) {
            $items = [];
            foreach ($keys as $key) {
                if (! preg_match('/^(order|project):([1-9][0-9]*)$/', $key, $match)) {
                    throw ValidationException::withMessages(['items' => 'Detail order tidak valid.']);
                }

                $detail = $match[1] === 'order'
                    ? OrderDetail::with(['order', 'produk.produkModel', 'produksi'])->lockForUpdate()->find($match[2])
                    : ProjectMpDetail::with(['projectMp', 'produk.produkModel', 'produksi'])->lockForUpdate()->find($match[2]);
                $parent = $match[1] === 'order' ? $detail?->order : $detail?->projectMp;
                if (! $detail || ! $parent || ($match[1] === 'order' && $parent->marketplace !== null)
                    || (int) ($detail->produk?->produkModel?->stok ?? 0) !== 1
                    || ($match[1] === 'order'
                        ? in_array(strtolower(trim((string) $detail->produksi?->nama)), ['batal', 'finish'], true)
                        : $detail->produksi?->nama === 'batal')
                    || ! can_edit_cabang_record($parent->cabang_id)) {
                    throw ValidationException::withMessages(['items' => 'Detail order tidak tersedia untuk pengambilan.']);
                }
                if ((int) $detail->jumlah <= 0) {
                    throw ValidationException::withMessages(['items' => 'Jumlah detail order harus lebih dari nol.']);
                }
                $items[] = compact('detail', 'parent') + ['sumber' => $match[1], 'cabang_id' => resolve_cabang_id($parent->cabang_id)];
            }

            // Kunci produk diserialkan agar dua staf tidak menggunakan stok yang sama.
            Produk::whereIn('id', collect($items)->pluck('detail.produk_id')->unique())
                ->orderBy('id')->lockForUpdate()->get();
            $stokService = app(StokService::class);
            foreach (collect($items)->groupBy(fn ($item) => $item['cabang_id'] . ':' . $item['detail']->produk_id) as $group) {
                $first = $group->first();
                $produkId = (int) $first['detail']->produk_id;
                $tersedia = $stokService->saldoTersedia($produkId, $first['cabang_id']);
                $diminta = $group->sum(fn ($item) => (int) $item['detail']->jumlah);
                if ($diminta > $tersedia) {
                    throw ValidationException::withMessages(['items' => "Stok {$first['detail']->produk->namaLengkap} tidak cukup: tersedia {$tersedia}, dipilih {$diminta}."]);
                }
            }

            foreach ($items as $item) {
                $detail = $item['detail'];
                $sumber = $item['sumber'];
                if (AmbilBarang::where('sumber', $sumber)->where('detail_id', $detail->id)->exists()) {
                    throw ValidationException::withMessages(['items' => 'Barang untuk salah satu detail sudah diambil. Muat ulang halaman.']);
                }

                // Bila mutasi lama tidak bisa dialokasikan dengan pasti oleh
                // migrasi (mis. ID dokumen beririsan), jangan potong dua kali.
                $legacy = DB::table('produk_stoks')
                    ->where('detail_id', $item['parent']->id)
                    ->where('produk_id', $detail->produk_id)
                    ->where('cabang_id', $item['cabang_id'])
                    ->where('kode', $sumber === 'project' ? 'shp' : 'jual')
                    ->whereNotIn('id', AmbilBarang::query()->select('produk_stok_id')->whereNotNull('produk_stok_id'))
                    ->whereNull('deleted_at');
                if ($sumber === 'order') {
                    $legacy->where('keterangan', 'like', 'barang dijual ke%');
                }
                if ($legacy->exists()) {
                    throw ValidationException::withMessages([
                        'items' => 'Ada mutasi penjualan lama yang belum bisa dicocokkan otomatis untuk nota ' . $item['parent']->nota
                            . '. Periksa riwayat stok terlebih dahulu agar tidak dipotong dua kali.',
                    ]);
                }

                $stok = $stokService->kurang((int) $detail->produk_id, $item['cabang_id'], (int) $detail->jumlah,
                    'jual', 'ambil barang order ' . $item['parent']->nota . ' (' . $sumber . ' #' . $detail->id . ')',
                    $item['parent']->id);
                AmbilBarang::create([
                    'sumber' => $sumber,
                    'detail_id' => $detail->id,
                    'produk_id' => $detail->produk_id,
                    'cabang_id' => $item['cabang_id'],
                    'jumlah' => $detail->jumlah,
                    'produk_stok_id' => $stok->id,
                    'user_id' => auth()->id(),
                    'created_at' => now(),
                    'nota' => $item['parent']->nota,
                    'pembeli' => $sumber === 'project' ? $item['parent']->konsumen : $item['parent']->kontak?->nama,
                    'jenis' => $sumber === 'order' ? 'Offline' : ($item['parent']->buffer?->custom ? 'Proses' : 'Produk'),
                    'shipping' => $sumber === 'order' ? $item['parent']->pengiriman : $item['parent']->shipping,
                    'tanggal_order' => $item['parent']->created_at,
                ]);
            }

            return count($items);
        });
    }
}
