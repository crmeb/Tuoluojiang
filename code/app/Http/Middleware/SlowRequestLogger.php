<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SlowRequestLogger
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (! (bool) env('SLOW_REQUEST_LOG_ENABLE', false)) {
            return $next($request);
        }

        $startedAt     = microtime(true);
        $slowSqlMs     = (float) env('SLOW_SQL_LOG_THRESHOLD_MS', 200);
        $slowRequestMs = (float) env('SLOW_REQUEST_LOG_THRESHOLD_MS', 800);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $response = $next($request);
        } finally {
            $queries = DB::getQueryLog();
            DB::flushQueryLog();
            DB::disableQueryLog();
        }

        $costMs      = round((microtime(true) - $startedAt) * 1000, 2);
        $queryTimeMs = array_sum(array_map(fn ($query) => (float) ($query['time'] ?? 0), $queries));
        $slowQueries = collect($queries)
            ->filter(fn ($query) => (float) ($query['time'] ?? 0) >= $slowSqlMs)
            ->take(5)
            ->map(fn ($query) => [
                'time_ms' => (float) ($query['time'] ?? 0),
                'sql'     => $query['query'] ?? '',
            ])
            ->values()
            ->all();

        if ($costMs < $slowRequestMs) {
            return $response;
        }

        Log::warning('slow api request', [
            'method'        => $request->method(),
            'path'          => $request->path(),
            'route'         => $request->route()?->uri(),
            'status'        => $response->getStatusCode(),
            'cost_ms'       => $costMs,
            'query_count'   => count($queries),
            'query_time_ms' => round($queryTimeMs, 2),
            'slow_queries'  => $slowQueries,
        ]);

        return $response;
    }
}
