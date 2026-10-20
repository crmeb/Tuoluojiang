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

namespace Tuoluojiang\Open\traits\dao;

use App\Http\Dao\BaseDao;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;

/**
 * 时间分组查询
 * Trait GroupDateSearchTrait.
 * @mixin  BaseDao
 */
trait GroupDateSearchTrait
{
    /**
     * 获取按照规定时间分组后的列表.
     * @param int $type 日期分组类型 1=年;2=月;3=日;4=周
     * @param string $group 分组字段
     * @param array $where 条件
     * @param array|string[] $field
     * @param null $sort
     * @return mixed
     */
    public function getDateGroupList(int $type, string $group, array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [], ?callable $callable = null)
    {
        $field[] = DB::raw('YEAR(' . $group . ') as year');
        $field[] = DB::raw('MONTH(' . $group . ') as month');
        $field[] = DB::raw('DAY(' . $group . ') as day');
        $field[] = DB::raw('WEEK(' . $group . ') as week');

        return $this->search($where)->select($field)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->when($limit, function ($query) use ($limit) {
            $query->limit($limit);
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
        })->when($type, function ($query) use ($type) {
            switch ($type) {
                case 1:// 年
                    $query->groupByRaw('year');
                    break;
                case 2:// 月
                    $query->groupByRaw('month');
                    break;
                case 3:// 日
                    $query->groupByRaw('day');
                    break;
                case 4:// 周
                    $query->groupByRaw('week');
                    break;
            }
        })->with($with)->get()->when($callable, fn ($query) => $query->each($callable))->toArray();
    }

    /**
     * 获取重复记录ID.
     * @param mixed $group
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRepeatIds($group = ['role_id', 'entid', 'user_id'], array $where = [])
    {
        return $this->search($where)->select([DB::raw('GROUP_CONCAT(id) as repeat_id')])->groupBy($group)->get()->map(function ($q) {
            $res = explode(',', $q->repeat_id);
            if (count($res) > 1) {
                return $res;
            }
        })->filter()->toArray();
    }
}
