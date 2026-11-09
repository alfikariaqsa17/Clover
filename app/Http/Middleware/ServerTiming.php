<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Alat diagnosis lag: saat APP_DEBUG=true, setiap respons membawa header "Server-Timing" berisi lama proses PHP,
 * waktu database, dan jumlah query. Lihat di browser: F12 > Network > klik permintaan > tab Timing.
 * Saat APP_DEBUG=false (produksi) middleware ini tidak melakukan apa pun.
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('app.debug')) {
            return $next($request);
        }

        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $queries = 0;
        $dbMs = 0.0;
        DB::listen(function ($q) use (&$queries, &$dbMs) {
            $queries++;
            $dbMs += $q->time;
        });

        $response = $next($request);
        $response->headers->set('Server-Timing', sprintf(
            'app;dur=%.0f, db;dur=%.0f;desc="%d query"',
            (microtime(true) - $start) * 1000, $dbMs, $queries
        ));

        return $response;
    }
}
