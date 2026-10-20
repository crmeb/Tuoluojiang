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

namespace App\Mcp\Tools\Traits;

use Illuminate\Support\Carbon;

/**
 * 把用户口述的日期、关键词转成查询条件.
 *
 * 修改类工具不能要求用户提供数据库 ID。
 */
trait ResolvesNaturalRecord
{
    /**
     * 解析今天/昨天/明天或可识别的日期，失败返回 null.
     */
    protected function parseNaturalDay(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $tz    = (string) config('app.timezone');
        $today = Carbon::now($tz)->startOfDay();
        $day   = match ($value) {
            '今天', '今日' => $today,
            '昨天', '昨日' => $today->copy()->subDay(),
            '明天', '明日' => $today->copy()->addDay(),
            default        => null,
        };
        if ($day !== null) {
            return $day->toDateString();
        }

        try {
            return Carbon::parse($value, $tz)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 转义 LIKE 通配符，避免用户原文里的 %、_ 扩大匹配.
     */
    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    /**
     * 取第一个非空字符串参数.
     *
     * @param array<string, mixed> $arguments
     * @param array<int, string> $keys
     */
    protected function firstFilledString(array $arguments, array $keys): string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $arguments)) {
                continue;
            }
            $value = trim((string) $arguments[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * 是否显式要求取最近一条.
     *
     * @param array<string, mixed> $arguments
     */
    protected function wantsLatestRecord(array $arguments): bool
    {
        if (! array_key_exists('latest', $arguments)) {
            return false;
        }
        $value = $arguments['latest'];
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', '是'], true);
    }
}
