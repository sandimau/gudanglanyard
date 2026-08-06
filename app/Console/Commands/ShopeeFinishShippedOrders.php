<?php

namespace App\Console\Commands;

use App\Http\Controllers\Webhook\BufferController;
use Illuminate\Console\Command;

class ShopeeFinishShippedOrders extends Command
{
    protected $signature = 'shopee:finish-shipped';

    protected $description = 'Cek ulang status order Shopee yang masih SHIPPED ke API Shopee; kalau sudah COMPLETED, buffernya dihapus otomatis.';

    public function handle(BufferController $bufferController): int
    {
        $summary = $bufferController->completeShippedOrders();

        $this->info("Diperiksa: {$summary['diperiksa']} order SHIPPED ({$summary['berhasil_dicek']} berhasil dicek, {$summary['gagal_dicek']} gagal), {$summary['selesai']} sudah COMPLETED, {$summary['dihapus']} baris buffer dihapus.");

        if (!empty($summary['status_terbaru'])) {
            $this->info('Rincian status terbaru: ' . json_encode($summary['status_terbaru']));
        }

        if (!empty($summary['contoh_error'])) {
            $this->warn('Contoh error: ' . json_encode($summary['contoh_error']));
        }

        return self::SUCCESS;
    }
}
