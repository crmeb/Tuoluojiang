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

namespace App\Http\Dao\Schedule;

use App\Http\Model\Schedule\ScheduleRemind;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Traits\Conditionable;

/**
 * 日程提醒表.
 */
class ScheduleRemindDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getEntList(): array
    {
        return toArray($this->search(['end_time_not' => true])
            ->where('entid', '<>', 0)
            ->groupBy('entid')
            ->select(['entid'])
            ->get());
    }

    /**
     * @return BaseModel|Conditionable|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function selectModel($where, array $field = [], array $with = [])
    {
        $completed = $where['completed'] ?? false;
        if ($completed) {
            unset($where['completed']);
        }
        $uid = $where['uid'] ?? '';

        return $this->search($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->when($this->defaultSort, function ($query) {
            if (is_array($this->defaultSort)) {
                foreach ($this->defaultSort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->orderByDesc($v);
                    } else {
                        $query->orderBy($k, $v);
                    }
                }
            } else {
                $query->orderByDesc($this->defaultSort);
            }
        })->when($completed && $uid, function ($query) use ($uid) {
            $query->whereIn('id', function ($query) use ($uid) {
                $query->from('schedule_record')->where('uid', $uid)->where('status', 1)
                    ->whereDate('updated_at', Carbon::today(config('app.timezone'))->toDateString())->select(['schedultid']);
            });
        })->select($field ?: '*');
    }

    protected function setModel()
    {
        return ScheduleRemind::class;
    }
}
