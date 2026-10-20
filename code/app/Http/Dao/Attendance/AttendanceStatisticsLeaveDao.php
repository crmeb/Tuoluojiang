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

namespace App\Http\Dao\Attendance;

use App\Http\Model\Attendance\AttendanceStatisticsLeave;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;

/**
 * 请假工时Dao
 * Class AttendanceStatisticsLeaveDao.
 */
class AttendanceStatisticsLeaveDao extends BaseDao
{
    use ListSearchTrait;
    use TogetherSearchTrait;

    /**
     * 获取假期类型.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getHolidayTypeIds(array $where): array
    {
        return $this->approvedSearch($where)->distinct()->select(['holiday_type_id'])->get()->map(function ($item) {
            return $item['holiday_type_id'];
        })->filter()->all();
    }

    /**
     * 统计已通过审批的请假明细.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sumApproved(array $where, string $field): mixed
    {
        return $this->approvedSearch($where)->sum($field) ?: 0.00;
    }

    /**
     * 查询已通过审批的请假明细.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function selectApproved($where = [], array $field = [], array $with = [], int $page = 0, int $limit = 0, bool $cursor = false)
    {
        $query = $this->approvedSearch($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->select($field ?: '*');

        return $cursor ? $query->cursor() : $query->get();
    }

    /**
     * 只读取仍处于已通过状态的审批申请，避免撤回残留明细进入统计.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function approvedSearch(array $where): Builder
    {
        return $this->search($where)->whereHas('applyRecord.approveApply', function ($query) {
            $query->where('status', 1);
        });
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return AttendanceStatisticsLeave::class;
    }
}
