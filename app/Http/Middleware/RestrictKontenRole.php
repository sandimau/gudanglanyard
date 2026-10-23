<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictKontenRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!user_has_role_insensitive('konten')) {
            return $next($request);
        }

        if ($request->routeIs('dashboard')) {
            return redirect()->route(konten_landing_route());
        }

        $jadwalRoutes = ['konten.jadwal'];
        $prosesRoutes = ['konten.proses', 'konten.updateTab'];
        $sharedKontenRoutes = [
            'konten.semua',
            'konten.create',
            'konten.store',
            'konten.edit',
            'konten.update',
            'konten.destroy',
            'konten.chat.index',
            'konten.chat.store',
        ];

        $user = $request->user();

        if ($request->routeIs(...$jadwalRoutes)) {
            abort_unless($user->can('konten_jadwal_access'), 403);
        } elseif ($request->routeIs(...$prosesRoutes)) {
            abort_unless($user->can('konten_proses_access'), 403);
        } elseif ($request->routeIs(...$sharedKontenRoutes)) {
            abort_unless(
                $user->can('konten_jadwal_access') || $user->can('konten_proses_access'),
                403
            );
        } else {
            abort_unless($request->routeIs('profile.show', 'profile.update'), 403);
        }

        return $next($request);
    }
}
