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

namespace App\Http\Service\Attendance;

use App\Constants\AttendanceClockEnum;
use App\Constants\AttendanceGroupEnum;
use Carbon\Carbon;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 计算考勤打卡状态
 */
class AttendanceStatusService extends BaseService
{
    /**
     * 定义时间区间类型.
     */
    public const PERIOD_FIRST_CHECK_IN = 'first_check_in';   // 第一次上班打卡区间

    public const PERIOD_FIRST_CHECK_OUT = 'first_check_out'; // 第一次下班打卡区间

    public const PERIOD_SECOND_CHECK_IN = 'second_check_in'; // 第二次上班打卡区间

    public const PERIOD_SECOND_CHECK_OUT = 'second_check_out'; // 第二次下班打卡区间

    public const PERIOD_WORK_OVERTIME = 'work_overtime'; // 加班打卡区间

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function checkUserClockStatus(int $userId, string $date, int $entId = 1)
    {
        $whiteUid = app()->get(AttendanceWhitelistService::class)->column(['type' => AttendanceGroupEnum::WHITELIST_MEMBER], 'uid');
        if (in_array($userId, $whiteUid)) {
            $shift = [];
        } else {
            $shift = app()->get(AttendanceArrangeService::class)->getUserShift($userId, $date);
            if (! $shift) {
                $isRest = app()->get(CalendarConfigService::class)->dayIsRest($date);
                $shift  = app()->get(AttendanceShiftService::class)->get(['types' => $isRest ? 1 : 2], ['*'], ['rules'])?->toArray();
            }
        }
        return $this->changeStatus($userId, $date, $shift);
    }

    /**
     * 处理打卡状态
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function changeStatus(int $userId, string $date, array $shift)
    {
        if (! $shift || ! $shift['rules']) {
            return app()->get(AttendanceStatisticsService::class)->delete(['uid' => $userId, 'date' => $date]);
        }
        $recodes = app()->get(AttendanceClockService::class)->getUserClock($userId, [
            Carbon::parse($date)->startOfDay()->toDateTimeString(),
            Carbon::parse($date)->addDay()->endOfDay()->toDateTimeString(),
        ]);
        return $this->calculatePeriodResult($userId, $date, $recodes, $shift);
    }

    /**
     * 计算实际出勤工时（小时）- 适配打卡对逻辑.
     * @param array $shiftData 班次/打卡数据数组
     * @return float 总出勤工时（保留2位小数）
     */
    public function calculateActualWorkHours(array $shiftData): float
    {
        // 第一组：one_shift_time（上班） + two_shift_time（下班）
        $firstGroupHours = $this->calculateSinglePunchPairHours(
            $shiftData['one_shift_time'] ?? null,
            $shiftData['two_shift_time'] ?? null,
        );

        // 第二组：three_shift_time（上班） + four_shift_time（下班）
        $secondGroupHours = $this->calculateSinglePunchPairHours(
            $shiftData['three_shift_time'] ?? null,
            $shiftData['four_shift_time'] ?? null
        );

        // 总工时 = 两组之和
        return round($firstGroupHours + $secondGroupHours, 2);
    }

