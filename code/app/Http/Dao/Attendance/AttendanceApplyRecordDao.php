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

use App\Constants\ApproveEnum;
use App\Http\Model\Attendance\AttendanceApplyRecord;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;

/**
 * 审批记录Dao
 * Class AttendanceApplyRecordDao.
 */
class AttendanceApplyRecordDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 获取加班次数.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCountGroupByUid(array $where): mixed
    {
        return $this->search($where)->selectRaw('`uid`, count(`id`) as `count`')->groupBy('uid')->distinct()->get();
    }

    /**
     * 查询指定时间范围内已通过审批的申请记录.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function selectApprovedInTimeRange(array $where, string $startTime, string $endTime, array $field = ['*']): mixed
    {
        return $this->search($where)
            ->where('start_time', '<=', $endTime)
            ->where('end_time', '>=', $startTime)
            ->whereHas('approveApply', function ($query) {
                $query->where('status', ApproveEnum::APPROVE_PASSED);
            })
            ->select($field ?: '*')
            ->get();
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
        $compareTime = $where['compare_time'] ?? '';
        if (isset($where['compare_time'])) {
            unset($where['compare_time']);
        }

        $overTime = $where['over_time'] ?? '';
        if (isset($where['over_time'])) {
            unset($where['over_time']);
        }
        return parent::search($where, $authWhere)->when($compareTime, function ($query) use ($compareTime) {
            $query->where('start_time', '<=', $compareTime)->where('end_time', '>', $compareTime);
        })->when($overTime, function ($query) use ($overTime) {
            $query->whereDate('start_time', $overTime);
        });
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return AttendanceApplyRecord::class;
    }
}
