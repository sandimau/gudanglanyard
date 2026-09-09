<?php

namespace App\Http\Middleware;

use App\Models\Cabang;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureCabangAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $cabangs = collect();
        $cabangAktif = null;

        $cabangSemua = false;

        if (Auth::check()) {
            $cabangs = Cabang::aktif()->orderBy('nama')->get();

            $cabangId = session('cabang_id');
            $cabangSemua = $cabangId === 'all';

            // Login pertama / session kosong → default Semua Cabang
            if ($cabangId === null || $cabangId === '') {
                session(['cabang_id' => 'all']);
                $cabangId = 'all';
                $cabangSemua = true;
            } elseif (!$cabangSemua && !$cabangs->contains('id', (int) $cabangId)) {
                // Cabang tersimpan tidak valid → fallback Semua Cabang
                session(['cabang_id' => 'all']);
                $cabangId = 'all';
                $cabangSemua = true;
            }

            $cabangAktif = $cabangSemua ? null : $cabangs->firstWhere('id', (int) $cabangId);
        }

        View::share('cabangs', $cabangs);
        View::share('cabangAktif', $cabangAktif);
        View::share('cabangSemua', $cabangSemua);

        return $next($request);
    }
}
