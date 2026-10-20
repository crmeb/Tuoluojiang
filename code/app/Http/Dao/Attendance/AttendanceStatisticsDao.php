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

namespace App\Http\Dao\Attendance;

use App\Constants\AttendanceClockEnum;
use App\Http\Model\Attendance\AttendanceStatistics;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤统计Dao
 * Class AttendanceStatisticsDao.
 */
class AttendanceStatisticsDao extends BaseDao
{
    use ListSearchTrait;
    use TogetherSearchTrait;

    /**
     * 按用户和考勤归属日期获取统计记录，不走自动数据权限过滤.
     *
     * 打卡流程查询的是当前登录人的考勤基础数据，不能被后台数据权限拦截。
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getByUidDate(int $uid, string $date, array $field = ['*']): mixed
    {
        return $this->getModel(false)
            ->where('uid', $uid)
            ->whereDate('created_at', $date)
            ->select($field ?: ['*'])
            ->first();
    }

    /**
     * 判断用户指定考勤日期统计是否存在，不走自动数据权限过滤.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function existsByUidDate(int $uid, string $date): bool
    {
        return $this->getModel(false)
            ->where('uid', $uid)
            ->whereDate('created_at', $date)
            ->exists();
    }

    /**
     * 删除用户指定考勤日期统计，不走自动数据权限过滤.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function deleteByUidDate(int $uid, string $date): mixed
    {
        return $this->getModel(false)
            ->where('uid', $uid)
            ->whereDate('created_at', $date)
            ->delete();
    }

    /**
     *  团队列表.
     *
     * @param array $where 条件
     * @param array|string[] $field 显示字段
     * @param array $with 关联
     * @param int $page 页码
     * @param int $limit 展示条数
     * @param null|array|string $sort 排序
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getStatisticsMemberList(array $where = [], array $field = ['*'], array $with = [], int $page = 0, int $limit = 0, array|string|null $sort = null): mixed
    {
        return $this->search($where)->groupBy('uid')->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->when($sort, function ($query) use ($sort) {
            if (is_array($sort)) {
                foreach ($sort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->orderByDesc($v);
                    } else {
                        $query->orderBy($k, $v);
                    }
                }
            } else {
                $query->orderByDesc($sort);
            }
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->select($field ?: '*')->get();
    }

    /**
     * 人员数量统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCountByUid(array $where): int
    {
        return $this->search($where)->distinct('uid')->count();
    }

    /**
     * 搜索.
     *
     * @param array|int|string $where
     *
     * @return BaseModel|BuildsQueries|mixed
     * @throws \ReflectionException*@throws BindingResolutionException
     * @throws BindingResolutionException
     */
    public function search($where, ?bool $authWhere = null): mixed
    {
        $status = $where['personnel_status'] ?? '';
        if (is_array($status)) {
            foreach ($status as $clockStatus) {
                if (in_array($clockStatus, AttendanceClockEnum::SAME_CLOCK)) {
                    $where['status'][] = $clockStatus;
                }

                if ($clockStatus == 5) {
                    $where['status'] = array_merge($where['status'] ?? [], AttendanceClockEnum::ALL_LACK_CARD);
                }

                if ($clockStatus == 6) {
                    $where['location_status'] = AttendanceClockEnum::OFFICE_ABNORMAL;
                }
            }
        } else {
            if (in_array($status, AttendanceClockEnum::SAME_CLOCK)) {
                $where['status'] = $status;
            }

            if ($status == 5) {
                $where['status'] = AttendanceClockEnum::ALL_LACK_CARD;
            }

            if ($status == 6) {
                $where['location_status'] = AttendanceClockEnum::OFFICE_ABNORMAL;
            }
        }

        if (isset($where['personnel_status'])) {
            unset($where['personnel_status']);
        }

        $scope = $where['scope'] ?? '';
        if (isset($where['scope'])) {
            unset($where['scope']);
        }

        // 查询缺卡数据
        $lackCardWithShiftNum = $where['lack_card_with_shift_num'] ?? '';
        if (isset($where['lack_card_with_shift_num'])) {
            unset($where['lack_card_with_shift_num']);
        }

        // 补卡条件
        $repairCondition = $where['repair_condition'] ?? [];
        if (isset($where['repair_condition'])) {
            unset($where['repair_condition']);
        }

        // 请假范围
        $holidayTime = $where['holiday_time'] ?? '';
        if (isset($where['holiday_time'])) {
            unset($where['holiday_time']);
        }
        return parent::search($where, $authWhere)->when($scope, function ($query) use ($scope) {
            $query->{$scope == 1 ? 'whereNotIn' : 'whereIn'}('uid', function ($query) {
                $query->from('admin_info')->where('admin_info.type', 4)->select(['admin_info.id']);
            });
        })->when($lackCardWithShiftNum, function ($query) use ($lackCardWithShiftNum) {
            $query->where(function ($query) use ($lackCardWithShiftNum) {
                $shifts = AttendanceClockEnum::SHIFT_CLASS;
                for ($i = 0; $i < $lackCardWithShiftNum * 2; ++$i) {
                    $query->orWhere(function ($query) use ($shifts, $i) {
                        $query->whereNull($shifts[$i] . '_shift_time');
                    });
                }
            });
        })->when($repairCondition, function ($query) use ($repairCondition) {
            $query->where(function ($query) use ($repairCondition) {
                if (isset($repairCondition['status'])) {
                    $query->where(function ($query) use ($repairCondition) {
                        $query->whereIn('one_shift_status', $repairCondition['status'])
                            ->orWhereIn('two_shift_status', $repairCondition['status'])
                            ->orWhereIn('three_shift_status', $repairCondition['status'])
                            ->orWhereIn('four_shift_status', $repairCondition['status']);
                    });
                }
                if (isset($repairCondition['location_status'])) {
                    $query->orWhere(function ($query) use ($repairCondition) {
                        $query->where('one_shift_location_status', $repairCondition['location_status'])
                            ->orWhere('two_shift_location_status', $repairCondition['location_status'])
                            ->orWhere('three_shift_location_status', $repairCondition['location_status'])
                            ->orWhere('four_shift_location_status', $repairCondition['location_status']);
                    });
                }
            });
        })->when($holidayTime, function ($query) use ($holidayTime) {
            if (is_array($holidayTime)) {
                $query->whereDate('created_at', '>=', $holidayTime[0])->whereDate('created_at', '<=', $holidayTime[1]);
            } else {
                $query->whereDate('created_at', $holidayTime);
            }
        });
    }

    public function getRepeatId()
    {
        return $this->getModel()->selectRaw('MAX(id) as keep_id')->groupBy(['uid', 'created_at'])->havingRaw('COUNT(*) > 1')->pluck('keep_id')->toArray();
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return AttendanceStatistics::class;
    }
}
