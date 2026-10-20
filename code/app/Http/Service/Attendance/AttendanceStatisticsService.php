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

namespace App\Http\Service\Attendance;

use App\Constants\ApproveEnum;
use App\Constants\AttendanceClockEnum;
use App\Http\Dao\Attendance\AttendanceStatisticsDao;
use App\Http\Service\Approve\ApproveService;
use App\Http\Service\Attendance\Traits\AttendanceStatisticsAbnormalTrait;
use App\Http\Service\Attendance\Traits\AttendanceStatisticsClockTrait;
use App\Http\Service\Attendance\Traits\AttendanceStatisticsCoreTrait;
use App\Http\Service\Attendance\Traits\AttendanceStatisticsCrossDayTrait;
use App\Http\Service\Attendance\Traits\AttendanceStatisticsPersonTrait;
use App\Http\Service\Attendance\Traits\AttendanceStatisticsReportTrait;
use App\Http\Service\Attendance\Traits\AttendanceStatisticsTeamTrait;
use App\Http\Service\Attendance\Traits\AttendanceStatisticsUpdateTrait;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤统计
 * Class AttendanceStatisticsService.
 * @mixin BaseModel
 */
class AttendanceStatisticsService extends BaseService
{
    use AttendanceStatisticsCoreTrait;
    use AttendanceStatisticsClockTrait;
    use AttendanceStatisticsUpdateTrait;
    use AttendanceStatisticsTeamTrait;
    use AttendanceStatisticsPersonTrait;
    use AttendanceStatisticsAbnormalTrait;
    use AttendanceStatisticsCrossDayTrait;
    use AttendanceStatisticsReportTrait;

