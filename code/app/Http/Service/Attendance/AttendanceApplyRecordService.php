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
use App\Http\Dao\Attendance\AttendanceApplyRecordDao;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveHolidayTypeService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 申请记录
 * Class AttendanceApplyRecordService.
 */
class AttendanceApplyRecordService extends BaseService
{
    public const CACHE_KEY = 'attendance_apply_record';

    public function __construct(AttendanceApplyRecordDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存申请记录.
     * @param int $applyId 审批申请ID
     * @param int $type 审批类型
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveRecord(int $applyId, int $type): void
    {
        $apply = app(ApproveApplyService::class)->get($applyId, with: ['approve', 'content'])?->toArray();
        if (! $apply) {
            return;
        }
        $others            = [];
        $duration          = 0;
        $dateValue         = ['timeType' => 'day'];
        $contentCollection = collect($apply['content']);
        // 处理异常记录ID
        $abnormalItem = $contentCollection->firstWhere('symbol', 'attendanceExceptionDate');
        if ($abnormalItem) {
            $others['abnormal_id'] = (int) $abnormalItem['value'];
        }
        // 处理加班时长
        $durationItem = $contentCollection->first(function ($item) {
            return isset($item['value']['duration']);
        });
        if ($durationItem) {
            $duration = $durationItem['value']['duration'];
        }
        // 处理时间相关逻辑
        $dateItem = $contentCollection->first(function ($item) {
            return isset($item['value']['dateStart'], $item['value']['dateEnd']);
        });
        if ($dateItem) {
            $dateValue = $dateItem['value'];
            $startObj  = Carbon::parse($dateValue['dateStart'], config('app.timezone'));
            $endObj    = Carbon::parse($dateValue['dateEnd'], config('app.timezone'));
            if ($dateValue['timeType'] == 'day') {
                $startObj = $dateValue['timeStart'] ? $startObj : $startObj->addHours(12)->subSecond();
                $endObj   = $dateValue['timeEnd'] ? $endObj->addHours(12) : $endObj->addDay()->subSecond();
            }
        } else {
            $startObj = $endObj = now();
        }
        // 处理加班补贴的calc_type
        if ($type == ApproveEnum::PERSONNEL_OVERTIME) {
            $overtimeItem = $contentCollection->first(function ($item) {
                return isset($item['content']['title']) && $item['content']['title'] == '加班补贴';
            });
            if ($overtimeItem) {
                $others['calc_type'] = $overtimeItem['value'] == '调休' ? 1 : 2;
            }
        }
        // 处理假期类型holiday_type_id
        if ($type == ApproveEnum::PERSONNEL_HOLIDAY) {
            $holidayItem = $contentCollection->first(function ($item) {
                return $item['symbol'] == 'holidayType' && $item['types'] == 'select';
            });
            if ($holidayItem) {
                $others['holiday_type_id'] = (int) $holidayItem['value'];
            }
        }
        // 补卡获取统计ID
        $recordItem = $contentCollection->firstWhere('symbol', 'attendanceExceptionRecord');
        if ($recordItem) {
            $others['record_id'] = (int) $recordItem['value'];
        }

        $this->transaction(function () use ($applyId, $type, $apply, $duration, $dateValue, $startObj, $endObj, $others) {
            $statisticsService = app()->get(AttendanceStatisticsService::class);
            $leaveService      = app()->get(AttendanceStatisticsLeaveService::class);
            $startTime         = $startObj->toDateTimeString();
            $endTime           = $endObj->toDateTimeString();

            $recordIds = $this->dao->column(['apply_id' => $applyId, 'apply_type' => $type], 'id');
            if ($recordIds) {
                $leaveService->dao->delete(['apply_record_id' => $recordIds]);
                $this->dao->delete(['id' => $recordIds]);
            }

            if ($type == ApproveEnum::PERSONNEL_SIGN && isset($others['abnormal_id'])) {
                $abnormalDate = $statisticsService->value($others['abnormal_id'], 'created_at');
                $startTime    = $abnormalDate ? Carbon::parse($abnormalDate, config('app.timezone'))->toDateString() : $startTime;
            }

            $res = $this->dao->create([
                'uid'        => $apply['user_id'],
                'apply_id'   => $applyId,
                'apply_type' => $type,
                'work_hours' => $duration,
                'date_type'  => app(AttendanceArrangeService::class)->dayIsRest($apply['user_id'], $startObj->toDateString()) ? 2 : 1,
                'time_type'  => $dateValue['timeType'] ?? 'day',
                'calc_type'  => $others['calc_type'] ?? 0,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'others'     => $others,
            ]);

            if (isset($others['record_id']) && $others['record_id'] > 0) {
                --$others['record_id'];
            }

            $statisticsService->updateAbnormalShiftStatus($apply['user_id'], $type, $startTime, $endTime, others: $others);

            $tags = [self::CACHE_KEY];
            if ($res && $type == ApproveEnum::PERSONNEL_HOLIDAY && ! empty($others['holiday_type_id'])) {
                $statisticsService->calcLeaveDurationByTime($apply['user_id'], $res->id, $others['holiday_type_id'], $startTime, $endTime);
                $tags[] = AttendanceStatisticsLeaveService::CACHE_KEY;
            }

            Cache::tags($tags)->flush();
        });
    }

    /**
     * 撤销审批时删除关联的考勤申请记录.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function revokeByApplyId(int $applyId): void
    {
        $records = $this->dao->select(['apply_id' => $applyId], ['id', 'apply_type']);
        if ($records->isEmpty()) {
            return;
        }

        $this->transaction(function () use ($records) {
            $leaveService = app()->get(AttendanceStatisticsLeaveService::class);
            foreach ($records as $record) {
                if ($record->apply_type == ApproveEnum::PERSONNEL_HOLIDAY) {
                    $leaveService->delete(['apply_record_id' => $record->id]);
                }

                $this->dao->delete($record->id);
            }
        });

        Cache::tags([self::CACHE_KEY, AttendanceStatisticsLeaveService::CACHE_KEY])->flush();
    }

    /**
     * 个人加班统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getPersonOvertimeStatistics(string $uuid, array $where): array
    {
        $list                = [];
        $timeTye             = 'hour';
        [$page, $limit]      = $this->getPageValue();
        $where['apply_type'] = ApproveEnum::PERSONNEL_OVERTIME;

        $tz             = config('app.timezone');
        $where['month'] = Carbon::parse($where['month'], $tz)->format('Y-m');
        $where['uid']   = app()->get(AttendanceClockService::class)->getStatisticsUserId($uuid, (int) $where['uid']);
        $statistics     = $this->dao->select($where, ['id', 'uid', 'date_type', 'time_type', 'calc_type', 'others', 'work_hours', 'start_time'], [], $page, $limit);
        foreach ($statistics as $item) {
            $dateString = Carbon::parse($item->start_time, $tz)->toDateString();
            $workHours  = $timeTye == $item->time_type ? $item->work_hours : $this->getWorkHours($item->time_type, $timeTye, $item->work_hours);
            $calcType   = (int) $item->calc_type;
            $dateType   = $dateString . '_' . $calcType;
            if (isset($list[$dateType])) {
                $list[$dateType]['work_hours'] = bcadd($workHours, $list[$dateType]['work_hours'], 1);
            } else {
                $list[$dateType] = [
                    'id'         => $item->id,
                    'date'       => $dateString,
                    'time_type'  => $timeTye,
                    'work_hours' => $workHours,
                    'date_type'  => $item->date_type,
                    'calc_type'  => $calcType,
                ];
            }
        }
        return array_values($list);
    }

    /**
     * 获取加班人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getOverTimeData(string $month, string $dateType, array $uid): array
    {
        $data  = [];
        $where = ['month' => $month, 'date_type' => $dateType, 'uid' => $uid, 'apply_type' => ApproveEnum::PERSONNEL_OVERTIME];
        $this->dao->getCountGroupByUid($where)->each(function ($item) use (&$data) {
            $data[$item->uid] = $item->count;
        });
        return $data;
    }

    /**
     * 个人假勤统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getPersonLeaveStatistics(string $uuid, array $where): array
    {
        $list                = [];
        [$page, $limit]      = $this->getPageValue();
        $where['uid']        = app()->get(AttendanceClockService::class)->getStatisticsUserId($uuid, (int) $where['uid']);
        $where['apply_type'] = [ApproveEnum::PERSONNEL_HOLIDAY, ApproveEnum::PERSONNEL_SIGN, ApproveEnum::PERSONNEL_OUT, ApproveEnum::PERSONNEL_TRIP];
        unset($where['status'], $where['user_id']);
        $statistics = $this->dao->select($where, ['id', 'uid', 'apply_type', 'calc_type', 'others', 'date_type', 'time_type', 'work_hours', 'start_time', 'created_at'], [], $page, $limit);

        $getStatus = function ($type, $holidayTypeId) {
            return match ($type) {
                ApproveEnum::PERSONNEL_HOLIDAY => $holidayTypeId,
                ApproveEnum::PERSONNEL_SIGN    => -1,
                ApproveEnum::PERSONNEL_TRIP    => -2,
                ApproveEnum::PERSONNEL_OUT     => -3,
                default                        => 0
            };
        };

        $tz = config('app.timezone');
        foreach ($statistics as $item) {
            $date          = Carbon::parse($item->start_time ?: $item->created_at, $tz)->toDateString();
            $holidayTypeId = $item->others['holiday_type_id'] ?? '';
            $status        = $getStatus($item->apply_type, $holidayTypeId);

            if ($item->apply_type == ApproveEnum::PERSONNEL_SIGN) {
                $workType = $item->others['record_id'] % 2 == 0 ? 0 : 1;
            } else {
                $workType = $item->apply_type == ApproveEnum::PERSONNEL_OVERTIME ? (int) $item->calc_type : 0;
            }
            $type = $item->apply_type . '_' . $workType . '_' . $holidayTypeId;

            if (! isset($list[$date])) {
                $list[$date] = [
                    'id'      => $item->id,
                    'date'    => $date,
                    'details' => [
                        $type => [
                            'work_hours' => $item->apply_type == ApproveEnum::PERSONNEL_SIGN ? '1' : $item->work_hours,
                            'work_type'  => $workType,
                            'time_type'  => $item->time_type,
                            'status'     => $status,
                        ],
                    ],
                ];
            } else {
                if (isset($list[$date]['details'][$type])) {
                    $list[$date]['details'][$type]['work_hours'] = $item->apply_type == ApproveEnum::PERSONNEL_SIGN
                        ? bcadd($list[$date]['details'][$type]['work_hours'], '1')
                        : bcadd($list[$date]['details'][$type]['work_hours'], $item->work_hours, 1);
                } else {
                    $list[$date]['details'][$type] = [
                        'work_hours' => $item->apply_type == ApproveEnum::PERSONNEL_SIGN ? '1' : $item->work_hours,
                        'work_type'  => $workType,
                        'time_type'  => $item->time_type,
                        'status'     => $status,
                    ];
                }
            }
        }

        foreach ($list as &$item) {
            $item['details'] = array_values($item['details']);
        }
        return array_values($list);
    }

    /**
     * 按月获取假勤次数.
     * @throws BindingResolutionException
     */
    public function getLeaveNumByMonth(array|int $uid, string $month, int $applyType, int $holidayTypeId = 0): int
    {
        $where = ['uid' => $uid, 'month' => $month, 'apply_type' => $applyType];
        if ($holidayTypeId) {
            $where['others->holiday_type_id'] = $holidayTypeId;
        }
        return $this->dao->count($where);
    }

    /**
     * 按时间获取假勤次数.
     * @throws BindingResolutionException
     */
    public function getLeaveNumByTime(array|int $uid, string $time, int $applyType, int $holidayTypeId = 0): int
    {
        $where = ['uid' => $uid, 'apply_type' => $applyType, 'time' => $time];
        if ($holidayTypeId) {
            $where['others->holiday_type_id'] = $holidayTypeId;
        }
        return $this->dao->count($where);
    }

    /**
     * 假勤统计
     * @return array[]
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getPersonLeaveMonthStatistics(array|int $uid, string $month): array
    {
        $data = [
            [
                'num'    => $this->getLeaveNumByMonth($uid, $month, ApproveEnum::PERSONNEL_SIGN),
                'status' => -1,
                'name'   => '申请补卡',
            ],
            [
                'num'    => $this->getLeaveNumByMonth($uid, $month, ApproveEnum::PERSONNEL_TRIP),
                'status' => -2,
                'name'   => '出差',
            ],
            [
                'num'    => $this->getLeaveNumByMonth($uid, $month, ApproveEnum::PERSONNEL_OUT),
                'status' => -3,
                'name'   => '外出',
            ],
        ];
        $holidayTypeList = app()->get(ApproveHolidayTypeService::class)->getTypeList();
        foreach ($holidayTypeList as $item) {
            $data[] = [
                'num'    => $this->getLeaveNumByMonth($uid, $month, ApproveEnum::PERSONNEL_HOLIDAY, $item['id']),
                'status' => $item['id'],
                'name'   => $item['name'],
            ];
        }

        return $data;
    }

    /**
     * 加班统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getOvertimeByDateType(array|int $uid, string $month, int $dateType): string
    {
        $where = ['uid' => $uid, 'apply_type' => ApproveEnum::PERSONNEL_OVERTIME, 'date_type' => $dateType, 'month' => $month];
        return $this->getSumByTimeType($where);
    }

    /**
     * 加班次数统计
     * @throws BindingResolutionException
     */
    public function getOvertimeNumByDateType(array|int $uid, string $month, int $dateType): int
    {
        $where = ['uid' => $uid, 'apply_type' => ApproveEnum::PERSONNEL_OVERTIME, 'date_type' => $dateType, 'month' => $month];
        return $this->dao->count($where, ['date_type', 'time_type', 'work_hours']);
    }

    /**
     * 获取假勤人员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getLeaveData(array $uid, string $month, array $status = []): array
    {
        $data      = [];
        $applyType = [ApproveEnum::PERSONNEL_SIGN, ApproveEnum::PERSONNEL_TRIP, ApproveEnum::PERSONNEL_OUT, ApproveEnum::PERSONNEL_HOLIDAY];
        $this->dao->getCountGroupByUid(['uid' => $uid, 'month' => $month, 'apply_type' => $applyType])->each(function ($item) use (&$data) {
            $data[$item->uid] = $item->count;
        });
        return $data;
    }

    /**
     * 按月统计申请.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSumByMonth(int $uid, string $month, int $applyType, string $timeType = 'hour'): string
    {
        $where = ['uid' => $uid, 'apply_type' => $applyType, 'month' => $month];
        return Cache::tags([self::CACHE_KEY])->remember(md5(json_encode($where)), (int) sys_config('system_cache_ttl', 3600), function () use ($where, $timeType) {
            return $this->getSumByTimeType($where, $timeType);
        });
    }

    /**
     * 按时间统计申请.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSumByTime(int $uid, string $time, int $applyType, string $timeType = 'day'): string
    {
        $where = ['uid' => $uid, 'apply_type' => $applyType, 'time' => $time];
        return $this->getSumByTimeType($where, $timeType);
    }

    /**
     * 时间类型统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSumByTimeType(array $where, string $timeType = 'hour'): string
    {
        $time = '0.00';
        $list = $this->dao->setTimeField('start_time')->select($where, ['date_type', 'time_type', 'work_hours']);
        foreach ($list as $item) {
            $workHours = $timeType == $item->time_type ? $item->work_hours : $this->getWorkHours($item->time_type, $timeType, $item->work_hours);
            $time      = bcadd($workHours, $time, 2);
        }

        return $time;
    }

    /**
     * 按指定日期统计已通过审批且覆盖当天的申请时长.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getApprovedSumByDate(array $where, string $date, string $timeType = 'hour', float|int|string $dayHours = '0.00', bool $requireDayHours = false): string
    {
        $tz       = config('app.timezone');
        $dateObj  = Carbon::parse($date, $tz);
        $startObj = $dateObj->copy()->startOfDay();
        $endObj   = $dateObj->copy()->endOfDay();
        $time     = '0.00';

        $list = $this->dao->selectApprovedInTimeRange(
            $where,
            $startObj->toDateTimeString(),
            $endObj->toDateTimeString(),
            ['time_type', 'work_hours', 'start_time', 'end_time']
        );

        foreach ($list as $item) {
            $time = bcadd($time, $this->getRecordDurationByDate($item, $dateObj, $timeType, $dayHours, $tz, $requireDayHours), 2);
        }

        return $time;
    }

    /**
     * 获取指定日期范围内已通过请假申请使用到的假期类型.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getApprovedHolidayTypeIdByDate(array|string $date, array|int $uid): array
    {
        $dates = array_values(array_filter((array) $date));
        if (! $dates) {
            return [];
        }

        $tz      = config('app.timezone');
        $typeIds = [];
        foreach ($dates as $day) {
            $dateObj = Carbon::parse($day, $tz);
            $records = $this->dao->selectApprovedInTimeRange(
                ['uid' => $uid, 'apply_type' => ApproveEnum::PERSONNEL_HOLIDAY],
                $dateObj->copy()->startOfDay()->toDateTimeString(),
                $dateObj->copy()->endOfDay()->toDateTimeString(),
                ['others']
            );

            foreach ($records as $record) {
                $typeId = (int) ($record->others['holiday_type_id'] ?? 0);
                if ($typeId > 0) {
                    $typeIds[] = $typeId;
                }
            }
        }

        return array_values(array_unique($typeIds));
    }

    /**
     * 获取工时.
     */
    public function getWorkHours(string $nowType, string $wantType, float|int|string $workHours): string
    {
        $workHours = (string) $workHours;
        return match ($nowType . '_' . $wantType) {
            'hour_day'    => bcdiv($workHours, '24', 2),
            'hour_minute' => bcmul($workHours, '3600', 2),
            'day_hour'    => bcmul($workHours, '24', 2),
            'day_minute'  => bcmul($workHours, '86400', 2),
            'minute_hour' => bcdiv($workHours, '3600', 2),
            'minute_day'  => bcdiv($workHours, '86400', 2),
            default       => $workHours
        };
    }

    /**
     * 计算单条申请在指定日期内的时长.
     */
    private function getRecordDurationByDate(mixed $record, Carbon $dateObj, string $wantType, float|int|string $dayHours, string $tz, bool $requireDayHours): string
    {
        $timeType = (string) $record->time_type;
        if ($timeType === 'day') {
            $dayDuration = $this->getDayDurationByDate(
                Carbon::parse($record->start_time, $tz),
                Carbon::parse($record->end_time, $tz),
                $dateObj,
                $record->work_hours
            );

            return $this->convertDailyDuration('day', $wantType, $dayDuration, $dayHours, $requireDayHours);
        }

        $duration = $this->getOverlapDurationByDate(
            Carbon::parse($record->start_time, $tz),
            Carbon::parse($record->end_time, $tz),
            $dateObj,
            $record->work_hours
        );

        return $this->convertDailyDuration($timeType, $wantType, $duration, $dayHours, $requireDayHours);
    }

    /**
     * 计算按天申请落在某一天的天数.
     */
    private function getDayDurationByDate(Carbon $startObj, Carbon $endObj, Carbon $dateObj, float|int|string $workHours): string
    {
        $date      = $dateObj->toDateString();
        $startDate = $startObj->toDateString();
        $endDate   = $endObj->toDateString();

        if ($date < $startDate || $date > $endDate) {
            return '0.00';
        }

        if ($startDate === $endDate) {
            return sprintf('%.2f', (float) $workHours);
        }

        if ($date === $startDate) {
            return $startObj->hour >= 12 || ($startObj->hour === 11 && $startObj->minute === 59) ? '0.50' : '1.00';
        }

        if ($date === $endDate) {
            return $endObj->hour < 13 ? '0.50' : '1.00';
        }

        return '1.00';
    }

    /**
     * 计算按小时/分钟申请落在某一天的时长.
     */
    private function getOverlapDurationByDate(Carbon $startObj, Carbon $endObj, Carbon $dateObj, float|int|string $workHours): string
    {
        $dayStart = $dateObj->copy()->startOfDay();
        $dayEnd   = $dateObj->copy()->endOfDay();
        if ($endObj->lt($dayStart) || $startObj->gt($dayEnd)) {
            return '0.00';
        }

        if ($startObj->toDateString() === $endObj->toDateString()) {
            return sprintf('%.2f', (float) $workHours);
        }

        $totalSeconds = max(1, $endObj->diffInSeconds($startObj));
        $overlapStart = $startObj->gt($dayStart) ? $startObj : $dayStart;
        $overlapEnd   = $endObj->lt($dayEnd) ? $endObj : $dayEnd;
        $seconds      = max(0, $overlapEnd->diffInSeconds($overlapStart));

        return sprintf('%.2f', ((float) $workHours) * ($seconds / $totalSeconds));
    }

    /**
     * 按每日统计口径转换申请时长单位.
     */
    private function convertDailyDuration(string $nowType, string $wantType, float|int|string $duration, float|int|string $dayHours, bool $requireDayHours): string
    {
        $duration = (string) $duration;
        $dayHours = (string) $dayHours;

        if ($requireDayHours && bccomp($dayHours, '0', 2) <= 0) {
            return '0.00';
        }

        if ($nowType === $wantType) {
            return sprintf('%.2f', (float) $duration);
        }

        if (bccomp($dayHours, '0', 2) > 0) {
            return match ($nowType . '_' . $wantType) {
                'day_hour'    => bcmul($duration, $dayHours, 2),
                'day_minute'  => bcmul(bcmul($duration, $dayHours, 2), '3600', 2),
                'hour_day'    => bcdiv($duration, $dayHours, 2),
                'minute_day'  => bcdiv(bcdiv($duration, '3600', 2), $dayHours, 2),
                default       => $this->getWorkHours($nowType, $wantType, $duration),
            };
        }

        return $this->getWorkHours($nowType, $wantType, $duration);
    }

    /**
     * 按时间获取假勤人/次数.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getLeaveNumByTimeByUid(array|int $uid, int $applyType, string $time, string $timeType = 'date', bool $isDistinct = false): int
    {
        $obj = $this->dao->search(['uid' => $uid, 'apply_type' => $applyType, $timeType => $time]);
        if ($isDistinct) {
            $obj->distinct('uid');
        }
        return $obj->count();
    }

    /**
     * 申请记录.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function createApplyRecord(int $id): void
    {
        $applyInfo = app()->get(ApproveApplyService::class)->get($id, ['id', 'user_id', 'status', 'approve_id'], ['approve', 'content']);

        if (! $applyInfo || $applyInfo->status != 1) {
            return;
        }

        $applyInfo = $applyInfo->toArray();

        $typeField = match ($applyInfo['approve']['types']) {
            ApproveEnum::PERSONNEL_OVERTIME => 'overtimeFrom',
            ApproveEnum::PERSONNEL_OUT      => 'outFrom',
            ApproveEnum::PERSONNEL_HOLIDAY  => 'leaveDuration',
            ApproveEnum::PERSONNEL_TRIP     => 'tripFrom',
            ApproveEnum::PERSONNEL_SIGN     => 'refillFrom',
            default                         => ''
        };
        if (! $typeField) {
            return;
        }
        $this->createRecord($applyInfo['user_id'], $applyInfo);
    }

    /**
     * 保存申请记录.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function createRecord(int $uid, array $apply): mixed
    {
        $tz = config('app.timezone');

        $applyId = $apply['id'];
        $type    = $apply['approve']['types'];

        // other options
        $others = [];

        $duration = 0;
        $timeType = 'day';
        $endTime  = $startTime = '';

        foreach ($apply['content'] as $item) {
            if ($item['symbol'] == 'attendanceExceptionDate') {
                $others['abnormal_id'] = (int) $item['value'];
            }

            if ($item['symbol'] == 'attendanceExceptionRecord') {
                $others['record_id'] = (int) $item['value'];
            }

            if (isset($item['value']['duration'])) {
                $duration = $item['value']['duration'];
            }

            if (isset($item['value']['dateStart'], $item['value']['dateEnd'])) {
                $startObj = Carbon::parse($item['value']['dateStart'], $tz);
                $endObj   = Carbon::parse($item['value']['dateEnd'], $tz);

                if ($item['value']['timeType'] == 'day') {
                    $startTime = $startObj->format('Y-m-d ' . ($item['value']['timeStart'] ? '00:00:00' : '12:00:00'));
                    $endTime   = $endObj->format('Y-m-d ' . ($item['value']['timeEnd'] ? '12:00:01' : '23:59:59'));
                } else {
                    $timeType  = 'hour';
                    $startTime = $startObj->toDateTimeString();
                    $endTime   = $endObj->toDateTimeString();
                }
            }

            if ($type == ApproveEnum::PERSONNEL_OVERTIME && isset($item['content']['title']) && $item['content']['title'] == '加班补贴') {
                $others['calc_type'] = $item['value'] == '调休' ? 1 : 2;
            }

            if ($type == ApproveEnum::PERSONNEL_HOLIDAY && $item['symbol'] == 'holidayType' && $item['types'] == 'select') {
                $others['holiday_type_id'] = (int) $item['value'];
            }
        }

        return $this->transaction(function () use ($uid, $type, $applyId, $startTime, $endTime, $duration, $timeType, $tz, $others) {
            $statisticsService = app()->get(AttendanceStatisticsService::class);
            if ($type == ApproveEnum::PERSONNEL_SIGN && isset($others['abnormal_id'])) {
                $startTime = $statisticsService->value($others['abnormal_id'], 'created_at')->toDateString();
            }

            $res = $this->dao->create([
                'uid'        => $uid,
                'work_hours' => $duration,
                'time_type'  => $timeType,
                'date_type'  => app()->get(AttendanceArrangeService::class)->dayIsRest($uid, $startTime) ? 2 : 1,
                'apply_id'   => $applyId,
                'start_time' => $startTime ?: null,
                'end_time'   => $endTime ?: null,
                'apply_type' => $type,
                'calc_type'  => $others['calc_type'] ?? 0,
                'others'     => $others,
            ]);

            // update abnormal attendance
            if (isset($others['record_id']) && $others['record_id'] > 0) {
                --$others['record_id'];
            }

            $statisticsService->updateAbnormalShiftStatus($uid, $type, $startTime, $endTime, $tz, $others);

            if ($res) {
                $tags = [self::CACHE_KEY];

                // save statistics leave duration
                if ($type == ApproveEnum::PERSONNEL_HOLIDAY) {
                    $statisticsService->calcLeaveDurationByTime($uid, $res->id, $others['holiday_type_id'], $startTime, $endTime);
                    $tags[] = AttendanceStatisticsLeaveService::CACHE_KEY;
                }

                Cache::tags($tags)->flush();
            }
            return $res;
        });
    }

    /**
     * 变更考勤记录.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateAbnormalShiftStatusByApplyRecord(int $userId, string $compareTime): void
    {
        $tz   = config('app.timezone');
        $list = $this->dao->select(['uid' => $userId, 'compare_time' => $compareTime]);

        $statisticsService = app()->get(AttendanceStatisticsService::class);
        foreach ($list as $item) {
            $statisticsService->updateAbnormalShiftStatus(
                $userId,
                $item->apply_type,
                $this->normalizeApplyRecordTime($item->start_time),
                $this->normalizeApplyRecordTime($item->end_time),
                $tz,
                $item->others
            );
        }
    }

    /**
     * 指定范围内考勤类型数量.
     * @throws BindingResolutionException
     */
    public function getCountByApplyType(int $userId, string $compareTime, int $applyType): int
    {
        return $this->dao->count(['uid' => $userId, 'compare_time' => $compareTime, 'apply_type' => $applyType]);
    }

    /**
     * 更新 请假/出差/外出/加班 考勤数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function calcApplyRecordTime(string $date): void
    {
        $type = [ApproveEnum::PERSONNEL_HOLIDAY, ApproveEnum::PERSONNEL_OVERTIME, ApproveEnum::PERSONNEL_OUT, ApproveEnum::PERSONNEL_TRIP];
        $tz      = config('app.timezone');
        $dateObj = Carbon::parse($date, $tz);
        $list    = $this->dao->selectApprovedInTimeRange(
            ['apply_type' => $type],
            $dateObj->copy()->startOfDay()->toDateTimeString(),
            $dateObj->copy()->endOfDay()->toDateTimeString()
        );
        if ($list->isEmpty()) {
            return;
        }

        $tags = [];

        $statisticsService = app()->get(AttendanceStatisticsService::class);
        foreach ($list as $item) {
            $startTime = $this->normalizeApplyRecordTime($item->start_time);
            $endTime   = $this->normalizeApplyRecordTime($item->end_time);

            // update abnormal attendance
            $statisticsService->updateAbnormalShiftStatus($item->uid, $item->apply_type, $startTime, $endTime, $tz, $item->others);

            // save statistics leave duration
            if ($item->apply_type == ApproveEnum::PERSONNEL_HOLIDAY) {
                $statisticsService->calcLeaveDurationByTime($item->uid, $item->id, $item->others['holiday_type_id'], $startTime, $endTime);
                empty($tags) && $tags[] = AttendanceStatisticsLeaveService::CACHE_KEY;
            }
        }
        $tags && Cache::tags($tags)->flush();
    }

    private function normalizeApplyRecordTime(mixed $time): string
    {
        return $time instanceof Carbon ? $time->toDateTimeString() : (string) $time;
    }
}
