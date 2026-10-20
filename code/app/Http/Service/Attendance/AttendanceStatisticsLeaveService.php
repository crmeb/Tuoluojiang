<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Attendance;

use App\Http\Dao\Attendance\AttendanceStatisticsLeaveDao;
use Carbon\Carbon;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;

/**
 * 考勤请假工时
 * Class AttendanceStatisticsLeaveService.
 */
class AttendanceStatisticsLeaveService extends BaseService
{
    public const CACHE_KEY = 'attendance_statistics_leave';

    public function __construct(AttendanceStatisticsLeaveDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 创建.
     * @throws BindingResolutionException
     */
    public function createLeaveRecord(int $statisticsId, int $uid, int $holidayTypeId, string $duration, int $applyRecordId, Carbon $createAt): bool
    {
        $res = $this->dao->updateOrCreate([
            'statistics_id'   => $statisticsId,
            'apply_record_id' => $applyRecordId,
            'holiday_type_id' => $holidayTypeId,
        ], [
            'statistics_id'   => $statisticsId,
            'uid'             => $uid,
            'holiday_type_id' => $holidayTypeId,
            'leave_duration'  => $duration,
            'apply_record_id' => $applyRecordId,
            'created_at'      => $createAt,
            'updated_at'      => $createAt,
        ]);

        if ($res) {
            Cache::tags([self::CACHE_KEY])->flush();
        }
        return (bool) $res;
    }

    /**
     * 按月度获取请假时长
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMonthLeaveDurationByHolidayTypeId(int $uid, string $month, int $holidayTypeId, int $durationType = 1): string
    {
        return $this->getLeaveDurationByHolidayTypeId($uid, $month, $holidayTypeId, $durationType);
    }

    /**
     * 按日期获取请假时长
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getDateLeaveDurationByHolidayTypeId(int $uid, string $date, int $holidayTypeId, int $durationType = 1): string
    {
        return $this->getLeaveDurationByHolidayTypeId($uid, $date, $holidayTypeId, $durationType, 'date');
    }

    /**
     * 按假期类型获取请假时长
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getLeaveDurationByHolidayTypeId(int $uid, string $time, int $holidayTypeId, int $durationType = 1, string $timeType = 'month'): string
    {
        $key = md5(json_encode(['uid' => $uid, 'time' => $time, 'time_type' => $timeType, 'holiday_type_id' => $holidayTypeId, 'duration_type' => $durationType, 'version' => 3]));
        return Cache::tags([self::CACHE_KEY])->remember($key, (int) sys_config('system_cache_ttl', 3600), function () use ($uid, $time, $holidayTypeId, $durationType, $timeType) {
            if ($durationType == 1) {
                return sprintf('%.2f', $this->dao->sumApproved(['uid' => $uid, $timeType => $time, 'holiday_type_id' => $holidayTypeId], 'leave_duration'));
            }
            return $this->getDurationByHolidayType($uid, $time, $holidayTypeId, $timeType);
        });
    }

    /**
     * 获取请假天数.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getDurationByHolidayType(int $uid, string $time, int $holidayTypeId, string $timeType = 'month'): string
    {
        $duration             = '0.00';
        $list                 = $this->dao->selectApproved(['uid' => $uid, $timeType => $time, 'holiday_type_id' => $holidayTypeId], ['id', 'statistics_id', 'leave_duration'], ['statistics']);
        $durationByStatistics = [];
        foreach ($list as $item) {
            if ($item?->statistics?->required_work_hours) {
                $statisticsId = (int) $item->statistics_id;
                if (! isset($durationByStatistics[$statisticsId])) {
                    $durationByStatistics[$statisticsId] = [
                        'leave_duration'      => '0.00',
                        'required_work_hours' => (string) $item->statistics->required_work_hours,
                    ];
                }
                $durationByStatistics[$statisticsId]['leave_duration'] = bcadd($durationByStatistics[$statisticsId]['leave_duration'], (string) $item->leave_duration, 2);
            }
        }

        foreach ($durationByStatistics as $item) {
            $duration = bcadd($duration, $this->formatDayDuration($item['leave_duration'], $item['required_work_hours']), 2);
        }

        return $duration;
    }

    /**
     * 按统计记录获取请假天数，最小单位半天.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getLeaveDaysByStatistics(array|int $uid, string $time, string $timeType = 'month'): array
    {
        $key = md5(json_encode(['uid' => $uid, 'time' => $time, 'time_type' => $timeType, 'version' => 1]));
        return Cache::tags([self::CACHE_KEY])->remember($key, (int) sys_config('system_cache_ttl', 3600), function () use ($uid, $time, $timeType) {
            $list                 = $this->dao->selectApproved(['uid' => $uid, $timeType => $time], ['id', 'statistics_id', 'leave_duration'], ['statistics']);
            $durationByStatistics = [];
            foreach ($list as $item) {
                if ($item?->statistics?->required_work_hours) {
                    $statisticsId = (int) $item->statistics_id;
                    if (! isset($durationByStatistics[$statisticsId])) {
                        $durationByStatistics[$statisticsId] = [
                            'leave_duration'      => '0.00',
                            'required_work_hours' => (string) $item->statistics->required_work_hours,
                        ];
                    }
                    $durationByStatistics[$statisticsId]['leave_duration'] = bcadd($durationByStatistics[$statisticsId]['leave_duration'], (string) $item->leave_duration, 2);
                }
            }

            $days = [];
            foreach ($durationByStatistics as $statisticsId => $item) {
                $days[$statisticsId] = $this->formatDayDuration($item['leave_duration'], $item['required_work_hours']);
            }

            return $days;
        });
    }

    /**
     * 按天统计时，假期最小统计单位为半天.
     */
    private function formatDayDuration(string $leaveDuration, string $requiredWorkHours): string
    {
        if (bccomp($leaveDuration, '0', 2) <= 0 || bccomp($requiredWorkHours, '0', 2) <= 0) {
            return '0.00';
        }

        $duration = round(((float) $leaveDuration / (float) $requiredWorkHours) * 2, 0, PHP_ROUND_HALF_UP) / 2;
        if ($duration <= 0) {
            $duration = 0.5;
        }

        return sprintf('%.2f', $duration);
    }

    /**
     * 按天获取请假时长
     * @throws BindingResolutionException
     */
    public function getLeaveDurationByDate(int $uid, string $time, string $type = 'date'): string
    {
        $key = md5(json_encode(['uid' => $uid, 'time' => $time, 'type' => $type, 'version' => 2]));
        return Cache::tags([self::CACHE_KEY])->remember($key, (int) sys_config('system_cache_ttl', 3600), function () use ($uid, $time, $type) {
            return sprintf('%.2f', $this->dao->sumApproved(['uid' => $uid, $type => $time], 'leave_duration'));
        });
    }

    /**
     * 根据月份获取假期类型.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getHolidayTypeIdByMonth(string $month, array|int $uid): array
    {
        return $this->dao->getHolidayTypeIds(['month' => $month, 'uid' => $uid]);
    }

    /**
     * 根据日期获取假期类型.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getHolidayTypeIdByDate(array|string $date, array|int $uid): array
    {
        return $this->dao->getHolidayTypeIds(['date' => $date, 'uid' => $uid]);
    }
}
