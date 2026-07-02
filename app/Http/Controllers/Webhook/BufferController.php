<?php

namespace App\Http\Controllers\Webhook;

use App\Models\Produk;
use App\Models\Belanja;
use App\Models\Produksi;
use App\Models\BukuBesar;
use App\Models\ProjectMp;
use App\Models\AkunDetail;
use App\Models\ProdukStok;
use App\Models\Marketplace;
use App\Models\ProdukModel;
use App\Models\BelanjaDetail;
use App\Models\MarketplaceLog;
use App\Models\ProjectMpDetail;
use App\Models\MarketplaceBuffer;
use App\Models\ProdukMarketplace;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ShopeeApi;
use App\Http\Controllers\Traits\MarketplaceTriger;
use App\Services\ShopeeStockSyncService;
use Illuminate\Support\Facades\DB;

class BufferController extends Controller
{
    use ShopeeApi, MarketplaceTriger;

    public function wallet($marketplace = false)
    {
        if ($marketplace) {
            $marketplaces = [$marketplace];
        } else {
            $marketplaces = Marketplace::where('marketplace', 'shopee')->whereNotNull('shop_id')->get();
        }

        foreach ($marketplaces as $marketplace) {
            try {
                $loop_api = true;
                $page_no = 0;

                $projectMp = [];
                $penarikanMp = [];
                $iklan = [];
                $last = false;
                $apiSuccess = false;

                $terakhir = BukuBesar::where('akun_detail_id', $marketplace->kas_id)->latest()->first();
                $from = $terakhir ? strtotime($terakhir->created_at) - 1 : strtotime("-3 days");

                $tokenError = false;

                while ($loop_api) {
                    $page_no++;
                    $param = [
                        'page_no' => $page_no,
                        'page_size' => '100',
                        'create_time_from' => $from,
                        'create_time_to' => strtotime("now")
                    ];

                    $api = $this->ambilApi($marketplace, 'payment/get_wallet_transaction_list', $param);

                    // Jika error karena token Shopee bermasalah
                    if ($this->isTokenApiError($api)) {
                        try {
                            if ($this->refreshMarketplaceToken($marketplace->id)) {
                                $marketplace = Marketplace::find($marketplace->id);
                                $this->logError($marketplace, 'wallet api error(token)', 'Token Shopee di-refresh otomatis, retry API');
                                $page_no--;
                                continue;
                            }
                            $this->logError($marketplace, 'wallet token refresh gagal', 'Gagal refresh token otomatis');
                        } catch (\Exception $tokenEx) {
                            $this->logError($marketplace, 'wallet token manual refresh gagal', $tokenEx->getMessage());
                        }
                        $tokenError = true;
                        $this->logError($marketplace, 'wallet api error', $api);
                        $loop_api = false;
                        continue;
                    }

                    if (isset($api['error']) && !empty($api['error'])) {
                        $this->logError($marketplace, 'wallet api error', $api);
                        $loop_api = false;
                        continue;
                    }

                    if (!empty($api['response'])) {
                        $apiSuccess = true;
                        $transcation = $api['response']['transaction_list'] ?? [];

                        if (!($api['response']['more'] ?? false)) {
                            $loop_api = false;
                        }

                        // Ambil transaksi terbaru untuk update saldo
                        if ($page_no == 1 && !empty($transcation[0])) {
                            $last = $transcation[0];
                        }

                        foreach ($transcation as $value) {
                            // Skip jika transaksi sudah pernah diproses
                            if ($terakhir) {
                                $txTime = date("Y-m-d H:i:s", $value['create_time']);
                                if ($txTime == $terakhir->created_at && $value['current_balance'] == $terakhir->debet) {
                                    continue;
                                }
                            }

                            if ($value['transaction_tab_type'] === 'wallet_wallet_payment') {
                                $harga = abs($value['amount']);
                                $iklan[] = [
                                    'total' => $harga,
                                    'harga' => $harga,
                                    'created_at' => date('Y-m-d H:i:s', $value['create_time']),
                                    'keterangan' => $value['description'] ?? $value['txn_title'] ?? ($value['transaction_tab_type'] . ' ' . $marketplace->nama),
                                ];
                            }

                            if ($value['transaction_tab_type'] == "wallet_order_income") {
                                $project = ProjectMp::where('nota', $value['order_sn'])->first();

                                $persen = 0;
                                if ($project && $project->total > 0) {
                                    $persen = ($project->total - $value['amount']) / $project->total * 100;
                                }

                                $projectMp[] = [
                                    'nota' => $value['order_sn'],
                                    'bersih' => $value['amount'],
                                    'persen' => floor($persen)
                                ];
                            }

                            if ($value['transaction_tab_type'] == 'wallet_withdrawals' && $value['amount'] != 0) {
                                $penarikanMp[] = [
                                    'akun_detail_id' => $marketplace->penarikan_id,
                                    'kode' => 'trf',
                                    'ket' => $value['transaction_tab_type'] . ' ' . $marketplace->nama,
                                    'detail_id' => $value['withdrawal_id'],
                                    'debet' => abs($value['amount']),
                                    'kredit' => 0,
                                    'created_at' => date("Y-m-d H:i:s", $value['create_time']),
                                    'updated_at' => now(),
                                ];
                            }
                        }
                    } else {
                        $loop_api = false;
                    }
                }

                // Jika ada error token Shopee, tidak perlu proses lebih jauh dan berikan pesan jelas
                if ($tokenError) {
                    // Stop di sini
                    continue;
                }

                // Proses jika API berhasil dipanggil
                if ($apiSuccess) {
                    if ($last) {
                        try {
                            BukuBesar::where('akun_detail_id', $marketplace->kas_id)->delete();
                            BukuBesar::create([
                                'akun_detail_id' => $marketplace->kas_id,
                                'kode' => 'byr',
                                'ket' => 'saldo akhir',
                                'debet' => $last['current_balance'],
                                'kredit' => 0,
                                'created_at' => date("Y-m-d H:i:s", $last['create_time'])
                            ]);
                        } catch (\Exception $e) {
                            $this->logError($marketplace, 'update saldo buku besar', $e->getMessage());
                        }
                    }

                    if (!empty($projectMp)) {
                        try {
                            ProjectMp::upsert($projectMp, ['nota'], ['bersih', 'persen']);
                        } catch (\Exception $e) {
                            $this->logError($marketplace, 'update project mp', $e->getMessage());
                        }
                    }

                    if (!empty($penarikanMp)) {
                        try {
                            DB::transaction(function () use ($penarikanMp, $marketplace) {
                                // Lock marketplace untuk mencegah race condition (double input saat webhook/cron bersamaan)
                                Marketplace::where('id', $marketplace->id)->lockForUpdate()->first();

                                // Ambil SEMUA detail_id yang sudah ada (tanpa limit) agar tidak double
                                $existingDetailIds = BukuBesar::where('akun_detail_id', $marketplace->penarikan_id)
                                    ->where('kode', 'trf')
                                    ->whereNotNull('detail_id')
                                    ->pluck('detail_id')
                                    ->flip()
                                    ->all();

                                foreach ($penarikanMp as $data) {
                                    $detailId = $data['detail_id'] ?? null;
                                    if ($detailId !== null && isset($existingDetailIds[$detailId])) {
                                        continue; // sudah ada di DB, skip
                                    }
                                    BukuBesar::create($data);
                                    if ($detailId !== null) {
                                        $existingDetailIds[$detailId] = true; // catat agar batch ini tidak double
                                    }
                                }
                            });
                            // Tidak perlu update saldo manual ke AkunDetail, karena sudah dilakukan di BukuBesar::boot()
                        } catch (\Exception $e) {
                            $this->logError($marketplace, 'insert penarikan', $e->getMessage());
                        }
                    }

                    if (!empty($iklan)) {
                        if (!$marketplace->iklan) {
                            $this->logError($marketplace, 'insert iklan', 'Produk iklan belum diset di marketplace');
                        } else {
                            try {
                                foreach (array_reverse($iklan) as $item) {
                                    $sudahAda = Belanja::where('kontak_id', $marketplace->kontak_id)
                                        ->where('created_at', $item['created_at'])
                                        ->where('total', $item['total'])
                                        ->where('akun_detail_id', $marketplace->kas_id)
                                        ->exists();

                                    if ($sudahAda) {
                                        continue;
                                    }

                                    $belanja = Belanja::create([
                                        'nota' => request()->nota ?: rand(1000000, 100),
                                        'total' => $item['total'],
                                        'kontak_id' => $marketplace->kontak_id,
                                        'akun_detail_id' => $marketplace->kas_id,
                                        'pembayaran' => $item['total'],
                                        'created_at' => $item['created_at'],
                                    ]);

                                    BelanjaDetail::create([
                                        'belanja_id' => $belanja->id,
                                        'produk_id' => $marketplace->iklan,
                                        'harga' => $item['harga'],
                                        'jumlah' => 1,
                                        'keterangan' => $item['keterangan'],
                                    ]);
                                }
                            } catch (\Exception $e) {
                                $this->logError($marketplace, 'insert iklan', $e->getMessage());
                            }
                        }
                    }
                } else {
                    $this->logError($marketplace, 'wallet api', 'Tidak ada response dari API wallet');
                }
            } catch (\Exception $e) {
                $this->logError($marketplace, 'wallet error', $e->getMessage());
            }
        }
    }

