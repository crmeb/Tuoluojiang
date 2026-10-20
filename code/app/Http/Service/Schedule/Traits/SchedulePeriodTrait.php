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

namespace App\Http\Service\Schedule\Traits;

use App\Constants\ScheduleEnum;
use Carbon\Carbon;

/**
 * 日程重复周期计算相关方法.
 */
trait SchedulePeriodTrait
{
    protected function updateTime(int $id, string $startTime = '', string $endTime = '', ?string $failTime = '')
    {
        if ($startTime && $endTime && $failTime) {
            $res1 = $this->dao->update($id, [
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'fail_time'  => Carbon::parse($failTime, $this->timeZone)->endOfDay()->toDateTimeString(),
            ]);
            $res2 = $this->remindDao->update(['sid' => $id], ['end_time' => $failTime]);
            $res = $res1 && $res2;
            $res && $this->dispatchTodoSync($id, 'update');
            return $res;
        }
        if ($failTime) {
            $res1 = $this->dao->update($id, ['fail_time' => Carbon::parse($failTime, $this->timeZone)->endOfDay()->toDateTimeString()]);
            $res2 = $this->remindDao->update(['sid' => $id], ['end_time' => $failTime]);
            $res = $res1 && $res2;
            $res && $this->dispatchTodoSync($id, 'update');
            return $res;
        }
        return false;
    }

    /**
     * 获取上个周期的截止时间.
     * @param mixed $oldStart
     * @param mixed $newStart
     * @param mixed $period
     * @param mixed $rate
     * @param mixed $days
     */
    protected function getPreviousPeriod($oldStart, $newStart, $period, $rate, $days = null): string
    {
        $failTime = $newStart;
        $days     = $days ? array_map('intval', $days) : [];
        switch ($period) {
            case ScheduleEnum::REPEAT_DAY:
                $failTime = Carbon::parse($newStart, $this->timeZone)->subDays($rate)->toDateTimeString();
                break;
            case ScheduleEnum::REPEAT_WEEK:
                rsort($days, SORT_NUMERIC);
                $day = Carbon::parse($newStart, $this->timeZone)->dayOfWeekIso;
                if ($day <= min($days)) {
                    $failDay  = Carbon::parse($oldStart, $this->timeZone)->subWeek()->startOfWeek()->addDays(max($days))->toDateString();
                    $failTime = Carbon::parse($oldStart, $this->timeZone)->toTimeString();
                    $failTime = Carbon::parse($failDay . ' ' . $failTime, $this->timeZone)->toDateTimeString();
                } else {
                    foreach ($days as $key => $val) {
                        if ($day > $val) {
                            $failDay  = Carbon::parse($oldStart, $this->timeZone)->startOfWeek()->addDays($val)->toDateString();
                            $failTime = Carbon::parse($oldStart, $this->timeZone)->toTimeString();
                            $failTime = Carbon::parse($failDay . ' ' . $failTime, $this->timeZone)->toDateTimeString();
                            break;
                        }
                        if ($day == $val) {
                            $failDay  = Carbon::parse($oldStart, $this->timeZone)->startOfWeek()->addDays($days[$key - 1])->toDateString();
                            $failTime = Carbon::parse($oldStart, $this->timeZone)->toTimeString();
                            $failTime = Carbon::parse($failDay . ' ' . $failTime, $this->timeZone)->toDateTimeString();
                            break;
                        }
                    }
                }
                break;
            case ScheduleEnum::REPEAT_MONTH:
                rsort($days, SORT_NUMERIC);
                $day = Carbon::parse($newStart, $this->timeZone)->format('d');
                if ($day <= min($days)) {
                    $failDay  = Carbon::parse($oldStart, $this->timeZone)->subMonth()->startOfMonth()->addDays(max($days))->toDateString();
                    $failTime = Carbon::parse($oldStart, $this->timeZone)->toTimeString();
                    $failTime = Carbon::parse($failDay . ' ' . $failTime, $this->timeZone)->toDateTimeString();
                } else {
                    foreach ($days as $key => $val) {
                        if ($day > $val) {
                            $failDay  = Carbon::parse($oldStart, $this->timeZone)->startOfMonth()->addDays($val)->toDateString();
                            $failTime = Carbon::parse($oldStart, $this->timeZone)->toTimeString();
                            $failTime = Carbon::parse($failDay . ' ' . $failTime, $this->timeZone)->toDateTimeString();
                            break;
                        }
                        if ($day == $val) {
                            $failDay  = Carbon::parse($oldStart, $this->timeZone)->startOfMonth()->addDays($days[$key - 1])->toDateString();
                            $failTime = Carbon::parse($oldStart, $this->timeZone)->toTimeString();
                            $failTime = Carbon::parse($failDay . ' ' . $failTime, $this->timeZone)->toDateTimeString();
                            break;
                        }
                    }
                }
                break;
            case ScheduleEnum::REPEAT_YEAR:
                $failTime = Carbon::parse($oldStart, $this->timeZone)->subYears($rate)->toDateTimeString();
                break;
        }
        return $failTime;
    }

