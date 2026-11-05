<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRingkasanRole
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(user_has_role_insensitive('super', 'manager'), 403);

        return $next($request);
    }
}
