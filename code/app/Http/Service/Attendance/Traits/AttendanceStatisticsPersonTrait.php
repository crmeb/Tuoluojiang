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
use App\Http\Service\Attendance\AttendanceApplyRecordService;
use App\Http\Service\Attendance\AttendanceClockService;
use App\Http\Service\Attendance\AttendanceGroupService;
use App\Http\Service\Attendance\AttendanceShiftService;
use App\Http\Service\Attendance\AttendanceStatisticsLeaveService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤统计个人方法.
 */
trait AttendanceStatisticsPersonTrait
{
    /**
     * 获取打卡记录.
     * @param mixed $tz
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getClockRecord(mixed $info, int $clockNumber, $tz, bool $isRest = false, bool $isReal = false): array
    {
        $list   = [];
        $nowObj = now($tz);
        $shifts = AttendanceClockEnum::SHIFT_CLASS;

        for ($i = 0; $i <= $clockNumber; ++$i) {
            if ($isRest) {
                continue;
            }

            $clockTime = $info->{$shifts[$i] . '_shift_time'};

            $record = [
                'number'          => $i,
                'clock_time'      => $clockTime ? Carbon::parse($clockTime, $tz)->format('H:i') : '',
                'location_status' => $info->{$shifts[$i] . '_shift_location_status'} ?? 0,
                'status'          => $info->{$shifts[$i] . '_shift_status'} ?? 0,
                'record_id'       => $info->{$shifts[$i] . '_shift_record_id'} ?? 0,
                'lat'             => '',
                'lng'             => '',
                'remark'          => '',
                'address'         => '',
                'image'           => [],
            ];

            $record['update_status'] = $this->canUpdateClockRecord($info, $i, $record, $nowObj, $tz) ? 1 : 0;
            if ($isReal && $record['status'] < 1 && ! $record['clock_time']) {
                continue;
            }

            $list[] = $record;
        }
        return $list;
    }

    /**
     * 获取打卡记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getStatisticsByUid(int $uid): array
    {
        $tz      = config('app.timezone');
        $dateObj = now($tz);
        $info    = $this->renewStatistics($uid, $dateObj);

        // 跨天重叠处理：主记录为昨日时，若今日也有匹配班次，切换到今日记录展示
        $primaryDate = isset($info->created_at) ? Carbon::parse($info->created_at, $tz)->toDateString() : '';
        $today       = $dateObj->toDateString();
        $yesterday   = Carbon::parse($today, $tz)->subDay()->toDateString();
        if ($primaryDate === $yesterday) {
            // 确保今日统计记录存在于数据库
            $this->renewStatisticsByDate($uid, $today);
            $overlapRecord = $this->findOverlappingRecord($uid, $dateObj, $today, $info, $tz);
            if ($overlapRecord) {
                $info = $overlapRecord;
            }
        }

        $isWhitelist            = app()->get(AttendanceGroupService::class)->isWhitelist($uid);
        [$status, $clockNumber] = $this->getClockNumber($dateObj, $info, $tz, $isWhitelist);
        if (! $isWhitelist) {
            [$info, $status, $clockNumber] = $this->checkClockRecord($info, $dateObj, $status, $clockNumber, $tz);
        }
        $list = $this->getStatisticsList($info, $clockNumber, $tz);

        $timestamp = $this->getClockTime($uid, $info, $clockNumber, $tz, $dateObj);
        return [
            'list'            => $list,
            'abnormal'        => $this->dao->count(['month' => $dateObj->format('Y-m'), 'uid' => $uid, 'abnormal_status' => AttendanceClockEnum::NORMAL]),
            'clock_status'    => $isWhitelist ? 1 : ($status == 0 ? $status : $this->getClockStatus($clockNumber, $dateObj, $info)),
            'clock_timestamp' => $timestamp,
        ];
    }

    /**
     * 个人考勤统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getPersonAttendanceStatistics(string $uuid, string $date, array $status = [], int $userId = 0): array
    {
        $tz = config('app.timezone');
        try {
            $dateObj = $date ? Carbon::parse($date, $tz) : now($tz);
        } catch (\Throwable $e) {
            throw $this->exception('日期格式有误');
        }
        $where = [
            'uid'   => app()->get(AttendanceClockService::class)->getStatisticsUserId($uuid, $userId),
            'month' => $dateObj->format('Y-m'),
        ];
        [$page, $limit] = $this->getPageValue();
        $statistics     = $this->dao->select($where, [
            'id', 'uid', 'shift_id', 'shift_data',
            'one_shift_status', 'two_shift_status', 'three_shift_status', 'four_shift_status',
            'one_shift_location_status', 'two_shift_location_status', 'three_shift_location_status', 'four_shift_location_status',
            'one_shift_time', 'two_shift_time', 'three_shift_time', 'four_shift_time',
            'required_work_hours', 'actual_work_hours',
        ], [], $page, $limit);

        $list         = [];
        $shifts       = AttendanceClockEnum::SHIFT_CLASS;
        $shiftService = app()->get(AttendanceShiftService::class);
        $leaveDays    = app()->get(AttendanceStatisticsLeaveService::class)->getLeaveDaysByStatistics($uid, $month, 'month');
        foreach ($statistics as $item) {
            $locationStatus = 0;

            $details   = [];
            $absentNum = $lateNum = $earlyNum = 0;
            $shiftNum  = ($item->shift_data['number'] ?? 0) * 2;
            $leaveDay  = (string) ($leaveDays[$item->id] ?? '0.00');
            for ($i = 0; $i < $shiftNum; ++$i) {
                $shiftStatus         = $item->{$shifts[$i] . '_shift_status'};
                $shiftLocationStatus = $item->{$shifts[$i] . '_shift_location_status'};

                if ($shiftLocationStatus > $locationStatus) {
                    $locationStatus = $shiftLocationStatus;
                }

                if ($shiftStatus == AttendanceClockEnum::LACK_CARD) {
                    ++$absentNum;
                }

                if (in_array($shiftStatus, AttendanceClockEnum::ALL_LACK_CARD)) {
                    $shiftStatus == AttendanceClockEnum::LATE_LACK_CARD && $lateNum++;
                    $shiftStatus == AttendanceClockEnum::EARLY_LACK_CARD && $earlyNum++;

                    if (! isset($details[$shiftStatus])) {
                        $details[$shiftStatus] = [
                            'work_type'  => in_array($i, [0, 2]) ? 1 : 2,
                            'time_type'  => '',
                            'work_hours' => '0.00',
                            'status'     => $shiftStatus,
                        ];
                    }
                }

                if (in_array($shiftStatus, AttendanceClockEnum::LATE_AND_LEAVE_EARLY)) {
                    $minutes = $shiftService->getNormalMinutes(
                        $item->created_at->toDateTimeString(),
                        $item->shift_data,
                        $shiftStatus,
                        $i,
                        (string) $item->{$shifts[$i] . '_shift_time'},
                        $tz
                    );
                    if (! isset($details[$shiftStatus])) {
                        $details[$shiftStatus] = [
                            'work_type'  => 0,
                            'time_type'  => 'minute',
                            'work_hours' => sprintf('%.2f', $minutes),
                            'status'     => $shiftStatus,
                        ];
                    } else {
                        $details[$shiftStatus]['work_hours'] = bcadd((string) $minutes, $details[$shiftStatus]['work_hours'], 2);
                    }
                }
            }

            $list[] = [
                'id'              => $item->id,
                'date'            => $item->created_at->toDateString(),
                'absenteeism'     => $item->shift_id > 1 && $absentNum == $shiftNum && bccomp($leaveDay, '0', 2) <= 0 ? 1 : 0,
                'location_status' => $locationStatus,
                'details'         => $absentNum != $shiftNum || bccomp($leaveDay, '0', 2) > 0 ? array_values($details) : [],
            ];
        }
        return $list;
    }

    /**
     * 判断是否为跨天打卡统计记录.
     * 当统计记录的实际日期（created_at）与当前日期不同时，说明是跨天打卡场景.
     * @param mixed $info
     */
    private function isCrossDayStatistics($info, Carbon $dateObj, string $tz): bool
    {
        if (! $info || ! isset($info->created_at)) {
            return false;
        }
        $statisticsDate = Carbon::parse($info->created_at, $tz)->toDateString();
        return $statisticsDate !== $dateObj->toDateString();
    }

