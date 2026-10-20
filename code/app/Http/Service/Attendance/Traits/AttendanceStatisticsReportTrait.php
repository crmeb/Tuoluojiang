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
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * 考勤统计报表方法
 */
trait AttendanceStatisticsReportTrait
{
    /**
     * 月报统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMonthReport(string $uuid, string $date, int $type, int $userId): array
    {
        $period   = [];
        $tz       = config('app.timezone');
        $shifts   = AttendanceClockEnum::SHIFT_CLASS;
        $dateObj  = ($date ? Carbon::parse($date, $tz) : now($tz));
        $timeZone = \Carbon\CarbonPeriod::create($dateObj->startOfMonth()->toDateString(), $dateObj->endOfMonth()->toDateString())->toArray();
        foreach ($timeZone as $item) {
            $dateString          = $item->toDateString();
            $period[$dateString] = ['date' => $dateString, 'status' => 0];
        }

        $list = $this->dao->select([
            'month' => $dateObj->format('Y-m'),
            'uid'   => $type
                ? app()->get(AttendanceClockService::class)->getStatisticsUserId($uuid, $userId)
                : app()->get(AttendanceGroupService::class)->getTeamMember($uuid),
        ]);
        foreach ($list as $item) {
            if (array_key_exists($item->date, $period)) {
                if ($item->shift_id > 1) {
                    $status = 1;
                } else {
                    continue;
                }

                for ($i = 0; $i < $item->shift_data['number'] * 2; ++$i) {
                    if (
                        $item->{$shifts[$i] . '_status'} > 1
                        || $item->{$shifts[$i] . '_shift_status'} > 1
                        || $item->{$shifts[$i] . '_shift_location_status'} == AttendanceClockEnum::OFFICE_ABNORMAL
                    ) {
                        $status = 2;
                        break;
                    }
                }
                $period[$item->date]['status'] = max($period[$item->date]['status'], $status);
            }
        }
        return array_values($period);
    }

    /**
     * 月统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMonthStatistics(string $uuid, string $date = '', int $type = 0, int $userId = 0): array
    {
        $tz = config('app.timezone');
        if (! $date) {
            $dateObj = now($tz);
        } else {
            $dateObj = Carbon::parse($date, $tz);
        }

        if ($type) {
            [$clockStatistics, $overtimeStatistics, $leaveStatistics] = $this->getPersonMonthStatistics($uuid, $userId, $dateObj->format('Y-m'));
        } else {
            [$clockStatistics, $overtimeStatistics, $leaveStatistics] = $this->getTeamMonthStatistics($uuid, $dateObj->format('Y-m'));
        }

        $clockStatistics['deadline'] = '';
        $clockStatistics['normal']   = $clockStatistics['total'] - $clockStatistics['abnormal'];

        return [
            'clock_statistics'    => $clockStatistics,
            'overtime_statistics' => $overtimeStatistics,
            'leave_statistics'    => $leaveStatistics,
        ];
    }

    /**
     * 首页团队统计
     */
    public function getHomeTeamStatistics(string $uuid): array
    {
        return Cache::remember(md5(json_encode(['uuid' => $uuid, 'type' => 'team'])), 60, function () use ($uuid) {
            $date  = now(config('app.timezone'))->toDateString();
            $where = ['date' => $date, 'uid' => app()->get(AttendanceGroupService::class)->getTeamMember(uuid_to_uid($uuid))];
            return [
                'late'        => $this->dao->getCountByUid(array_merge($where, ['status' => AttendanceClockEnum::ALL_LATE])),
                'leave_early' => $this->dao->getCountByUid(array_merge($where, ['status' => AttendanceClockEnum::LEAVE_EARLY])),
                'lack_card'   => $this->dao->getCountByUid(array_merge($where, ['status' => AttendanceClockEnum::ALL_LACK_CARD])),
            ];
        });
    }

    /**
     * 首页个人统计
     */
    public function getHomePersonStatistics(string $uuid): array
    {
        return Cache::remember(md5(json_encode(['uuid' => $uuid, 'type' => 'person'])), 60, function () use ($uuid) {
            $uid   = uuid_to_uid($uuid);
            $month = now(config('app.timezone'))->format('Y-m');
            $where = ['month' => $month, 'uid' => $uid];
            return [
                'late'        => $this->dao->count(array_merge($where, ['status' => AttendanceClockEnum::ALL_LATE])),
                'leave_early' => $this->dao->count(array_merge($where, ['status' => AttendanceClockEnum::LEAVE_EARLY])),
                'lack_card'   => $this->getLackCardAndAbsenteeismNum($uid, $month)[1] ?? 0,
            ];
        });
    }
}
