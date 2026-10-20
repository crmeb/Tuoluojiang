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

namespace App\Http\Service\Attendance\Traits;

use App\Http\Service\Attendance\AttendanceArrangeService;
use App\Http\Service\Attendance\AttendanceGroupService;
use App\Http\Service\Attendance\AttendanceShiftService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;

/**
 * 考勤统计跨天处理方法.
 */
trait AttendanceStatisticsCrossDayTrait
{
    /**
     * 根据打卡时间确定所属的统计记录日期.
     *
     * 优先级：今日上班窗 > 昨日下班窗 > 今日下班窗 > 明日上班窗.
     *
     * 设计说明：
     * - 今日上班窗优先：用户打卡最常见的情形是为今日班次上班
     * - 昨日下班窗次之：跨天班次下班打卡（如凌晨打卡下班）
     * - 今日下班窗兜底：用户打完上班卡后继续打下班卡
     * - 明日上斑窗最后：深夜为明日跨天班次提前打卡
     * - 匹配类型上下文感知：上班用 work_only，下班用 off_only，避免 grace 时间误匹配
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function determineClockStatisticsDate(int $uid, Carbon $clockTime, string $currentDate, string $tz): mixed
    {
        $yesterday = Carbon::parse($currentDate, $tz)->subDay()->toDateString();
        $tomorrow  = Carbon::parse($currentDate, $tz)->addDay()->toDateString();

        $todayRecord     = $this->getValidRecord($uid, $currentDate);
        $yesterdayRecord = $this->getValidRecord($uid, $yesterday);

        // 1. 今日上班窗（用户为今日班次打卡上班，最常见情形）
        if ($todayRecord) {
            $match = $this->matchClockTimeToShift($todayRecord, $clockTime, $currentDate, $tz, 'work_only');
            if ($match) {
                return $match;
            }
        }

        // 2. 昨日下班窗（跨天班次下班打卡）
        if ($yesterdayRecord) {
            $match = $this->matchClockTimeToShift($yesterdayRecord, $clockTime, $yesterday, $tz, 'off_only');
            if ($match) {
                return $match;
            }
        }

        // 3. 今日下班窗（今日上班卡已完成后打下班卡）
        if ($todayRecord) {
            $match = $this->matchClockTimeToShift($todayRecord, $clockTime, $currentDate, $tz, 'off_only');
            if ($match) {
                return $match;
            }
        }

        // 4. 明日上班窗（深夜为明日跨天班次提前打卡）
        $tomorrowRecord = $this->getValidRecord($uid, $tomorrow);
        if ($tomorrowRecord) {
            $match = $this->matchClockTimeToShift($tomorrowRecord, $clockTime, $tomorrow, $tz, 'work_only');
            if ($match) {
                return $match;
            }
        }

        return null;
    }

    /**
     * 查找与主记录时间窗重叠的另一天记录.
     *
     * 双向检测：
     * - 主记录为昨日 → 检查今日上班窗是否重叠
     * - 主记录为今日 → 检查昨日下班窗是否重叠
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function findOverlappingRecord(int $uid, Carbon $clockTime, string $currentDate, mixed $primaryRecord, string $tz): mixed
    {
        $primaryDate = isset($primaryRecord->created_at)
            ? Carbon::parse($primaryRecord->created_at, $tz)->toDateString()
            : '';
        $yesterday = Carbon::parse($currentDate, $tz)->subDay()->toDateString();

        if ($primaryDate === $yesterday) {
            // 主记录为昨日 → 检查今日上班窗是否重叠
            $todayRecord = $this->getValidRecord($uid, $currentDate);
            if (! $todayRecord) {
                return null;
            }
            $todayMatch = $this->matchClockTimeToShift($todayRecord, $clockTime, $currentDate, $tz, 'work_only');
            if (! $todayMatch) {
                return null;
            }
            return $todayRecord;
        }

        if ($primaryDate === $currentDate) {
            // 主记录为今日 → 检查昨日下班窗是否重叠
            $yesterdayRecord = $this->getValidRecord($uid, $yesterday);
            if (! $yesterdayRecord) {
                return null;
            }
            $yesterdayMatch = $this->matchClockTimeToShift($yesterdayRecord, $clockTime, $yesterday, $tz, 'off_only');
            if (! $yesterdayMatch) {
                return null;
            }
            return $yesterdayRecord;
        }

        return null;
    }

    /**
     * 获取有效的统计记录（排除休息和白名单）.
     */
    private function getValidRecord(int $uid, string $date): mixed
    {
        $record = $this->dao->getByUidDate($uid, $date);
        if (! $record || $record->shift_id <= 1 || empty($record->shift_data)) {
            return null;
        }
        return $record;
    }