    private function logError($marketplace, $jenis, $isi, $shop_id = null)
    {
        MarketplaceLog::create([
            'isi' => is_array($isi) ? json_encode($isi) : $isi,
            'jenis' => $jenis,
            'shop_id' => $marketplace->shop_id ?? $shop_id,
            'marketplace' => $marketplace->nama ?? null,
            'tanggal' => now()
        ]);
    }

    private function ambilApiWithTokenRecovery($marketplace, $path, $param = [])
    {
        $api = $this->ambilApi($marketplace, $path, $param);

        if (empty($api['response']) && $this->isTokenApiError($api)) {
            if ($this->refreshMarketplaceToken($marketplace->id)) {
                $marketplace = Marketplace::find($marketplace->id);
                $api = $this->ambilApi($marketplace, $path, $param);
            } else {
                $this->logError($marketplace, 'token refresh gagal', $api);
            }
        }

        return [$api, $marketplace];
    }

    /**
     * Cek marketplace_buffers yang belum punya project_id (belum diproses).
     * URL: /buffer/pending
     */
    public function cekBufferPending()
    {
        $buffers = MarketplaceBuffer::whereNull('project_id')
            ->leftJoin('marketplaces', 'marketplaces.shop_id', '=', 'marketplace_buffers.shop_id')
            ->select(
                'marketplace_buffers.id',
                'marketplace_buffers.nota',
                'marketplace_buffers.shop_id',
                'marketplace_buffers.mp',
                'marketplace_buffers.status',
                'marketplace_buffers.created_at',
                'marketplace_buffers.updated_at',
                'marketplaces.nama as nama_marketplace'
            )
            ->orderBy('marketplace_buffers.created_at', 'desc')
            ->get();

        $byShop = $buffers->groupBy('shop_id')->map(function ($items, $shopId) {
            return [
                'shop_id' => $shopId,
                'nama_marketplace' => $items->first()->nama_marketplace,
                'jumlah' => $items->count(),
                'status' => $items->countBy('status')->sortDesc(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'total' => $buffers->count(),
            'by_shop' => $byShop,
            'data' => $buffers,
        ]);
    }

    public function prosesBuffer()
    {
        $ambil = MarketplaceBuffer::where('mp', 'shopee')
            ->whereNull('project_id')
            ->orderBy('shop_id')
            ->get();

        $mps = $ambil->groupBy('shop_id');

        foreach ($mps as $shop_id => $mp) {

            $marketplace = Marketplace::where('shop_id', $shop_id)->first();

            if ($marketplace) {

                $nota = [];
                $i = 0;
                foreach ($mp as $push) {
                    $i++;
                    if ($i > 40) {
                        break;
                    }
                    if ($push->status != 'CANCELLED')
                        $nota[] = $push->nota;
                    else
                        MarketplaceBuffer::where('mp', 'shopee')->where('id', $push->id)->delete();
                }

                $param = [
                    'order_sn_list' => implode(',', $nota),
                    "response_optional_fields" => "item_list,buyer_username,total_amount,shipping_carrier"
                ];

                [$api, $marketplace] = $this->ambilApiWithTokenRecovery($marketplace, 'order/get_order_detail', $param);

                if (!empty($api['response'])) {

                    foreach ($api['response']['order_list'] as $orderlist) {

                        $keterangan = preg_replace('/[^\x20-\x7E]/', '', $orderlist['message_to_seller']);

                        $nota = $orderlist['order_sn'];

                        $baru = false;
                        $project_id = null;

                        DB::transaction(function () use ($nota, $orderlist, $marketplace, $keterangan, &$baru, &$project_id) {
                            $existingProject = ProjectMp::where('nota', $nota)->lockForUpdate()->first();

                            if ($existingProject) {
                                $project_id = $existingProject->id;

                                MarketplaceBuffer::where('mp', 'shopee')->where('nota', $nota)->update([
                                    'project_id' => $project_id,
                                    'marketplace_id' => $marketplace->id,
                                ]);

                                return;
                            }

                            $created_at = date('Y-m-d H:i:s', $orderlist['create_time']);
                            $deathline = date('Y-m-d H:i:s', strtotime($created_at . ' +6 days'));
                            $projectMp = ProjectMp::create([
                                'marketplace_id' => $marketplace->id,
                                'nota' => $nota,
                                'total' => $orderlist['total_amount'],
                                'konsumen' => $orderlist['buyer_username'],
                                'keterangan' => $keterangan,
                                'shipping' => $orderlist['shipping_carrier'],
                                'created_at' => $created_at,
                                'deadline' => $deathline,
                            ]);
                            $project_id = $projectMp->id;
                            $baru = true;
                        });

                        if ($baru) {
                            $items = $orderlist['item_list'];

                            $orderdetail = [];

                            $custom = null;
                            $hargaTotal = 0;
                            foreach ($items as $item) {

                                $sku = $item['model_sku'];
                                if (empty($sku))
                                    $sku = $item['item_sku'];

                                if ($sku != 'NON_PRODUK') {
                                    if (strpos($sku, 'CUSTOM_') !== false) {
                                        $custom = 1;
                                        $sku = str_replace('CUSTOM_', "", $sku);
                                    }

                                    $jumlah = $item['model_quantity_purchased'];
                                    $harga = $item['model_discounted_price'];

                                    $hargaTotal += $harga * $jumlah;

                                    $paket = 1;
                                    if (strpos($sku, '_') !== false) {
                                        $skuParts = explode('_', $sku);
                                        $sku = $skuParts[0];
                                        $paket = $skuParts[1];
                                        $jumlah = $jumlah * $paket;
                                        $harga = floor($harga / $paket);
                                    }

                                    $item_id = $item['item_id'];
                                    $model_id = $item['model_id'];

                                    ProdukMarketplace::upsert([
                                        [
                                            'model_id' => $model_id,
                                            'item_id' => $item_id,
                                            'produk_id' => $sku,
                                            'marketplace_id' => $marketplace->id,
                                            'paket' => $paket,
                                            'harga' => $harga,
                                            'nama' => $item['item_name'],
                                            'varian' => $item['model_name'],
                                            'created_at' => now(),
                                            'updated_at' => now()
                                        ]
                                    ], ['model_id', 'item_id'], ['produk_id', 'paket', 'harga', 'nama', 'varian', 'updated_at']);

                                    $hpp = 0;
                                    $produk = Produk::find($sku);
                                    $model_id = $produk->produk_model_id ?? 0;
                                    $stok = ProdukModel::find($model_id)->stok ?? 0;
                                    if ($stok == 1 && !$custom) {
                                        $this->mpBeli($sku, $marketplace, $jumlah, $project_id);
                                        $hpp = $produk->hpp ?? 0;
                                    }

                                    // Get first produksi for custom orders
                                    $produksi_id = null;
                                    if ($custom) {
                                        $firstProduksi = Produksi::where('nama', '!=', 'finish')
                                            ->where('nama', '!=', 'batal')
                                            ->orderBy('urutan')
                                            ->first();
                                        $produksi_id = $firstProduksi->id ?? null;
                                    }

                                    $orderdetail[] = [
                                        'harga' => $harga,
                                        'jumlah' => $jumlah,
                                        'produk_id' => $sku,
                                        'nota' => $nota,
                                        'tema' => $item['model_name'],
                                        'project_id' => $project_id,
                                        'hpp' => $hpp,
                                        'produksi_id' => $produksi_id,
                                    ];
                                }
                            }

                            if (!empty($orderdetail)) {
                                ProjectMpDetail::insert($orderdetail);
                                ProjectMp::where('id', $project_id)->update(['total' => $hargaTotal]);
                            }

                            MarketplaceBuffer::where('mp', 'shopee')->where('nota', $nota)->update([
                                'project_id' => $project_id,
                                'custom' => $custom,
                                'marketplace_id' => $marketplace->id
                            ]);
                        }
                    }
                } else {
                    $this->logError($marketplace, 'proses buffer', $api);
                }
            }
        }

        MarketplaceBuffer::where('mp', 'shopee')
            ->whereNotNull('project_id')
            ->where('status', 'COMPLETED')
            ->delete();

        /////////////3. memproses yg cancel
        $this->hapusCancelShopee();
    }

    public function hapusCancelShopee()
    {
        $ambil = MarketplaceBuffer::where('mp', 'shopee')
            ->whereNotNull('project_id')
            ->where('status', 'CANCELLED')
            ->get();

        foreach ($ambil as $cancel) {
            $project = ProjectMp::find($cancel->project_id);

            $details = ProjectMpDetail::select('project_mp_details.produk_id', 'produk_models.stok')
                ->where('project_id', $cancel->project_id)
                ->leftJoin('produks', 'produks.id', '=', 'project_mp_details.produk_id')
                ->leftJoin('produk_models', 'produk_models.id', '=', 'produks.produk_model_id')
                ->get();

            ProdukStok::where('detail_id', $cancel->project_id)->where('kode', 'shp')->forceDelete();
            foreach ($details as $detail) {
                $this->updateStokMp($detail->produk_id);
                app(ShopeeStockSyncService::class)->markDirty((int) $detail->produk_id);
            }
            ProjectMpDetail::where('project_id', $cancel->project_id)->delete();
            ProjectMp::where('id', $cancel->project_id)->delete();
            MarketplaceBuffer::where('mp', 'shopee')->where('id', $cancel->id)->delete();
        }
    }

    public function bersihkanBuffer()
    {
        $buffers = MarketplaceBuffer::where('mp', 'shopee')
            ->where(function ($query) {
                $query->where('created_at', '<', now()->subDays(7))
                    ->orWhereNotIn('status', ['READY_TO_SHIP', 'CANCELLED', 'UNPAID', 'PROCESSED', 'SHIPPED', 'TO_CONFIRM_RECEIVE']);
            })
            ->where('status', '!=', 'TO_RETURN')
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get();

        $buffers = $buffers->groupBy('shop_id');

        foreach ($buffers as $shop_id => $buffer) {
            $notaArray = [];
            foreach ($buffer as $detail) {
                $notaArray[] = $detail->nota;
            }
            $notaString = implode(',', $notaArray);
            $param = [
                'order_sn_list' => $notaString
            ];
            $marketplace = Marketplace::where('shop_id', $shop_id)->first();
            if (!$marketplace) {
                $this->logError(null, 'bersihkan buffer', "Marketplace tidak ditemukan untuk shop_id: {$shop_id}", $shop_id);
                continue;
            }
            [$api, $marketplace] = $this->ambilApiWithTokenRecovery($marketplace, 'order/get_order_detail', $param);

            if (!empty($api['response'])) {
                foreach ($api['response']['order_list'] as $orderlist) {
                    $nota = $orderlist['order_sn'];
                    $status = $orderlist['order_status'];
                    MarketplaceBuffer::where('mp', 'shopee')->where('nota', $nota)->update(['status' => $status]);
                }
            } else {
                $this->logError($marketplace, 'bersihkan buffer', $api);
            }
        }
    }

    public function updateBufferCancel()
    {
        $marketplaces = Marketplace::where('marketplace', 'shopee')
            ->whereNotNull('shop_id')
            ->get();

        foreach ($marketplaces as $marketplace) {

            $shop_id = $marketplace->shop_id;
            $buffers = MarketplaceBuffer::where('mp', 'shopee')
                ->where('shop_id', $shop_id)
                ->orderBy('id', 'asc')
                ->where('status', 'IN_CANCEL')
                ->limit(40)
                ->get();

            $notaArray = [];
            foreach ($buffers as $buffer) {
                $notaArray[] = $buffer->nota;

                //update status delivery failed
                $param = [
                    'order_sn' => $buffer->nota,
                ];

                [$api, $marketplace] = $this->ambilApiWithTokenRecovery($marketplace, 'logistics/get_tracking_info', $param);
                if (!empty($api['response'])) {
                    if (isset($api['response']['order_sn']) && isset($api['response']['logistics_status'])) {
                        $nota = $api['response']['order_sn'];
                        $status = $api['response']['logistics_status'];
                        if ($status === 'LOGISTICS_DELIVERY_FAILED') {
                            MarketplaceBuffer::where('mp', 'shopee')
                                ->where('nota', $nota)
                                ->update(['status' => $status]);
                        }
                    }
                } else {
                    $this->logError($marketplace, 'update buffer cancel', $api);
                }
            }
            $notaString = implode(',', $notaArray);

            $param = [
                'order_sn_list' => $notaString
            ];

            [$api, $marketplace] = $this->ambilApiWithTokenRecovery($marketplace, 'order/get_order_detail', $param);

            if (!empty($api['response'])) {
                foreach ($api['response']['order_list'] as $orderlist) {
                    $nota = $orderlist['order_sn'];
                    $status = $orderlist['order_status'];
                    MarketplaceBuffer::where('mp', 'shopee')->where('nota', $nota)->update(['status' => $status]);
                }
            } else {
                $this->logError($marketplace, 'update buffer cancel', $api);
            }
        }
    }
}