    /**
     * 获取班次的时间区间.
     */
    private function getTimePeriods(string $date, array $shift): array
    {
        $periods = [];
        foreach ($shift['rules'] as $rule) {
            $lateLackCard  = (int) ($rule['late_lack_card'] ?? 0);
            $earlyLackCard = (int) ($rule['early_lack_card'] ?? 0);
            $earlyCard     = (int) ($rule['early_card'] ?? 0);
            $delayCard     = (int) ($rule['delay_card'] ?? 0);
            if ($rule['number'] <= 1) {
                // 第一次上班时间区间
                $firstStartTime = $this->combineDateTime($date, $rule['work_hours']);
                if ($rule['first_day_after'] == 1) {
                    $firstStartTime->addDay();
                }
                $periods[self::PERIOD_FIRST_CHECK_IN] = [
                    'start'       => $firstStartTime->copy()->subSeconds($earlyCard),
                    'advance'     => $earlyCard / 60,
                    'late'        => $rule['late'] / 60,
                    'severe_late' => $rule['extreme_late'] / 60,
                    'late_lack'   => $lateLackCard / 60,
                    'standard'    => $firstStartTime,
                    'end'         => $firstStartTime->copy()->addSeconds($lateLackCard),
                ];

                // 第一次下班时间区间
                $firstEndTime = $this->combineDateTime($date, $rule['off_hours']);
                if ($rule['second_day_after'] == 1) {
                    $firstEndTime->addDay();
                }
                $firstCheckOutStart = $firstEndTime->copy()->subSeconds($earlyLackCard);
                if ($firstCheckOutStart->lt($periods[self::PERIOD_FIRST_CHECK_IN]['end'])) {
                    $firstCheckOutStart = $periods[self::PERIOD_FIRST_CHECK_IN]['end']->copy();
                }
                $periods[self::PERIOD_FIRST_CHECK_OUT] = [
                    'start'           => $firstCheckOutStart,
                    'early_leave'     => $rule['early_leave'] / 60,
                    'early_lack_card' => $earlyLackCard / 60,
                    'standard'        => $firstEndTime,
                    'end'             => $firstEndTime->copy()->addSeconds($delayCard),
                ];
            } else {
                // 第二次上班时间区间
                $secondStartTime = $this->combineDateTime($date, $rule['work_hours']);
                if ($rule['first_day_after'] == 1) {
                    $secondStartTime->addDay();
                }
                $periods[self::PERIOD_SECOND_CHECK_IN] = [
                    'start'       => $secondStartTime->copy()->subSeconds($earlyCard),
                    'advance'     => $earlyCard / 60,
                    'late'        => $rule['late'] / 60,
                    'severe_late' => $rule['extreme_late'] / 60,
                    'late_lack'   => $lateLackCard / 60,
                    'standard'    => $secondStartTime,
                    'end'         => $secondStartTime->copy()->addSeconds($lateLackCard),
                ];

                // 第二次下班时间区间
                $secondEndTime = $this->combineDateTime($date, $rule['off_hours']);
                if ($rule['second_day_after'] == 1) {
                    $secondEndTime->addDay();
                }
                $secondCheckOutStart = $secondEndTime->copy()->subSeconds($earlyLackCard);
                if ($secondCheckOutStart->lt($periods[self::PERIOD_SECOND_CHECK_IN]['end'])) {
                    $secondCheckOutStart = $periods[self::PERIOD_SECOND_CHECK_IN]['end']->copy();
                }
                $periods[self::PERIOD_SECOND_CHECK_OUT] = [
                    'start'           => $secondCheckOutStart,
                    'early_leave'     => $rule['early_leave'] / 60,
                    'early_lack_card' => $earlyLackCard / 60,
                    'standard'        => $secondEndTime,
                    'end'             => $secondEndTime->copy()->addSeconds($delayCard),
                ];
            }
        }
        $periods[self::PERIOD_WORK_OVERTIME] = [
            'start' => isset($periods[self::PERIOD_SECOND_CHECK_OUT]) ? $periods[self::PERIOD_SECOND_CHECK_OUT]['standard']->copy()->addSeconds($shift['overtime']) : $periods[self::PERIOD_FIRST_CHECK_OUT]['standard']->copy()->addSeconds($shift['overtime']),
            'end'   => Carbon::parse($date)->endOfDay(),
        ];
        return $periods;
    }

    /**
     * 合并日期和时间.
     * @param mixed $date
     * @param mixed $timeString
     */
    private function combineDateTime($date, $timeString): Carbon
    {
        return Carbon::parse($date . ' ' . $timeString);
    }

    /**
     * 是否落在统计归属日之后.
     */
    private function isAfterStatisticsDay(Carbon $time, string $date): int
    {
        return $time->gt(Carbon::parse($date)->endOfDay()) ? 1 : 0;
    }

    /**
     * 判断打卡记录属于哪个时间区间.
     */
    private function getPeriodType(Carbon $checkTime, array $periods): ?string
    {
        foreach ($periods as $type => $period) {
            if ($checkTime->between($period['start'], $period['end'])) {
                return $type;
            }
        }
        return null;
    }

