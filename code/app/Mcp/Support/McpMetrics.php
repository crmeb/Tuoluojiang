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

namespace App\Mcp\Support;

use App\Mcp\ToolRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MCP 结构化监控指标.
 */
class McpMetrics
{
    private const CACHE_PREFIX = 'mcp:metrics:';

    public static function recordToolExecution(
        string $tool,
        int $durationMs,
        bool $isError,
        ?string $module = null
    ): void {
        if (! self::enabled()) {
            return;
        }

        $payload = [
            'metric'   => 'mcp_tool_duration_ms',
            'tool'     => $tool,
            'module'   => $module,
            'duration' => $durationMs,
            'is_error' => $isError,
        ];

        Log::channel((string) config('mcp.logging.channel', 'mcp'))->info('MCP metric', $payload);

        $toolKey = self::sanitizeKey($tool);
        Cache::increment(self::CACHE_PREFIX . 'tool_exec_total:' . $toolKey);
        Cache::increment(self::CACHE_PREFIX . 'tool_duration_ms_total:' . $toolKey, max(1, $durationMs));

        if ($isError) {
            Cache::increment(self::CACHE_PREFIX . 'tool_error_total:' . $toolKey);
            Log::channel((string) config('mcp.logging.channel', 'mcp'))->warning('MCP metric', [
                'metric' => 'mcp_tool_error_total',
                'tool'   => $tool,
                'module' => $module,
            ]);
        }
    }

    /**
     * 读取指标快照（便于运维排查或后续对接 Prometheus）.
     */
    public static function snapshot(string $tool): array
    {
        $toolKey = self::sanitizeKey($tool);

        return [
            'tool'              => $tool,
            'exec_total'        => (int) Cache::get(self::CACHE_PREFIX . 'tool_exec_total:' . $toolKey, 0),
            'error_total'       => (int) Cache::get(self::CACHE_PREFIX . 'tool_error_total:' . $toolKey, 0),
            'duration_ms_total' => (int) Cache::get(self::CACHE_PREFIX . 'tool_duration_ms_total:' . $toolKey, 0),
        ];
    }

    /**
     * 读取当前本地 MCP 工具聚合指标.
     *
     * @param null|array<int, string> $tools
     *
     * @return array{tools: array<int, array<string, mixed>>, exec_total: int, error_total: int, duration_ms_total: int, error_rate: float, avg_duration_ms: float}
     */
    public static function overview(?array $tools = null): array
    {
        $tools ??= ToolRegistry::getToolNames(null, false);
        $items = [];
        foreach (array_values(array_unique(array_filter(array_map('strval', $tools)))) as $tool) {
            $item = self::snapshot($tool);
            $item['module'] = ToolRegistry::getToolModule($tool);
            $item['avg_duration_ms'] = $item['exec_total'] > 0
                ? round($item['duration_ms_total'] / $item['exec_total'], 2)
                : 0;
            $items[] = $item;
        }

        $execTotal     = array_sum(array_column($items, 'exec_total'));
        $errorTotal    = array_sum(array_column($items, 'error_total'));
        $durationTotal = array_sum(array_column($items, 'duration_ms_total'));

        usort($items, static fn (array $left, array $right): int => $right['exec_total'] <=> $left['exec_total']);

        return [
            'tools'              => $items,
            'exec_total'         => $execTotal,
            'error_total'        => $errorTotal,
            'duration_ms_total'  => $durationTotal,
            'error_rate'         => $execTotal > 0 ? round($errorTotal / $execTotal, 4) : 0,
            'avg_duration_ms'    => $execTotal > 0 ? round($durationTotal / $execTotal, 2) : 0,
        ];
    }

    public static function enabled(): bool
    {
        return (bool) config('mcp.metrics.enabled', true);
    }

    private static function sanitizeKey(string $value): string
    {
        return preg_replace('/[^a-z0-9_\-]/', '_', strtolower($value)) ?: 'unknown';
    }
}