    /**
     * 获取下个周期的时间.
     * @param mixed $newStart
     * @param mixed $newEnd
     * @param mixed $period
     * @param mixed $rate
     * @param mixed $days
     * @return array
     */
    protected function getNextPeriod($newStart, $newEnd, $period, $rate, $days = [])
    {
        $startTime = $newStart;
        $endTime   = $newEnd;
        switch ($period) {
            case ScheduleEnum::REPEAT_DAY:
                $startTime = Carbon::parse($newStart, $this->timeZone)->addDays($rate)->toDateTimeString();
                $endTime   = Carbon::parse($newEnd, $this->timeZone)->addDays($rate)->toDateTimeString();
                break;
            case ScheduleEnum::REPEAT_WEEK:
                sort($days, SORT_NUMERIC);
                $day = Carbon::parse($newStart, $this->timeZone)->dayOfWeekIso;
                if ($day >= max($days)) {
                    $startDay  = Carbon::parse($newStart, $this->timeZone)->addWeeks($rate)->startOfWeek()->addDays(min($days))->subDay()->toDateString();
                    $startTime = Carbon::parse($startDay . ' ' . Carbon::parse($newStart, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                    $endDay    = Carbon::parse($newEnd, $this->timeZone)->addWeeks($rate)->startOfWeek()->addDays(min($days))->subDay()->toDateString();
                    $endTime   = Carbon::parse($endDay . ' ' . Carbon::parse($newEnd, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                } else {
                    foreach ($days as $key => $val) {
                        if ($day == $val) {
                            $startDay  = Carbon::parse($newStart, $this->timeZone)->startOfWeek()->addDays($days[$key + 1])->subDay()->toDateString();
                            $startTime = Carbon::parse($startDay . ' ' . Carbon::parse($newStart, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                            $endDay    = Carbon::parse($newEnd, $this->timeZone)->startOfWeek()->addDays($days[$key + 1])->subDay()->toDateString();
                            $endTime   = Carbon::parse($endDay . ' ' . Carbon::parse($newEnd, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                            break;
                        }
                        if ($day < $val) {
                            $startDay  = Carbon::parse($newStart, $this->timeZone)->startOfWeek()->addDays($val)->subDay()->toDateString();
                            $startTime = Carbon::parse($startDay . ' ' . Carbon::parse($newStart, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                            $endDay    = Carbon::parse($newEnd, $this->timeZone)->startOfWeek()->addDays($val)->subDay()->toDateString();
                            $endTime   = Carbon::parse($endDay . ' ' . Carbon::parse($newEnd, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                            break;
                        }
                    }
                }
                break;
            case ScheduleEnum::REPEAT_MONTH:
                sort($days, SORT_NUMERIC);
                $day = Carbon::parse($newStart, $this->timeZone)->format('d');
                if ($day >= max($days)) {
                    $startDay  = Carbon::parse($newStart, $this->timeZone)->addMonth()->startOfMonth()->addDays(min($days))->subDay()->toDateString();
                    $startTime = Carbon::parse($startDay . ' ' . Carbon::parse($newStart, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                    $endDay    = Carbon::parse($newEnd, $this->timeZone)->addMonth()->startOfMonth()->addDays(min($days))->subDay()->toDateString();
                    $endTime   = Carbon::parse($endDay . ' ' . Carbon::parse($newEnd, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                } else {
                    foreach ($days as $key => $val) {
                        if ($day == $val) {
                            $startDay  = Carbon::parse($newStart, $this->timeZone)->startOfMonth()->addDays($days[$key + 1])->subDay()->toDateString();
                            $startTime = Carbon::parse($startDay . ' ' . Carbon::parse($newStart, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                            $endDay    = Carbon::parse($newEnd, $this->timeZone)->startOfMonth()->addDays($days[$key + 1])->subDay()->toDateString();
                            $endTime   = Carbon::parse($endDay . ' ' . Carbon::parse($newEnd, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                            break;
                        }
                        if ($day < $val) {
                            $startDay  = Carbon::parse($newStart, $this->timeZone)->startOfMonth()->addDays($val)->subDay()->toDateString();
                            $startTime = Carbon::parse($startDay . ' ' . Carbon::parse($newStart, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                            $endDay    = Carbon::parse($newEnd, $this->timeZone)->startOfMonth()->addDays($val)->subDay()->toDateString();
                            $endTime   = Carbon::parse($endDay . ' ' . Carbon::parse($newEnd, $this->timeZone)->toTimeString(), $this->timeZone)->toDateTimeString();
                            break;
                        }
                    }
                }
                break;
            case ScheduleEnum::REPEAT_YEAR:
                $startTime = Carbon::parse($newStart, $this->timeZone)->addYears($rate)->toDateTimeString();
                $endTime   = Carbon::parse($newEnd, $this->timeZone)->addYears($rate)->toDateTimeString();
                break;
        }
        return [$startTime, $endTime];
    }

    /**
     * 两个时间段是否交集.
     * @param mixed $start1
     * @param mixed $end1
     * @param mixed $start2
     * @param mixed $end2
     */
    protected function hasOverlap($start1, $end1, $start2, $end2): bool
    {
        if ($end1 < $start2 || $start1 > $end2) {
            // 两个时间段没有交集
            return false;
        }
        // 两个时间段有交集
        return true;
    }
}
