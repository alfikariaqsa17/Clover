<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Halaman ini hanya untuk admin.');
        }

        return $next($request);
    }
}
