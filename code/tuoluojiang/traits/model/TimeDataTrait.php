<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\traits\model;

use Carbon\CarbonImmutable;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * 时间搜索
 * Trait TimeDataTrait.
 *
 * @mixin  BaseModel
 */
trait TimeDataTrait
{
    /**
     * 时间查询字段名.
     */
    protected $timeField;

    /**
     * 设置时间查询字段.
     * @return $this
     */
    public function setTimeField(string $timeField)
    {
        $this->timeField = $timeField;

        return $this;
    }

    /**
     * 获取时间查询字段.
     */
    public function getTimeField(): string
    {
        if (! $this->timeField) {
            $this->timeField = $this->getCreatedAtColumn();
        }

        return $this->timeField;
    }

    /**
     * 时间查询作用域
     * @param mixed $value
     * @return Builder
     */
    public function scopeTime(Builder $query, $value)
    {
        $createTimeField = $this->getTimeField();

        $today = CarbonImmutable::today(); // 今天 00:00:00

        $result = match ($value) {
            // 今天
            'today' => $this->addDateRangeClause($query, $createTimeField, $today, $today->endOfDay()),
            // 昨天
            'yesterday' => $this->addDateRangeClause($query, $createTimeField, $today->subDay(), $today->subDay()->endOfDay()),
            // 本周
            'week' => $this->addDateRangeClause($query, $createTimeField, $today->startOfWeek(), $today->endOfWeek()->endOfDay()),
            // 上周
            'last week' => $this->addDateRangeClause($query, $createTimeField, $today->subWeek()->startOfWeek(), $today->subWeek()->endOfWeek()->endOfDay()),
            // 本月
            'month' => $this->addDateRangeClause($query, $createTimeField, $today->startOfMonth(), $today->endOfMonth()->endOfDay()),
            // 上月
            'last month' => $this->addDateRangeClause($query, $createTimeField, $today->subMonth()->startOfMonth(), $today->subMonth()->endOfMonth()->endOfDay()),
            // 今年
            'year' => $this->addDateRangeClause($query, $createTimeField, $today->startOfYear(), $today->endOfYear()->endOfDay()),
            // 去年
            'last year' => $this->addDateRangeClause($query, $createTimeField, $today->subYear()->startOfYear(), $today->subYear()->endOfYear()->endOfDay()),
            // 本季度
            'quarter' => $this->addDateRangeClause($query, $createTimeField, $today->startOfQuarter(), $today->endOfQuarter()->endOfDay()),
            // 最近7天
            'lately7' => $this->addDateRangeClause($query, $createTimeField, $today->subDays(6), $today->endOfDay()),
            // 最近30天
            'lately30' => $this->addDateRangeClause($query, $createTimeField, $today->subDays(29), $today->endOfDay()),
            // 未来30天
            'future30' => $this->addDateRangeClause($query, $createTimeField, $today, $today->addDays(29)->endOfDay()),
            default    => null
        };
        if ($result) {
            return $result;
        }
        // 处理预定义时间范围
        if (isset($timeHandlers[$value])) {
            return $timeHandlers[$value]();
        }
        // 处理自定义日期范围（含 "-" 分隔符）
        if (str_contains($value, '-')) {
            return $this->handleCustomDateRange($query, $createTimeField, $value);
        }
        // 处理自定义最近天数（lately+数字格式）
        if (preg_match('/^lately([1-9]\d*)$/', $value, $matches)) {
            $days = (int) $matches[1];
            return $this->addDateRangeClause($query, $createTimeField, $today->subDays($days - 1), $today->endOfDay());
        }
        // 处理自定义未来天数（future+数字格式）
        if (preg_match('/^future([1-9]\d*)$/', $value, $matches)) {
            $days = (int) $matches[1];
            return $this->addDateRangeClause($query, $createTimeField, $today, $today->addDays($days)->endOfDay());
        }
        // 处理自定义N天前（before_days+数字格式）
        if (preg_match('/^before_days([1-9]\d*)$/', $value, $matches)) {
            $days = (int) $matches[1];
            return $this->where($createTimeField, '<=', $today->subDays($days)->endOfDay()->toDateTimeString());
        }
        return $query;
    }

    /**
     * 添加时间范围查询条件.
     * @param mixed $query
     * @param mixed $field
     * @param mixed $start
     * @param mixed $end
     * @return mixed
     */
    protected function addDateRangeClause($query, $field, $start, $end)
    {
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }
        return $query->whereBetween($field, [$start->toDateTimeString(), $end->toDateTimeString()]);
    }

    /**
     * 处理自定义日期范围（拆分复杂逻辑）.
     */
    private function handleCustomDateRange(Builder $query, string $field, string $value): Builder
    {
        // 限制分割为两部分，避免多 "-" 场景错误
        [$startTime, $endTime] = array_pad(explode('-', $value, 2), 2, '');
        $startTime             = str_replace('/', '-', trim($startTime));
        $endTime               = str_replace('/', '-', trim($endTime));
        // 纯日期范围（无时间部分）
        if (! str_contains($startTime, ':') && ! str_contains($endTime, ':')) {
            $endDate = Carbon::parse($endTime)->timezone(config('app.timezone'))->addDay()->toDateString();
            return $query->whereDate($field, '>=', $startTime)
                ->whereDate($field, '<', $endDate);
        }
        // 完整时间范围处理
        if ($startTime && $endTime) {
            $end = $startTime === $endTime
                ? date('Y-m-d H:i:s', strtotime($endTime) + 86400)
                : $endTime;
            return $query->whereBetween($field, [$startTime, $end]);
        }
        // 单边时间范围（优化条件判断）
        if ($endTime) {
            return $query->where($field, '<', $endTime);
        }
        if ($startTime) {
            return $query->where($field, '>=', $startTime);
        }
        return $query;
    }
}