    public function __construct(AttendanceStatisticsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 考勤数据.
     * @param mixed $info
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getStatisticsList($info, int $clockNumber, string $tz, bool $isReal = false): array
    {
        $list      = $this->getClockRecord($info, $clockNumber, $tz, false, $isReal);
        $recordIds = array_filter(array_column($list, 'record_id'));
        if ($recordIds) {
            $records = app()->get(AttendanceClockService::class)->column(['id' => $recordIds], ['lat', 'lng', 'remark', 'image', 'address'], 'id');
        }

        foreach ($list as &$item) {
            if (isset($records[$item['record_id']])) {
                $item = array_merge($item, $records[$item['record_id']]);
            }
            unset($item['record_id']);
        }
        return $list;
    }

    /**
     * 获取打卡详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getStatisticsDetail(string $uuid, string $date, int $userId): array
    {
        $tz = config('app.timezone');
        if (! $date) {
            throw $this->exception('时间异常');
        }

        $seconds = 0;
        $dateObj = Carbon::parse($date, $tz);
        $date    = $dateObj->toDateString();
        $info    = $this->getUserRecordByDate(app()->get(AttendanceClockService::class)->getStatisticsUserId($uuid, $userId), $date);
        $list    = $this->getStatisticsList($info, $info->shift_id > 1 ? ($info->shift_data['number'] * 2 - 1) : 1, $tz, true);

        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        if ($info->shift_id > 1) {
            $normal = true;
            for ($i = 0; $i < $info->shift_data['number'] * 2; ++$i) {
                if ($info->{$shifts[$i] . '_shift_status'} > 1 || empty($info->{$shifts[1] . '_shift_time'})) {
                    $normal = false;
                    break;
                }
            }

            if ($normal) {
                for ($i = 0; $i < $info->shift_data['number'] * 2; $i += 2) {
                    $seconds += Carbon::parse($info->{$shifts[$i] . '_shift_time'}, $tz)
                        ->diffInSeconds(Carbon::parse($info->{$shifts[$i + 1] . '_shift_time'}, $tz), false);
                }
            }

            if ($info->shift_data['number'] == 1 && $info->shift_data['rest_time']) {
                $restStartObj = Carbon::parse($date . ' ' . $info->shift_data['rest_start'] . ':00', $tz);
                $restEndObj   = Carbon::parse($date . ' ' . $info->shift_data['rest_end'] . ':00', $tz);
                $seconds -= ($info->shift_data['rest_start_after'] ? $restStartObj->addDay() : $restStartObj)
                    ->diffInHours($info->shift_data['rest_end_after'] ? $restEndObj->addDay() : $restEndObj, false);
            }
        } else {
            if (count($list) == 2) {
                $firstTime  = $info->{$shifts[0] . '_shift_time'};
                $secondTime = $info->{$shifts[1] . '_shift_time'};
                if ($firstTime && $secondTime) {
                    $seconds = Carbon::parse($firstTime, $tz)->diffInSeconds(Carbon::parse($secondTime, $tz), false);
                }
            }
        }

        return ['list' => $list, 'work_hours' => max($seconds, 0)];
    }

    /**
     * 考勤班次
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getShiftsByDate(string $date): array
    {
        return $this->dao->search(['date' => $date, 'shift_id_gt' => 1])->groupBy('shift_id')->select(['uid', 'shift_id', 'shift_data'])->get()->toArray();
    }

    /**
     * 获取旷工缺卡人/次数.
     * @return int[]
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getLackCardAndAbsenteeismPeopleNum(array|int $uid, string $date, string $timeType = 'date'): array
    {
        $shifts  = AttendanceClockEnum::SHIFT_CLASS;
        $records = $this->dao->select([$timeType => $date, 'uid' => $uid], [
            'id', 'uid', 'shift_id', 'shift_data',
            'one_shift_status', 'two_shift_status', 'three_shift_status', 'four_shift_status',
        ]);
        $leaveDays = app()->get(AttendanceStatisticsLeaveService::class)->getLeaveDaysByStatistics($uid, $date, $timeType);

        $lackCardNum = $absenteeismNum = [];
        foreach ($records as $record) {
            if ($record->shift_id < 2) {
                continue;
            }
            $lackNum  = $absentNum = 0;
            $shiftNum = $record->shift_data['number'] * 2;
            $leaveDay = (string) ($leaveDays[$record->id] ?? '0.00');
            for ($i = 0; $i < $shiftNum; ++$i) {
                $shiftStatus = $record->{$shifts[$i] . '_shift_status'};
                if ($shiftStatus == AttendanceClockEnum::LACK_CARD) {
                    ++$absentNum;
                }

                if (in_array($shiftStatus, AttendanceClockEnum::ALL_LACK_CARD)) {
                    ++$lackNum;
                }
            }

            if (bccomp($leaveDay, '1', 2) >= 0) {
                continue;
            }

            if ($absentNum == $shiftNum && bccomp($leaveDay, '0', 2) <= 0) {
                if (! isset($absenteeismNum[$record->uid])) {
                    $absenteeismNum[$record->uid] = 1;
                } else {
                    ++$absenteeismNum[$record->uid];
                }
            } elseif ($lackNum > 0) {
                if (! isset($lackCardNum[$record->uid])) {
                    $lackCardNum[$record->uid] = $lackNum;
                } else {
                    $lackCardNum[$record->uid] += $lackNum;
                }
            }
        }
        return [count($absenteeismNum), count($lackCardNum), array_sum($absenteeismNum), array_sum($lackCardNum)];
    }

    /**
     * 获取加班人/次数.
     */
    public function getOverTimeStatistics(array|int $members, string $date, string $timeType = 'date'): array
    {
        $overTimeNum = [];
        foreach ($this->select(['uid' => $members, $timeType => $date]) as $record) {
            if (bccomp($record->actual_work_hours, '0.0')
                && bccomp(bcmul(bcsub($record->actual_work_hours, $record->required_work_hours), '3600'), (string) $record->shift_data['overtime'])) {
                if (! isset($overTimeNum[$record->uid])) {
                    $overTimeNum[$record->uid] = 1;
                } else {
                    ++$overTimeNum[$record->uid];
                }
            }
        }
        return [count($overTimeNum), array_sum($overTimeNum)];
    }

    /**
     * 清除白名单考勤数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function clearWhitelist(array $members)
    {
        return $this->dao->delete(['uid' => $members, 'gt_date' => now(config('app.timezone'))->subDay()->toDateString()]);
    }

    /**
     * 审批审批类型.
     * @throws BindingResolutionException
     */
    public function getApproveList(string $uuid, string $date): array
    {
        $approveService = app()->get(ApproveService::class);
        return $approveService->dao->getList([
            'types' => [
                ApproveEnum::PERSONNEL_HOLIDAY,
                ApproveEnum::PERSONNEL_OUT,
                ApproveEnum::PERSONNEL_TRIP,
                ApproveEnum::PERSONNEL_SIGN,
                ApproveEnum::PERSONNEL_OVERTIME,
            ],
        ], ['*'], 0, 0, 'id');
    }

    /**
     * 获取打卡状态
     * @param mixed $info
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getClockStatus(int $clockNumber, Carbon $dateObj, $info): int
    {
        if ($info->shift_id < 2) {
            return 1;
        }

        $tz     = config('app.timezone');
        $date   = Carbon::parse($info->created_at, $tz)->toDateString();
        $status = AttendanceClockEnum::NORMAL;
        $rule   = $info->shift_data['rules'][$clockNumber < 2 ? 0 : 1];
        if (in_array($clockNumber, [0, 2])) {
            $workObj = $this->getWorkObj($rule, $date, $tz);
            $seconds = $workObj->diffInSeconds($dateObj);
            if ($dateObj->gt($workObj)) {
                $lateSeconds = (int) ($rule['late'] ?? 0);
                $status      = match (true) {
                    $seconds > (int) $rule['late_lack_card'] => AttendanceClockEnum::LATE_LACK_CARD,
                    $seconds > (int) $rule['extreme_late']   => AttendanceClockEnum::EXTREME_LATE,
                    $seconds > $lateSeconds                  => AttendanceClockEnum::LATE,
                    default                                  => AttendanceClockEnum::NORMAL
                };
            } elseif ($dateObj->lt($workObj)) {
                $status = match (true) {
                    $seconds > (int) $rule['early_card'] => AttendanceClockEnum::LACK_CARD,
                    default                              => AttendanceClockEnum::NORMAL
                };
            }
        } else {
            $offWorkObj = $this->getOffWorkObj($rule, $date, $tz);
            if ($dateObj->lt($offWorkObj)) {
                $seconds           = $offWorkObj->diffInSeconds($dateObj);
                $earlyLeaveSeconds = (int) ($rule['early_leave'] ?? 0);
                $status            = match (true) {
                    $seconds > (int) $rule['early_lack_card'] => AttendanceClockEnum::EARLY_LACK_CARD,
                    $seconds > $earlyLeaveSeconds             => AttendanceClockEnum::LEAVE_EARLY,
                    default                                   => AttendanceClockEnum::NORMAL
                };
            }
        }

        if ($dateObj->timestamp > $this->getClockEndTime((int) $info->uid, $info, $clockNumber, $tz)) {
            $status = AttendanceClockEnum::LACK_CARD;
        }
        return $status;
    }

    /**
     * 获取审批免打卡
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function calcAssociatedApprove(mixed $info, string $dateString, int $clockNumber, array $rule, string $tz): array
    {
        $freeClock      = false;
        $locationStatus = 0;
        $recordService  = app()->get(AttendanceApplyRecordService::class);
        $workObj        = $this->{in_array($clockNumber, [0, 2]) ? 'getWorkObj' : 'getOffWorkObj'}($rule, $dateString, $tz);
        $recordList     = $recordService->select(['uid' => $info->uid, 'compare_time' => $workObj->toDateTimeString()], ['apply_type', 'start_time', 'end_time']);
        foreach ($recordList as $item) {
            if ($workObj->gte($item->start_time) && $workObj->lt($item->end_time)) {
                // out and trip
                if (in_array($item->apply_type, [ApproveEnum::PERSONNEL_OUT, ApproveEnum::PERSONNEL_TRIP])) {
                    $locationStatus = 1;
                }

                // holiday
                if ($item->apply_type == ApproveEnum::PERSONNEL_HOLIDAY) {
                    $freeClock      = true;
                    $locationStatus = 1;
                }
            }
        }

        return [$freeClock, $locationStatus];
    }

    /**
     * 删除重复数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteRepeat()
    {
        $repeatId = $this->dao->getRepeatId();
        if ($repeatId) {
            $this->dao->delete(['id' => $repeatId]);
        }
    }

    /**
     * 计算考勤请假工时.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function calcLeaveDurationByTime(int $uid, int $applyRecordId, int $holidayTypeId, string $startTime, string $endTime): void
    {
        $tz       = config('app.timezone');
        $startObj = Carbon::parse($startTime, $tz);
        $endObj   = Carbon::parse($endTime, $tz);

        $leaveService = app()->get(AttendanceStatisticsLeaveService::class);
        $leaveService->dao->delete(['apply_record_id' => $applyRecordId]);

        $list = $this->dao->select(['uid' => $uid, 'holiday_time' => $startObj->eq($endObj) ? $startObj->toDateString() : [$startObj->toDateString(), $endObj->toDateString()]]);

        foreach ($list as $item) {
            if ($item->shift_id < 2) {
                continue;
            }

            $duration   = '0.0';
            $restEndObj = $restStartObj = null;

            $dateString = Carbon::parse($item->created_at, $tz)->toDateString();

            // rest time
            if ($item->shift_data['number'] == 1 && $item->shift_data['rest_time']) {
                $restStartObj = Carbon::parse($dateString . ' ' . $item->shift_data['rest_start'] . ':00', $tz);
                $item->shift_data['rest_start_after'] && $restStartObj->addDay();

                $restEndObj = Carbon::parse($dateString . ' ' . $item->shift_data['rest_end'] . ':00', $tz);
                $item->shift_data['rest_end_after'] && $restEndObj->addDay();
            }

            for ($i = 0; $i <= $item->shift_data['number'] - 1; ++$i) {
                $rule       = $item->shift_data['rules'][$i];
                $workObj    = $this->getWorkObj($rule, $dateString, $tz);
                $offWorkObj = $this->getOffWorkObj($rule, $dateString, $tz);
                if ($endObj->lt($workObj) || $startObj->gt($offWorkObj)) {
                    continue;
                }

                // skip working time
                $skipWork = false;

                // skip off work time
                $skipOffWork = false;

                // comp rest time
                if ($restStartObj && $restEndObj) {
                    if ($startObj->gt($restStartObj)) {
                        $skipWork = true;
                    }

                    if ($endObj->lt($restEndObj)) {
                        $skipOffWork = true;
                    }

                    if (! $skipWork) {
                        $tmpStartObj = $workObj->lt($startObj) ? $startObj : $workObj;
                        $tmpEndObj   = $endObj->gt($restStartObj) ? $restStartObj : $endObj;
                        $duration    = $this->calcLeaveDuration($duration, $tmpStartObj, $tmpEndObj);
                    }

                    if (! $skipOffWork) {
                        if ($skipWork) {
                            $tmpStartObj = $restEndObj->lt($startObj) ? $startObj : $startObj->addSeconds($restEndObj->diffInSeconds($startObj));
                        } else {
                            $tmpStartObj = $restEndObj;
                        }
                        $tmpEndObj = $endObj->gt($offWorkObj) ? $offWorkObj : $endObj;
                        $duration  = $this->calcLeaveDuration($duration, $tmpStartObj, $tmpEndObj);
                    }
                } else {
                    $tmpStartObj = $startObj->gt($workObj) ? $startObj : $workObj;
                    $tmpEndObj   = $endObj->gt($offWorkObj) ? $offWorkObj : $endObj;
                    $duration    = $this->calcLeaveDuration($duration, $tmpStartObj, $tmpEndObj);
                }
            }

            // save leave duration
            if (bccomp($duration, '0', 2) > 0) {
                $leaveService->createLeaveRecord($item->id, $item->uid, $holidayTypeId, $duration, $applyRecordId, $item->created_at);
            }
        }
    }

    /**
     * 获取旷工缺卡异常数量.
     * @return int[]
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    private function getLackCardAndAbsenteeismNum(array|int $uid, string $date): array
    {
        $lackCard = $absenteeism = $locationAbnormal = 0;
        $shifts   = AttendanceClockEnum::SHIFT_CLASS;
        $records  = $this->dao->select(['month' => $date, 'uid' => $uid], [
            'id', 'uid', 'shift_id', 'shift_data',
            'one_shift_status', 'two_shift_status', 'three_shift_status', 'four_shift_status',
            'one_shift_location_status', 'two_shift_location_status', 'three_shift_location_status', 'four_shift_location_status',
        ]);
        $leaveDays = app()->get(AttendanceStatisticsLeaveService::class)->getLeaveDaysByStatistics($uid, $date, 'month');
        foreach ($records as $record) {
            if ($record->shift_id < 2) {
                continue;
            }
            $lackNum  = $absentNum = 0;
            $shiftNum = $record->shift_data['number'] * 2;
            $leaveDay = (string) ($leaveDays[$record->id] ?? '0.00');
            for ($i = 0; $i < $shiftNum; ++$i) {
                $shiftStatus         = $record->{$shifts[$i] . '_shift_status'};
                $shiftLocationStatus = $record->{$shifts[$i] . '_shift_location_status'};

                if ($shiftStatus == AttendanceClockEnum::LACK_CARD) {
                    ++$absentNum;
                }

                if (in_array($shiftStatus, AttendanceClockEnum::ALL_LACK_CARD)) {
                    ++$lackNum;
                }

                if ($shiftLocationStatus == AttendanceClockEnum::OFFICE_ABNORMAL) {
                    ++$locationAbnormal;
                }
            }

            if (bccomp($leaveDay, '1', 2) >= 0) {
                continue;
            }

            if ($absentNum == $shiftNum && bccomp($leaveDay, '0', 2) <= 0) {
                ++$absenteeism;
            } else {
                $lackCard += $lackNum;
            }
        }
        return [$absenteeism, $lackCard, $locationAbnormal];
    }

    /**
     * 获取异常天数.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function getAbnormalDays(int $uid, string $month): int
    {
        $abnormal = 0;
        $shifts   = AttendanceClockEnum::SHIFT_CLASS;
        $records  = $this->dao->select(['month' => $month, 'uid' => $uid], [
            'id', 'uid', 'shift_id', 'shift_data',
            'one_shift_status', 'two_shift_status', 'three_shift_status', 'four_shift_status',
            'one_shift_location_status', 'two_shift_location_status', 'three_shift_location_status', 'four_shift_location_status',
        ]);
        foreach ($records as $record) {
            if ($record->shift_id < 2) {
                continue;
            }

            $isRecord = false;
            $shiftNum = $record->shift_data['number'] * 2;
            for ($i = 0; $i < $shiftNum; ++$i) {
                if ($isRecord) {
                    break;
                }
                if ($record->{$shifts[$i] . '_shift_status'} > AttendanceClockEnum::NORMAL) {
                    ! $isRecord && $abnormal++;
                    $isRecord = true;
                }

                if ($record->{$shifts[$i] . '_shift_location_status'} > AttendanceClockEnum::OFFICE_OUTSIDE) {
                    ! $isRecord && $abnormal++;
                    $isRecord = true;
                }
            }
        }

        return $abnormal;
    }

    /**
     * 计算请假时长
     */
    private function calcLeaveDuration(string $duration, Carbon $startObj, Carbon $endObj): string
    {
        if ($startObj && $endObj && $endObj->gt($startObj)) {
            $duration = bcadd(bcdiv((string) $endObj->diffInMinutes($startObj), '60', 2), $duration, 2);
        }

        return $duration;
    }
}
