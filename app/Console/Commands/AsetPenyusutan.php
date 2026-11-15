<?php

namespace App\Console\Commands;

use App\Services\Accounting\AsetPenyusutanService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AsetPenyusutan extends Command
{
    protected $signature = 'aset:penyusutan {--periode= : Periode dalam format YYYY-MM}';

    protected $description = 'Mencatat penyusutan bulanan aset tetap';

    public function handle(AsetPenyusutanService $service): int
    {
        $periode = null;

        if ($this->option('periode')) {
            try {
                $periode = Carbon::createFromFormat('Y-m', (string) $this->option('periode'))->startOfMonth();
            } catch (\Throwable $exception) {
                $this->error('Format periode harus YYYY-MM.');
                return self::FAILURE;
            }
        }

        $hasil = $service->jalankan($periode);

        $this->info(sprintf(
            'Penyusutan aset selesai: %d diproses, %d dilewati, %d gagal.',
            $hasil['diproses'],
            $hasil['dilewati'],
            $hasil['gagal']
        ));

        return self::SUCCESS;
    }
}