    /**
     * 判断打卡记录是否允许前端展示更新打卡入口.
     * 上班卡只有地点异常且当前仍可打正常卡时才能更新，和实际更新接口保持一致。
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function canUpdateClockRecord(mixed $info, int $clockNumber, array $record, Carbon $nowObj, string $tz): bool
    {
        if (! $record['clock_time']) {
            return false;
        }

        if ($nowObj->timestamp >= $this->getClockEndTime((int) $info['uid'], $info, $clockNumber, $tz)) {
            return false;
        }

        if (! in_array($clockNumber, [0, 2])) {
            return true;
        }

        return $record['location_status'] == AttendanceClockEnum::OFFICE_ABNORMAL
            && $this->getClockStatus($clockNumber, $nowObj, $info) == AttendanceClockEnum::NORMAL;
    }

    /**
     * 获取个人月报打卡统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function getPersonMonthStatistics(string $uuid, int $userId, string $month): array
    {
        $uid                                         = app()->get(AttendanceClockService::class)->getStatisticsUserId($uuid, $userId);
        [$absenteeism, $lackCard, $locationAbnormal] = $this->getLackCardAndAbsenteeismNum($uid, $month);
        $where                                       = ['month' => $month, 'uid' => $uid];

        $clockStatistics = [
            'lack_card'         => $lackCard,
            'absenteeism'       => $absenteeism,
            'location_abnormal' => $locationAbnormal,
            'total'             => $this->dao->count($where),
            'work_hours'        => sprintf('%.2f', $this->dao->avg($where, 'actual_work_hours')),
            'late'              => $this->dao->count(array_merge($where, ['status' => AttendanceClockEnum::ALL_LATE])),
            'leave_early'       => $this->dao->count(array_merge($where, ['status' => AttendanceClockEnum::LEAVE_EARLY])),
            'abnormal'          => $this->dao->count(array_merge($where, ['abnormal_status' => AttendanceClockEnum::NORMAL])),
        ];

        $recordService      = app()->get(AttendanceApplyRecordService::class);
        $overtimeStatistics = [
            'work'    => $recordService->getOvertimeByDateType($uid, $month, 1),
            'rest'    => $recordService->getOvertimeByDateType($uid, $month, 2),
            'holiday' => '0.00',
        ];

        return [$clockStatistics, $overtimeStatistics, $recordService->getPersonLeaveMonthStatistics($uid, $month)];
    }
}
