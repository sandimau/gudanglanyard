<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKontenSetupRole
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(user_has_role_insensitive('super', 'manager'), 403);

        return $next($request);
    }
}
