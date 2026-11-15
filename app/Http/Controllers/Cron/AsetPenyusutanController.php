<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Services\Accounting\AsetPenyusutanService;
use Illuminate\Http\Request;

class AsetPenyusutanController extends Controller
{
    /**
     * Endpoint untuk cron eksternal (mis. cron-job.org).
     * Selalu memproses bulan berjalan agar tidak bisa dipakai mengejar bulan lama.
     */
    public function __invoke(Request $request, AsetPenyusutanService $service)
    {
        $expected = (string) config('app.cron_token');

        if ($expected !== '' && ! hash_equals($expected, (string) $request->query('token', ''))) {
            abort(403, 'Token cron tidak valid.');
        }

        $hasil = $service->jalankan();

        return response()->json([
            'ok' => true,
            'periode' => now()->startOfMonth()->format('Y-m'),
            'diproses' => $hasil['diproses'],
            'dilewati' => $hasil['dilewati'],
            'gagal' => $hasil['gagal'],
        ]);
    }
}
