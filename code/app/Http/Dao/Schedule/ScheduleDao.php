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

namespace App\Http\Dao\Schedule;

use App\Http\Model\Schedule\Schedule;
use App\Http\Model\Schedule\ScheduleTask;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\HigherOrderWhenProxy;

/**
 * 日程表.
 */
class ScheduleDao extends BaseDao
{
    use ListSearchTrait;
    use JoinSearchTrait;

    /**
     * 设置模型.
     * @return BaseModel
     * @throws BindingResolutionException
     */
    public function getModel(bool $need = true)
    {
        if ($need) {
            return $this->getJoinModel('id', 'pid');
        }
        return parent::getModel($need);
    }

    /**
     * 关联查询任务完成情况.
     * @param mixed $where
     * @param mixed $field
     * @return array|BaseModel[]|Collection|HigherOrderWhenProxy[]|\Illuminate\Database\Eloquent\Collection
     * @throws BindingResolutionException
     */
    public function getWithTask($where, $field = ['*'])
    {
        return $this->getModel()
            ->when($where['uid'], fn ($query) => $query->where($this->getFiled('uid', $this->aliasB), $where['uid']))
//            ->when($where['cid'], fn ($query) => $query->where($this->getFiled('cid'), $where['cid']))
            ->when($where['start_time'] && $where['end_time'], fn ($query) => $query->whereBetween($this->getFiled('updated_at', $this->aliasB), [$where['start_time'], $where['end_time']]))
//            ->when($where['start_time'], fn ($query) => $query->where($this->getFiled('start_time'), $where['start_time']))
//            ->when($where['end_time'], fn ($query) => $query->where($this->getFiled('end_time'), $where['end_time']))
            ->when($where['status'], fn ($query) => $query->where($this->getFiled('status', $this->aliasB), $where['status']))
            ->select($field)->get();
    }

    protected function setModel()
    {
        return Schedule::class;
    }

    protected function setModelB(): string
    {
        return ScheduleTask::class;
    }
}