    /**
     * 计算考勤结果.
     * @param mixed $attendanceRecords
     */
    private function calculatePeriodResult(int $userId, string $date, $attendanceRecords, array $shift)
    {
        $periods = $this->getTimePeriods($date, $shift);
        $remarks = '';

        // 记录各时段的有效打卡记录
        $periodRecords = [
            self::PERIOD_FIRST_CHECK_IN   => null,
            self::PERIOD_FIRST_CHECK_OUT  => null,
            self::PERIOD_SECOND_CHECK_IN  => null,
            self::PERIOD_SECOND_CHECK_OUT => null,
            self::PERIOD_WORK_OVERTIME    => null,
        ];

        // 处理每条打卡记录
        foreach ($attendanceRecords as $record) {
            $recordTime = Carbon::parse($record['created_at']);
            $periodType = $this->getPeriodType($recordTime, $periods);
            if ($periodType === null) {
                $remarks .= "打卡时间 {$record['created_at']} 不在任何有效时间区间内; ";
                continue;
            }

            // 如果该时段已有记录，选择最优的一条
            if ($periodRecords[$periodType] !== null) {
                $existingTime = Carbon::parse($periodRecords[$periodType]['created_at']);

                if ($periodType === self::PERIOD_FIRST_CHECK_IN || $periodType === self::PERIOD_SECOND_CHECK_IN) {
                    // 上班取最早
                    if ($recordTime->lt($existingTime)) {
                        $periodRecords[$periodType] = $record;
                    }
                } else {
                    // 下班取最晚
                    if ($recordTime->gt($existingTime)) {
                        $periodRecords[$periodType] = $record;
                    }
                }
            } else {
                $periodRecords[$periodType] = $record;
            }
            $periodType = $this->getPeriodType($recordTime, [self::PERIOD_WORK_OVERTIME => $periods[self::PERIOD_WORK_OVERTIME]]);
            if ($periodType === null) {
                $remarks .= "打卡时间 {$record['created_at']} 不在任何有效时间区间内; ";
                continue;
            }

            // 如果该时段已有记录，选择最优的一条
            if ($periodRecords[$periodType] !== null) {
                $existingTime = Carbon::parse($periodRecords[$periodType]['created_at']);

                if ($periodType === self::PERIOD_FIRST_CHECK_IN || $periodType === self::PERIOD_SECOND_CHECK_IN) {
                    // 上班取最早
                    if ($recordTime->lt($existingTime)) {
                        $periodRecords[$periodType] = $record;
                    }
                } else {
                    // 下班取最晚
                    if ($recordTime->gt($existingTime)) {
                        $periodRecords[$periodType] = $record;
                    }
                }
            } else {
                $periodRecords[$periodType] = $record;
            }
        }
        // 处理第一次上班
        if ($periodRecords[self::PERIOD_FIRST_CHECK_IN] === null) {
            $remarks .= '上午上班缺卡; ';
            $periods[self::PERIOD_FIRST_CHECK_IN]['end']->lt(now()) && $one_shift_status = AttendanceClockEnum::LATE_LACK_CARD;
        } else {
            $checkInTime  = Carbon::parse($periodRecords[self::PERIOD_FIRST_CHECK_IN]['created_at']);
            $standardTime = $periods[self::PERIOD_FIRST_CHECK_IN]['standard'];
            // 上班时间
            $one_shift_time            = $checkInTime->toDateTimeString();
            $one_shift_location_status = $periodRecords[self::PERIOD_FIRST_CHECK_IN]['is_external'] ? AttendanceClockEnum::OFFICE_OUTSIDE : AttendanceClockEnum::OFFICE_STAFF;
            $one_shift_is_after        = $this->isAfterStatisticsDay($checkInTime, $date);

            $firstLateMins = $standardTime->diffInMinutes($checkInTime, false);
            if ($firstLateMins > 0) {
                if ($firstLateMins >= $periods[self::PERIOD_FIRST_CHECK_IN]['severe_late']) {
                    $remarks .= "上午上班晚到{$firstLateMins}分钟，严重迟到; ";
                    $one_shift_status = AttendanceClockEnum::EXTREME_LATE;
                } elseif ($firstLateMins >= $periods[self::PERIOD_FIRST_CHECK_IN]['late']) {
                    $remarks .= "上午上班晚到{$firstLateMins}分钟，迟到; ";
                    $one_shift_status = AttendanceClockEnum::LATE;
                } else {
                    $one_shift_status = AttendanceClockEnum::NORMAL;
                }
            } elseif (abs($firstLateMins) > $periods[self::PERIOD_FIRST_CHECK_IN]['advance']) {
                $one_shift_status = AttendanceClockEnum::LATE_LACK_CARD;
            } else {
                $one_shift_status = AttendanceClockEnum::NORMAL;
            }
        }

        // 处理第一次下班
        if ($periodRecords[self::PERIOD_FIRST_CHECK_OUT] === null) {
            $remarks .= '上午下班缺卡; ';
            $periods[self::PERIOD_FIRST_CHECK_OUT]['end']->lt(now()) && $two_shift_status = AttendanceClockEnum::LATE_LACK_CARD;
        } else {
            $checkOutTime = Carbon::parse($periodRecords[self::PERIOD_FIRST_CHECK_OUT]['created_at']);
            $standardTime = $periods[self::PERIOD_FIRST_CHECK_OUT]['standard'];
            // 下班时间
            $two_shift_time            = $checkOutTime->toDateTimeString();
            $two_shift_location_status = $periodRecords[self::PERIOD_FIRST_CHECK_OUT]['is_external'] ? AttendanceClockEnum::OFFICE_OUTSIDE : AttendanceClockEnum::OFFICE_STAFF;
            $two_shift_is_after        = $this->isAfterStatisticsDay($checkOutTime, $date);

            $firstEarlyMins = $checkOutTime->diffInMinutes($standardTime, false);
            if ($firstEarlyMins > 0) {
                if ($firstEarlyMins >= $periods[self::PERIOD_FIRST_CHECK_OUT]['early_lack_card']) {
                    $remarks .= "上午下班早退{$firstEarlyMins}分钟; ";
                    $two_shift_status = AttendanceClockEnum::EARLY_LACK_CARD;
                } elseif ($firstEarlyMins >= $periods[self::PERIOD_FIRST_CHECK_OUT]['early_leave']) {
                    $remarks .= "上午下班早退{$firstEarlyMins}分钟; ";
                    $two_shift_status = AttendanceClockEnum::LEAVE_EARLY;
                } else {
                    $two_shift_status = AttendanceClockEnum::NORMAL;
                }
            } else {
                $two_shift_status = AttendanceClockEnum::NORMAL;
            }
        }
        if ($shift['number'] > 1) {
            // 处理第二次上班
            if ($periodRecords[self::PERIOD_SECOND_CHECK_IN] === null) {
                $remarks .= '下午上班缺卡; ';
                $periods[self::PERIOD_SECOND_CHECK_IN]['end']->lt(now()) && $three_shift_status = AttendanceClockEnum::LATE_LACK_CARD;
            } else {
                $checkInTime  = Carbon::parse($periodRecords[self::PERIOD_SECOND_CHECK_IN]['created_at']);
                $standardTime = $periods[self::PERIOD_SECOND_CHECK_IN]['standard'];
                // 上班时间
                $three_shift_time            = $checkInTime->toDateTimeString();
                $three_shift_location_status = $periodRecords[self::PERIOD_SECOND_CHECK_IN]['is_external'] ? AttendanceClockEnum::OFFICE_OUTSIDE : AttendanceClockEnum::OFFICE_STAFF;
                $three_shift_is_after        = $this->isAfterStatisticsDay($checkInTime, $date);

                $secondLateMins = $standardTime->diffInMinutes($checkInTime, false);
                if ($secondLateMins > 0) {
                    if ($secondLateMins >= $periods[self::PERIOD_SECOND_CHECK_IN]['severe_late']) {
                        $remarks .= "下午上班晚到{$secondLateMins}分钟，严重迟到; ";
                        $three_shift_status = AttendanceClockEnum::EXTREME_LATE;
                    } elseif ($secondLateMins >= $periods[self::PERIOD_SECOND_CHECK_IN]['late']) {
                        $remarks .= "下午上班晚到{$secondLateMins}分钟，迟到; ";
                        $three_shift_status = AttendanceClockEnum::LATE;
                    } else {
                        $three_shift_status = AttendanceClockEnum::NORMAL;
                    }
                } elseif (abs($secondLateMins) > $periods[self::PERIOD_SECOND_CHECK_IN]['advance']) {
                    $three_shift_status = AttendanceClockEnum::LATE_LACK_CARD;
                } else {
                    $three_shift_status = AttendanceClockEnum::NORMAL;
                }
            }

            // 处理第二次下班
            if ($periodRecords[self::PERIOD_SECOND_CHECK_OUT] === null) {
                $remarks .= '下午下班缺卡; ';
                $periods[self::PERIOD_SECOND_CHECK_OUT]['end']->lt(now()) && $four_shift_status = AttendanceClockEnum::LATE_LACK_CARD;
            } else {
                $checkOutTime = Carbon::parse($periodRecords[self::PERIOD_SECOND_CHECK_OUT]['created_at']);
                $standardTime = $periods[self::PERIOD_SECOND_CHECK_OUT]['standard'];
                // 下班时间
                $four_shift_time            = $checkOutTime->toDateTimeString();
                $four_shift_location_status = $periodRecords[self::PERIOD_SECOND_CHECK_OUT]['is_external'] ? AttendanceClockEnum::OFFICE_OUTSIDE : AttendanceClockEnum::OFFICE_STAFF;
                $four_shift_is_after        = $this->isAfterStatisticsDay($checkOutTime, $date);

                $secondEarlyMins = $checkOutTime->diffInMinutes($standardTime, false);
                if ($secondEarlyMins > 0) {
                    if ($secondEarlyMins >= $periods[self::PERIOD_SECOND_CHECK_OUT]['early_lack_card']) {
                        $remarks .= "下午下班早退{$secondEarlyMins}分钟; ";
                        $four_shift_status = AttendanceClockEnum::EARLY_LACK_CARD;
                    } elseif ($secondEarlyMins >= $periods[self::PERIOD_SECOND_CHECK_OUT]['early_leave']) {
                        $remarks .= "下午下班早退{$secondEarlyMins}分钟; ";
                        $four_shift_status = AttendanceClockEnum::LEAVE_EARLY;
                    } else {
                        $four_shift_status = AttendanceClockEnum::NORMAL;
                    }
                } else {
                    $four_shift_status = AttendanceClockEnum::NORMAL;
                }
            }
        }

        // 处理加班
        if ($periodRecords[self::PERIOD_WORK_OVERTIME]) {
            $checkOutTime        = Carbon::parse($periodRecords[self::PERIOD_WORK_OVERTIME]['created_at']);
            $workOvertimeSeconds = $periods[self::PERIOD_WORK_OVERTIME]['start']->diffInSeconds($checkOutTime, false);
        }
        $data = [
            'one_shift_time'              => $one_shift_time ?? null,
            'one_shift_is_after'          => $one_shift_is_after ?? '',
            'one_shift_status'            => $one_shift_status ?? 0,
            'one_shift_location_status'   => $one_shift_location_status ?? '',
            'two_shift_time'              => $two_shift_time ?? null,
            'two_shift_is_after'          => $two_shift_is_after ?? '',
            'two_shift_status'            => $two_shift_status ?? 0,
            'two_shift_location_status'   => $two_shift_location_status ?? '',
            'three_shift_time'            => $three_shift_time ?? null,
            'three_shift_is_after'        => $three_shift_is_after ?? '',
            'three_shift_status'          => $three_shift_status ?? 0,
            'three_shift_location_status' => $three_shift_location_status ?? '',
            'four_shift_time'             => $four_shift_time ?? null,
            'four_shift_is_after'         => $four_shift_is_after ?? '',
            'four_shift_status'           => $four_shift_status ?? 0,
            'four_shift_location_status'  => $four_shift_location_status ?? '',
            //            'work_overtime'               => $workOvertimeSeconds ?? 0,
        ];
        $data['actual_work_hours'] = $this->calculateActualWorkHours($data);
        return app()->get(AttendanceStatisticsService::class)->update(['uid' => $userId, 'date' => $date], $data);
    }

    /**
     * 计算单组打卡对的时长
     */
    private function calculateSinglePunchPairHours(?string $startTime, ?string $endTime): float
    {
        // 缺少任意打卡时间 → 时长为0
        if (empty($startTime) || empty($endTime)) {
            return 0.0;
        }
        $second = Carbon::make($startTime)->diffInSeconds(Carbon::make($endTime));
        return $second / 3600;
    }
}
