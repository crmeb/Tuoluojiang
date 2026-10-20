<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Traits;

use Illuminate\Support\Facades\Request;

trait InteractsWithService
{
    protected function applyPage(array $arguments): array
    {
        $limit = min(100, max(1, (int) ($arguments['limit'] ?? 20)));
        $page  = max(1, (int) ($arguments['page'] ?? 1));

        if (isset($arguments['offset'])) {
            $offset = max(0, (int) $arguments['offset']);
            $page   = (int) ($offset / $limit) + 1;
        }

        Request::merge([
            'page'  => $page,
            'limit' => $limit,
        ]);

        return [$page, $limit];
    }

    protected function onlyFilled(array $arguments, array $map): array
    {
        $where = [];
        foreach ($map as $argumentKey => $whereKey) {
            if (is_int($argumentKey)) {
                $argumentKey = $whereKey;
            }
            if (array_key_exists($argumentKey, $arguments) && $arguments[$argumentKey] !== '' && $arguments[$argumentKey] !== null) {
                $where[$whereKey] = $arguments[$argumentKey];
            }
        }
        return $where;
    }

    protected function dateRange(array $arguments, string $startKey = 'start_date', string $endKey = 'end_date'): string
    {
        $start = $arguments[$startKey] ?? '';
        $end   = $arguments[$endKey] ?? '';
        $start = is_string($start) ? str_replace('-', '/', $start) : $start;
        $end   = is_string($end) ? str_replace('-', '/', $end) : $end;
        if ($start && $end) {
            return $start . '-' . $end;
        }
        if ($start) {
            return $start . '-' . $start;
        }
        if ($end) {
            return $end . '-' . $end;
        }
        return '';
    }

    protected function timeRange(array $arguments, string $timeKey = 'time'): string
    {
        $range = $this->dateRange($arguments);
        if ($range) {
            return $range;
        }

        $time = trim((string) ($arguments[$timeKey] ?? ''));
        if ($time === '') {
            return '';
        }

        if (preg_match_all('/\d{4}[\/-]\d{1,2}[\/-]\d{1,2}(?:\s+\d{1,2}:\d{1,2}(?::\d{1,2})?)?/', $time, $matches) >= 2) {
            return str_replace('-', '/', $matches[0][0]) . '-' . str_replace('-', '/', $matches[0][1]);
        }

        return $time;
    }

    /**
     * 管理端业绩趋势默认时间：近一个月至今天（YYYY/MM/DD-YYYY/MM/DD）.
     */
    protected function defaultPerformanceTimeRange(): string
    {
        return date('Y/m/d', datetime_timestamp('-1 month')) . '-' . date('Y/m/d');
    }

    /**
     * 解析业绩统计时间；空则回退到管理端默认近一个月.
     */
    protected function performanceTimeRange(array $arguments, string $timeKey = 'time'): string
    {
        $time = $this->timeRange($arguments, $timeKey);

        return $time !== '' ? $time : $this->defaultPerformanceTimeRange();
    }

    protected function toArray(mixed $data): array
    {
        if (! $data) {
            return [];
        }
        if (is_array($data)) {
            return $data;
        }
        if (method_exists($data, 'toArray')) {
            return $data->toArray();
        }
        return (array) $data;
    }

    protected function intArray(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $items = [];
        foreach ((array) $value as $item) {
            if (is_array($item)) {
                $items = array_merge($items, $this->intArray($item));
                continue;
            }

            $items = array_merge($items, preg_split('/[,，、;；]+/', (string) $item) ?: []);
        }

        return array_values(array_unique(array_filter(array_map('intval', $items))));
    }

    protected function missingId(string $name): array
    {
        return ['error' => true, 'message' => $name . '不能为空'];
    }
}
