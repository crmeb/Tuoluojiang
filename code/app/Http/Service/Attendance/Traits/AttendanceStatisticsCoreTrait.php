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

use App\Http\Service\Admin\AdminService;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Attendance\AttendanceArrangeService;
use App\Http\Service\Attendance\AttendanceClockService;
use App\Http\Service\Attendance\AttendanceGroupService;
use App\Http\Service\Attendance\AttendanceShiftService;
use App\Http\Service\Attendance\CalendarConfigService;
use App\Http\Service\Frame\FrameService;
use Carbon\Carbon;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤统计核心数据方法.
 */
trait AttendanceStatisticsCoreTrait
{
    /**
     * 打卡记录数据.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUserRecordByDate(int $uid, string $date): mixed
    {
        $info = $this->dao->getByUidDate($uid, $date);
        if ($info) {
            return $info;
        }

        // 考勤班次
        $shiftService = app()->get(AttendanceShiftService::class);

        $isWhitelist = app()->get(AttendanceGroupService::class)->isWhitelist($uid);
        if (! $isWhitelist) {
            // 考勤班次
            $clockGroup = app()->get(AttendanceClockService::class)->getClockBasicByUid($uid, $date);
            $shift      = $shiftService->getArrangeShiftById($clockGroup['shift_id'], $date);
        } else {
            $shift      = [];
            $clockGroup = ['group_id' => 0, 'group_name' => '', 'shift_id' => 0];
        }
        return (object) [
            'uid'                         => $uid,
            'frame_id'                    => app()->get(FrameService::class)->getFrameIdByUserId($uid),
            'group_id'                    => $clockGroup['group_id'],
            'group'                       => $clockGroup['group_name'],
            'shift_id'                    => $clockGroup['shift_id'],
            'shift_data'                  => $shift,
            'required_work_hours'         => $shiftService->getRequiredAttendanceHours($shift, $date),
            'one_shift_time'              => null,
            'one_shift_is_after'          => 0,
            'one_shift_status'            => 0,
            'one_shift_location_status'   => 0,
            'one_shift_record_id'         => 0,
            'two_shift_time'              => null,
            'two_shift_is_after'          => 0,
            'two_shift_status'            => 0,
            'two_shift_location_status'   => 0,
            'two_shift_record_id'         => 0,
            'three_shift_time'            => null,
            'three_shift_is_after'        => 0,
            'three_shift_status'          => 0,
            'three_shift_location_status' => 0,
            'three_shift_record_id'       => 0,
            'four_shift_time'             => null,
            'four_shift_is_after'         => 0,
            'four_shift_status'           => 0,
            'four_shift_location_status'  => 0,
            'four_shift_record_id'        => 0,
        ];
    }

    /**
     * 更新考勤统计数据.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function renewStatistics(int $uid, Carbon $dateObj): mixed
    {
        $tz   = config('app.timezone');
        $date = $dateObj->toDateString();

        // 首先检查打卡时间是否属于其他日期的班次（跨天打卡处理）
        $crossDayRecord = $this->determineClockStatisticsDate($uid, $dateObj, $date, $tz);
        if ($crossDayRecord) {
            return $crossDayRecord;
        }

        $info = $this->dao->getByUidDate($uid, $date);
        if ($info) {
            return $info;
        }

        // 检查跨天打卡（检查前一天是否有未完成的跨天班次）
        $crossDayStatistics = $this->checkCrossDayAttendance($uid, $dateObj, $date, $tz);
        if ($crossDayStatistics) {
            return $crossDayStatistics;
        }

        return $this->createStatistics($uid, $date);
    }

    /**
     * 按指定考勤归属日期精确获取/生成统计数据.
     *
     * 实时打卡可自动匹配跨天时间窗；但前端已经明确传入归属日期时，
     * 需要绕过自动匹配，避免夜班边界再次落到相邻日期。
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function renewStatisticsByDate(int $uid, string $date): mixed
    {
        $date = Carbon::parse($date, config('app.timezone'))->toDateString();
        $info = $this->dao->getByUidDate($uid, $date);
        if ($info) {
            return $info;
        }

        return $this->createStatistics($uid, $date);
    }

    /**
     * 批量获取/生成打卡统计数据.
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function batchRenewStatistics(array $uid, array $date): array
    {
        // 生成所有需验证的组合
        $allCombinations = collect($uid)->crossJoin(collect($date)); // 格式：[[uid1, date1], [uid1, date2], ...]
        $allKeys         = $allCombinations->map(fn ($item) => "{$item[0]}_{$item[1]}"); // 生成唯一标识：uid_date
        // 查询已有数据
        $existingData = collect($this->dao->select(['uid' => $uid, 'date' => $date])?->toArray() ?? [])->map(function ($item) {
            $item['date'] = Carbon::parse($item['created_at'])->toDateString();
            return $item;
        });
        // 数据按uid_date分组
        $existingKeys = $existingData->map(function ($item) {
            return "{$item['uid']}_{$item['date']}";
        })->values();
        $existingMap = $existingData->keyBy(fn ($item) => "{$item['uid']}_{$item['date']}");
        // 找出缺失
        $missingKeys         = $allKeys->diff($existingKeys); // 差集：需创建的uid_date
        $missingCombinations = $allCombinations->filter(function ($item) use ($missingKeys) {
            $key = "{$item[0]}_{$item[1]}";
            return $missingKeys->contains($key) && $this->canCreateAttendanceStatistics((int) $item[0]);
        });
        // 创建缺失的数据
        $missingCombinations->each(function ($item) use ($existingMap) {
            [$uid, $date] = $item;
            try {
                $createResult = $this->createStatistics($uid, $date);
                if ($createResult && ! isset($createResult['date'])) {
                    $createResult['date'] = $date;
                }
                if (! $createResult) {
                    Log::error("用户[{$uid}]日期[{$date}]的考勤统计数据创建失败");
                }
                $existingMap->put("{$uid}_{$date}", $createResult);
            } catch (UniqueConstraintViolationException $e) {
                // 并发情况下可能已被其他进程创建，重新查询获取
                $existing = $this->dao->getByUidDate($uid, $date)?->toArray();
                if ($existing) {
                    if (! isset($existing['date'])) {
                        $existing['date'] = $date;
                    }
                    $existingMap->put("{$uid}_{$date}", $existing);
                }
            }
        });
        // 返回全量数据
        return $allCombinations->map(function ($item) use ($existingMap) {
            $key = "{$item[0]}_{$item[1]}";
            return $existingMap->get($key);
        })->filter()->keyBy(function ($item) {
            return "{$item['uid']}_{$item['date']}";
        })->all();
    }

    /**
     * 生成员工打卡数据.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function generateMemberStatistics(string $date): void
    {
        // attendance group arrange record
        app()->get(AttendanceArrangeService::class)->generateAttendGroupRecord($date);

        // attendance members
        $groupService = app()->get(AttendanceGroupService::class);
        $members = array_intersect(
            app()->get(AdminService::class)->column(['status' => 1], 'id'),
            app()->get(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id'),
            $groupService->getAttendanceMemberIds()
        );

        // default shift id
        $defaultShiftId = app()->get(CalendarConfigService::class)->dayIsRest($date) ? 1 : 2;

        $dateObj     = Carbon::parse($date, config('app.timezone'));
        $date        = $dateObj->startOfDay()->toDateString();
        $arrangeDate = $dateObj->startOfMonth()->toDateString();

        foreach ($members as $member) {
            # generate arrange record
            $this->generateArrange($member, $date, $defaultShiftId, $arrangeDate);

            # generate statistics
            $this->generateStatistics($member, $date);
        }
    }

    /**
     * 重新生成打卡数据（排班变更后调用）
     * 删除现有数据并重新创建.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function regenerateStatistics(int $uid, string $date): void
    {
        if (! $this->canCreateAttendanceStatistics($uid)) {
            return;
        }

        try {
            // 删除现有记录
            $this->dao->deleteByUidDate($uid, $date);
            // 重新创建
            $this->createStatistics($uid, $date);
        } catch (\Throwable $e) {
            Log::error($uid . '重新生成打卡数据失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'uid' => $uid]);
        }
    }

    /**
     * 批量重新生成指定日期范围的打卡数据（排班变更后调用）.
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function batchRegenerateStatistics(array $uid, string $startDate, string $endDate): void
    {
        $tz      = config('app.timezone');
        $start   = Carbon::parse($startDate, $tz);
        $end     = Carbon::parse($endDate, $tz);
        $current = $start->copy();

        while ($current->lte($end)) {
            $date = $current->toDateString();

            foreach ($uid as $userId) {
                try {
                    $this->regenerateStatistics($userId, $date);
                } catch (\Throwable $e) {
                    Log::error("重新生成考勤数据失败[uid:{$userId}, 日期:{$date}]：" . $e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }
            }

            $current->addDay();
        }
    }

    /**
     * 创建打卡数据.
     *
     * @return \BaseModel|Model
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    protected function createStatistics(int $uid, string $date): mixed
    {
        if ($info = $this->dao->getByUidDate($uid, $date)) {
            return $info;
        }

        if (! $this->canCreateAttendanceStatistics($uid)) {
            return null;
        }

        $shiftService = app()->get(AttendanceShiftService::class);
        $isWhitelist  = app()->get(AttendanceGroupService::class)->isWhitelist($uid);
        if (! $isWhitelist) {
            // 考勤班次
            $clockGroup = app()->get(AttendanceClockService::class)->getClockBasicByUid($uid, $date);
            $shift      = $shiftService->getArrangeShiftById($clockGroup['shift_id'], $date);
        } else {
            $shift      = [];
            $clockGroup = ['group_id' => 0, 'group_name' => '', 'shift_id' => 0];
        }
        $data = [
            'uid'                 => $uid,
            'frame_id'            => app()->get(FrameService::class)->getFrameIdByUserId($uid),
            'group_id'            => $clockGroup['group_id'],
            'group'               => $clockGroup['group_name'],
            'shift_id'            => $clockGroup['shift_id'],
            'shift_data'          => $shift,
            'required_work_hours' => ! $isWhitelist ? $shiftService->getRequiredAttendanceHours($shift, $date) : '0.0',
            'created_at'          => $date,
        ];
        try {
            return $this->dao->create($data);
        } catch (\Throwable $e) {
            if ($this->isStatisticsUniqueConflict($e) && ($info = $this->dao->getByUidDate($uid, $date))) {
                return $info;
            }

            throw $e;
        }
    }

    /**
     * 判断是否为考勤统计唯一约束并发冲突.
     */
    protected function isStatisticsUniqueConflict(\Throwable $e): bool
    {
        if ($e instanceof UniqueConstraintViolationException) {
            return true;
        }

        if ($e instanceof QueryException) {
            $driverCode = $e->errorInfo[1] ?? null;
            return $driverCode === 1062 || (string) $e->getCode() === '23000';
        }

        return str_contains($e->getMessage(), 'uniq_attendance_statistics_uid_date_active');
    }

    /**
     * 生成打卡数据.
     */
    protected function generateStatistics(int $uid, string $date): void
    {
        try {
            if ($this->canCreateAttendanceStatistics($uid) && ! $this->dao->existsByUidDate($uid, $date)) {
                $this->createStatistics($uid, $date);
            }
        } catch (\Throwable $e) {
            Log::error($uid . '打卡数据创建失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'uid' => $uid]);
        }
    }

    /**
     * 生成排班数据.
     */
    protected function generateArrange(int $uid, string $date, int $shiftId, string $arrangeDate): void
    {
        try {
            app()->get(AttendanceArrangeService::class)->generateAttendanceRecordByMember($uid, $shiftId, $date, $arrangeDate);
        } catch (\Throwable $e) {
            Log::error($uid . '默认排班数据创建失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'uid' => $uid, 'date' => $date]);
        }
    }

    protected function isDepartedUser(int $uid): bool
    {
        return app()->get(AdminInfoService::class)->value($uid, 'type') == 4;
    }

    protected function canCreateAttendanceStatistics(int $uid): bool
    {
        return ! $this->isDepartedUser($uid) && (bool) app()->get(AttendanceGroupService::class)->getMemberClockGroup($uid, 0, false, true);
    }
}
