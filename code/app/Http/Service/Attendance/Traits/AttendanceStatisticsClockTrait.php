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

use App\Constants\AttendanceClockEnum;
use App\Http\Service\Attendance\AttendanceArrangeService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤统计打卡时间计算方法
 */
trait AttendanceStatisticsClockTrait
{
    /**
     * 上班打卡时间.
     */
    public function getWorkObj(array $rule, string $date, string $tz): Carbon
    {
        $dateObj = Carbon::parse($date . ' ' . $rule['work_hours'] . ':00', $tz);
        return $rule['first_day_after'] ? $dateObj->addDay() : $dateObj;
    }

    /**
     * 下班打卡时间.
     */
    public function getOffWorkObj(array $rule, string $date, string $tz): Carbon
    {
        $dateObj = Carbon::parse($date . ' ' . $rule['off_hours'] . ':00', $tz);
        return $rule['second_day_after'] ? $dateObj->addDay() : $dateObj;
    }

    /**
     * 获取打卡班次
     * @param mixed $statistics
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getClockNumber(Carbon $dateObj, $statistics, string $tz = '', bool $isWhitelist = false): array
    {
        $status = $number = 0;
        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        if ($isWhitelist) {
            $status = 1;
            if (! is_null($statistics->{$shifts[0] . '_shift_time'})) {
                $number = 1;
            }
            return [$status, $number];
        }

        if ($statistics->shift_id < 2) {
            if ($dateObj->lte(Carbon::parse($statistics->created_at, $tz)->endOfDay())) {
                $status = 1;
            }
            if (! is_null($statistics->{$shifts[0] . '_shift_time'})) {
                $number = 1;
            }
            return [$status, $number];
        }

        $tz   = $tz ?: config('app.timezone');
        $rule = $statistics->shift_data['rules'][0];
        $date = Carbon::parse($statistics->created_at, $tz)->toDateString();

        if (is_null($statistics->{$shifts[0] . '_shift_time'}) && $dateObj->gte($this->getWorkObj($rule, $date, $tz)->subSeconds($rule['early_card']))) {
            $status = 1;
            if ($statistics->{$shifts[0] . '_shift_status'} > 1) {
                $number = 1;
            }
        }

        if (! is_null($statistics->{$shifts[0] . '_shift_time'})) {
            $status = 1;
            $number = 1;
        }

        if (is_null($statistics->{$shifts[1] . '_shift_time'}) && $dateObj->gte($this->getOffWorkObj($rule, $date, $tz)->subSeconds($rule['early_lack_card']))) {
            $status = 1;
            $number = 1;
        }

        if ($statistics->shift_data['number'] == 2) {
            if (! is_null($statistics->{$shifts[1] . '_shift_time'})) {
                $status = 0;
                $number = 2;
            }

            $rule = $statistics->shift_data['rules'][1];
            if (is_null($statistics->{$shifts[2] . '_shift_time'}) && $dateObj->gte($this->getWorkObj($rule, $date, $tz)->subSeconds($rule['early_card']))) {
                $status = 1;
                if ($statistics->{$shifts[2] . '_shift_status'} > 1) {
                    $number = 3;
                }
            }

            if (! is_null($statistics->{$shifts[2] . '_shift_time'})) {
                $status = 0;
                $number = 3;
            }

            if (is_null($statistics->{$shifts[3] . '_shift_time'}) && $dateObj->gte($this->getOffWorkObj($rule, $date, $tz)->subSeconds($rule['early_lack_card']))) {
                $status = 1;
                $number = 3;
            }
        }

        return [$status, $number];
    }

    /**
     * 打卡结束时间.
     * @param mixed $info
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getClockEndTime(int $uid, $info, int $clockNumber, string $tz, bool $withTimestamp = true): mixed
    {
        $endTime        = 0;
        $currentDateObj = Carbon::parse($info->created_at, $tz);
        if ($info->shift_id < 2) {
            $endTime = $currentDateObj->endOfDay();
        } else {
            $date = $currentDateObj->toDateString();

            // 下班打卡截止时间
            $offWorkTimestamp = function (int $uid, array $rule, string $date, string $tz) {
                if ($rule['delay_card']) {
                    return $this->getOffWorkObj($rule, $date, $tz)->addSeconds($rule['delay_card']);
                }
                return $this->getLastShiftClockTimeObj($uid, $rule, $date, $tz);
            };

            $rule = $clockNumber > 1 ? $info->shift_data['rules'][1] : $info->shift_data['rules'][0];
            switch ($clockNumber) {
                case 1:
                    if ($info->shift_data['number'] == 1) {
                        $endTime = $offWorkTimestamp($uid, $rule, $date, $tz);
                    } else {
                        if ($rule['delay_card']) {
                            $endTime = $this->getOffWorkObj($rule, $date, $tz)->addSeconds($rule['delay_card']);
                        } else {
                            $rule    = $info->shift_data['rules'][1];
                            $endTime = $this->getWorkObj($rule, $date, $tz)->subSeconds($rule['early_card']);
                        }
                    }
                    break;
                case 3:
                    $endTime = $offWorkTimestamp($uid, $rule, $date, $tz);
                    break;
                default:
                    $endTime = $this->getWorkObj($rule, $date, $tz)->addSeconds($rule['late_lack_card']);
                    break;
            }
        }
        return $withTimestamp ? $endTime->timestamp : $endTime;
    }

    /**
     * 打卡结束时间.
     * @param mixed $info
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getClockTime(int $uid, $info, int $clockNumber, string $tz, ?Carbon $clockDateObj = null): mixed
    {
        $currentDateObj = Carbon::parse($info->created_at, $tz);
        if ($info->shift_id < 2) {
            return $currentDateObj->endOfDay()->timestamp;
        }

        $date = $currentDateObj->toDateString();
        $rule = $info->shift_data['rules'][$clockNumber > 1 ? 1 : 0];

        // 跨天打卡时使用传入的日期对象，否则使用当前时间
        $dateObj = $clockDateObj ?? now($tz);

        if (in_array($clockNumber, [0, 2])) {
            return $this->getOnWorkClockTime($rule, $date, $tz, $dateObj);
        }

        return $this->getOffWorkClockTime($uid, $info, $clockNumber, $rule, $date, $tz, $dateObj);
    }

    /**
     * 最后班次打卡截止时间.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getLastShiftClockTimeObj(int $uid, array $rule, string $date, string $tz = ''): Carbon
    {
        // 跨天班次下班在次日，需检查次日是否休息
        $checkDate = $rule['second_day_after'] ? Carbon::parse($date, $tz)->addDay()->toDateString() : $date;

        if (app()->get(AttendanceArrangeService::class)->dayIsRest($uid, $checkDate)) {
            $timestamp = $this->getOffWorkObj($rule, $date, $tz)->endOfDay();
        } else {
            // 下个班次提前打卡时间
            $dateObj   = Carbon::parse($date, $tz)->addDay();
            $rule      = $this->renewStatistics($uid, $dateObj)->shift_data['rules'][0];
            $timestamp = $this->getWorkObj($rule, $dateObj->toDateString(), $tz)->subSeconds($rule['early_card']);
        }
        return $timestamp;
    }

    /**
     * 核对当前打卡时间.
     * @param mixed $info
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function checkWorkTime(int $uid, Carbon $dateObj, $info, int $clockNumber, string $tz): void
    {
        if (! $info->shift_data['rules'][$clockNumber < 2 ? 0 : 1]['free_clock'] && $dateObj->timestamp > $this->getClockEndTime($uid, $info, $clockNumber, $tz)) {
            throw $this->exception('无法打卡');
        }
    }

    /**
     * 上班卡下一次状态变化时间.
     */
    private function getOnWorkClockTime(array $rule, string $date, string $tz, Carbon $dateObj): int
    {
        $workObj          = $this->getWorkObj($rule, $date, $tz);
        $earliestClockObj = $workObj->copy()->subSeconds((int) $rule['early_card']);
        $lateObj          = $workObj->copy()->addSeconds((int) $rule['late']);
        $extremeLateObj   = $workObj->copy()->addSeconds((int) $rule['extreme_late']);
        $lateLackObj      = $workObj->copy()->addSeconds((int) $rule['late_lack_card']);
        $offEarlyLackObj  = $this->getOffWorkObj($rule, $date, $tz)->subSeconds((int) $rule['early_lack_card']);

        return match (true) {
            $dateObj->lt($earliestClockObj) => $earliestClockObj->timestamp,
            $dateObj->lte($lateObj)         => $lateObj->timestamp,
            $dateObj->lte($extremeLateObj)  => $extremeLateObj->timestamp,
            $dateObj->lte($lateLackObj)     => $lateLackObj->timestamp,
            $dateObj->lt($offEarlyLackObj)  => $offEarlyLackObj->timestamp,
            default                         => $offEarlyLackObj->timestamp,
        };
    }

    /**
     * 下班卡下一次状态变化时间.
     *
     * @param mixed $info
     */
    private function getOffWorkClockTime(int $uid, $info, int $clockNumber, array $rule, string $date, string $tz, Carbon $dateObj): int
    {
        $offWorkObj      = $this->getOffWorkObj($rule, $date, $tz);
        $earlyLackObj    = $offWorkObj->copy()->subSeconds((int) $rule['early_lack_card']);
        $earlyLeaveObj   = $offWorkObj->copy()->subSeconds((int) $rule['early_leave']);
        $clockEndTimeObj = $this->getClockEndTime($uid, $info, $clockNumber, $tz, false);

        return match (true) {
            $dateObj->lt($earlyLackObj)  => $earlyLackObj->timestamp,
            $dateObj->lt($earlyLeaveObj) => $earlyLeaveObj->timestamp,
            $dateObj->lt($offWorkObj)    => $offWorkObj->timestamp,
            default                      => $clockEndTimeObj->timestamp,
        };
    }
}