    /**
     * 匹配打卡时间到班次时间范围.
     *
     * @param string $matchType 匹配类型: 'all'=上下班都检查, 'work_only'=仅检查上班, 'off_only'=仅检查下班
     * @param mixed $record
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function matchClockTimeToShift($record, Carbon $clockTime, string $date, string $tz, string $matchType = 'all'): mixed
    {
        $shiftData = $record->shift_data;
        $rules     = $shiftData['rules'] ?? [];

        if (empty($rules)) {
            return null;
        }

        // 遍历每个班次时段，检查打卡时间是否在范围内
        foreach ($rules as $ruleIndex => $rule) {
            // 弹性时间（2小时范围内视为有效）
            $graceSeconds = 7200; // 2小时

            // 检查是否在上班时间范围内
            if ($matchType !== 'off_only') {
                $workTime  = $this->getShiftTime($rule, $date, 'work', $tz);
                $workStart = (clone $workTime)->subSeconds($rule['early_card'] ?? 0);
                $workEnd   = (clone $workTime)->addSeconds($rule['late'] ?? 0)->addSeconds($graceSeconds);
                if ($clockTime->gte($workStart) && $clockTime->lte($workEnd)) {
                    return $record;
                }
            }

            // 检查是否在下班时间范围内
            if ($matchType !== 'work_only') {
                $offTime  = $this->getShiftTime($rule, $date, 'off', $tz);
                $offStart = (clone $offTime)->subSeconds($rule['early_lack_card'] ?? 0);
                $offEnd   = (clone $offTime)->addSeconds($rule['delay_card'] ?? 0)->addSeconds($graceSeconds);
                if ($clockTime->gte($offStart) && $clockTime->lte($offEnd)) {
                    return $record;
                }
            }
        }

        return null;
    }

    /**
     * 获取班次的具体时间（考虑跨天）.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function getShiftTime(array $rule, string $date, string $type, string $tz): Carbon
    {
        $timeType      = $type === 'work' ? 'work_hours' : 'off_hours';
        $dayAfterField = $type === 'work' ? 'first_day_after' : 'second_day_after';

        $time    = $rule[$timeType] ?? '00:00:00';
        $dateObj = Carbon::parse($date . ' ' . $time, $tz);

        // 检查是否跨天
        if ($rule[$dayAfterField] ?? false) {
            $dateObj->addDay();
        }

        return $dateObj;
    }

    /**
     * 跨天打卡检测：检查前一天是否有未完成的跨天班次.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function checkCrossDayAttendance(int $uid, Carbon $dateObj, string $date, string $tz): mixed
    {
        // 检查前一天是否有未完成的跨天班次
        $prevDate       = Carbon::parse($date, $tz)->subDay()->toDateString();
        $prevStatistics = $this->dao->getByUidDate($uid, $prevDate);
        if (! $prevStatistics) {
            return null;
        }

        if (
            // 白名单用户不检查跨天打卡
            app()->get(AttendanceGroupService::class)->isWhitelist($uid, $prevStatistics->group_id)
            || $prevStatistics->shift_id <= 1
            || ! $prevStatistics->shift_data
            || ($prevStatistics->shift_data['rules'][0]['second_day_after'] ?? 0) != 1
            || is_null($prevStatistics->one_shift_time)
            || ! is_null($prevStatistics->two_shift_time)
        ) {
            return null;
        }

        $rule       = $prevStatistics->shift_data['rules'][0];
        $offWorkObj = Carbon::parse($prevDate . ' ' . $rule['off_hours'] . ':00', $tz)->addDay();
        $earlyTime  = (clone $offWorkObj)->subSeconds($rule['early_lack_card']);
        $endTime    = (clone $offWorkObj)->addSeconds($rule['delay_card'] ?: 3600);

        if (! ($dateObj->gte($earlyTime) && $dateObj->lte($endTime))) {
            return null;
        }
        // 检查当天排班是否与当前打卡时间重叠（防误判保护）
        if ($this->isTodayShiftOverlapping($uid, $dateObj, $date, $tz)) {
            return null;
        }

        return $prevStatistics;
    }

    /**
     * 检查当天排班是否与当前打卡时间重叠（防误判保护）.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function isTodayShiftOverlapping(int $uid, Carbon $dateObj, string $date, string $tz): bool
    {
        [, $todayShiftId] = app()->get(AttendanceArrangeService::class)->getRecordByUid($uid, $date);
        if ($todayShiftId <= 1) {
            return false;
        }

        $todayShift = app()->get(AttendanceShiftService::class)->getArrangeShiftById($todayShiftId, $date);
        if (empty($todayShift['rules'][0]['work_hours'])) {
            return false;
        }

        $todayRule    = $todayShift['rules'][0];
        $todayWorkObj = Carbon::parse($date . ' ' . $todayRule['work_hours'] . ':00', $tz);
        if ($todayRule['first_day_after']) {
            $todayWorkObj->addDay();
        }
        // 计算当天最早工作时间
        $todayEarliestWork = (clone $todayWorkObj)->subSeconds($todayRule['early_card']);

        return $dateObj->gte($todayEarliestWork);
    }
}
