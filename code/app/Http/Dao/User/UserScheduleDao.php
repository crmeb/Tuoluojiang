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

namespace App\Http\Dao\User;

use App\Http\Model\Schedule\Schedule;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\RemindService;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Traits\Conditionable;

class UserScheduleDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * @return string
     */
    public function setModel()
    {
        return Schedule::class;
    }

    /**
     * 基础查询列表(通用).
     * @param array $where 条件
     * @param array|string[] $field 显示字段
     * @param int $page 页码
     * @param int $limit 展示条数
     * @param null|array|string $sort 排序
     * @param array $with 关联
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        $entid = $where['entid'];
        unset($where['entid']);
        $res = $this->search($where)->select($field)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->when($limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->when($entid, function ($query) use ($entid) {
            $query->where(function ($q) use ($entid) {
                $q->where('entid', $entid)->orWhere('entid', 0);
            });
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
        })->with($with)->get();
        $res->each(function ($item) {
            switch ($item['types']) {
                case 'client_track':// 客户跟踪
                    $item['title'] = app()->get(CustomerService::class)->value(['id' => $item['link_id']], 'name');
                    $item['link']  = '/customer/list';
                    break;
                case 'client_renew':// 客户续费
                case 'client_return':// 订单回款
                    $id            = app()->get(RemindService::class)->value(['id' => $item['link_id']], 'cid');
                    $item['title'] = app()->get(OrderService::class)->value(['id' => $id], 'title');
                    $item['link']  = '/customer/contract';
                    break;
                default:// 其他类型
                    $item['title'] = $item['content'];
                    $item['link']  = '';
                    break;
            }
        });
        return $res->toArray();
    }

    /**
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCount(array $where)
    {
        $entid = $where['entid'];
        unset($where['entid']);
        return $this->search($where)->when($entid, function ($query) use ($entid) {
            $query->where('entid', $entid)->orWhere('entid', 0);
        })->count();
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    public function getEntList()
    {
        return $this->search(['end_time_not' => true])
            ->where('entid', '<>', 0)
            ->groupBy('entid')
            ->select(['entid'])
            ->get()
            ->toArray();
    }

    /**
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUidList()
    {
        return $this->search(['end_time_not' => true])
            ->where('uid', '<>', '')
            ->groupBy('uid')
            ->select(['uid'])
            ->get()
            ->toArray();
    }

    /**
     * @param mixed $where
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
                $query->from('user_schedule_record')->where('uid', $uid)->where('status', 1)
                    ->whereDate('updated_at', Carbon::today(config('app.timezone'))->toDateString())->select(['schedultid']);
            });
        })->select($field ?: '*');
    }
}
