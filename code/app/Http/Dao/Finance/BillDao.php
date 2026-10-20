<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Dao\Finance;

use App\Http\Model\Finance\Bill;
use App\Http\Service\Finance\BillCategoryService;
use Carbon\Carbon;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Tuoluojiang\Open\utils\Statistics;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 资金流水记录
 * Class BillDao.
 * @mixin BaseDao
 */
class BillDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;
    use TogetherSearchTrait;
    use BatchSearchTrait;

    /**
     * 基础查询列表(通用).
     *
     * @param array $where 条件
     * @param array|string[] $field 显示字段
     * @param int $page 页码
     * @param int $limit 展示条数
     * @param null|array|string $sort 排序
     * @param array $with 关联
     *
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        return $this->setTimeField('edit_time')->search($where)->select($field)->when($page && $limit, function ($query) use ($page, $limit) {
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
        })->with($with)->get()->toArray();
    }

    /**
     * 资金记录时间查询.
     *
     * @param mixed $entid
     * @param mixed $cateId
     * @param mixed $time
     * @param mixed $timeType
     * @param mixed $str
     * @param mixed $types
     *
     * @return array
     * @throws BindingResolutionException
     */
    public function getTrend($time, $timeType, $str, $types, $entid, $cateId = [], array $userIds = [])
    {
        return $this->getModel()->where(function ($query) use ($time, $types) {
            if ($time[0] == $time[1]) {
                $query->whereDate('edit_time', $time[0]);
            } else {
                $query->whereBetween('edit_time', [Carbon::make($time[0])->toDateTimeString(), Carbon::make($time[1])->endOfDay()->toDateTimeString()]);
            }
            $query->where('types', $types);
        })->when($cateId, function ($q) use ($cateId) {
            $q->whereIn('cate_id', $cateId);
        })->when($userIds, function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })->where('entid', $entid)->selectRaw("DATE_FORMAT(edit_time,'{$timeType}') as days,{$str} as num")
            ->groupBy('days')->get()->toArray();
    }

    /**
     * 资金记录排行.
     * @param mixed $time
     * @param mixed $types
     * @param mixed $entid
     * @param mixed $level
     * @param mixed $sum
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getBillRank($time, $types, $entid, $level = 0, $sum = 0, array|int $cateId = 0, array $cateSearch = [], array $userIds = [])
    {
        $cateService = app()->get(BillCategoryService::class);
        $where       = ['types' => $types];
        if ($cateId || ! $level) {
            $where['pid'] = $cateId;
        } else {
            ! $cateSearch && $where['level'] = $level;
        }
        $cateIds = $cateService->getSearchCate($cateId, $types);
        $list    = [];
        foreach ($cateIds as $v) {
            if ($cateSearch && ! in_array($v['id'], $cateSearch)) {
                continue;
            }
            $num = $this->getModel()->where(function ($query) use ($time) {
                if ($time[0] == $time[1]) {
                    $query->whereDate('edit_time', $time[0]);
                } else {
                    $query->whereBetween('edit_time', [Carbon::make($time[0])->toDateTimeString(), Carbon::make($time[1])->endOfDay()->toDateTimeString()]);
                }
            })->when($entid, function ($query) use ($entid) {
                $query->where('entid', $entid);
            })->when($userIds, function ($query) use ($userIds) {
                $query->whereIn('user_id', $userIds);
            })->when($v['id'], function ($query) use ($cateService, $cateId, $v, $cateSearch) {
                if ($cateId && $cateId == $v['id']) {
                    $query->where('cate_id', $cateId);
                } else {
                    $ids = $cateSearch ? array_intersect($cateSearch, $cateService->getSubCateIdByCache($v['id'], true)) : $cateService->getSubCateIdByCache($v['id'], true);
                    $query->whereIn('cate_id', $ids);
                }
            })->where('types', $types)->sum('num');
            //            if ($num) {
            $list[] = [
                'cate_id' => $v['id'],
                'name'    => $v['name'],
                'sum'     => $num,
            ];
            //            }
            unset($num);
        }
        if ($list) {
            $list  = Statistics::calcRatio($list, 'sum', 'cate_id', 'ratio', (float) $sum);
            $ratio = array_column($list, 'ratio');
            array_multisort($ratio, SORT_DESC, $list);
        }
        return $list;
    }

    /**
     * 资金记录合计
     *
     * @param mixed $time
     * @param mixed $types
     * @param mixed $level
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function getSum($time, $types, $level = 0, int $entId = 0, array|int $cateId = 0, array $userIds = [])
    {
        $cateService = app()->get(BillCategoryService::class);
        return $this->getModel()->where(function ($query) use ($time) {
            if ($time[0] == $time[1]) {
                $query->whereDate('edit_time', $time[0]);
            } else {
                $query->whereBetween('edit_time', [Carbon::make($time[0])->toDateTimeString(), Carbon::make($time[1])->endOfDay()->toDateTimeString()]);
            }
        })->when($entId, function ($query) use ($entId) {
            $query->where('entid', $entId);
        })->when($userIds, function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })->when($level, function ($query) use ($level, $cateService) {
            $query->whereIn('cate_id', $cateService->column(['gt_level' => $level], 'id'));
        })->when($cateId || ! $level, function ($query) use ($cateId, $cateService) {
            $query->whereIn('cate_id', is_array($cateId) ? $cateId : $cateService->getSubCateIdByCache($cateId, true));
        })->where('types', $types)->sum('num');
    }

    /**
     * 搜索.
     *
     * @param array|int|string $where
     *
     * @return BaseModel|BuildsQueries|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function search($where, ?bool $authWhere = null)
    {
        $name  = $where['name_like'] ?? '';
        $uid   = $where['uid_like'] ?? [];
        $entId = $where['entid'] ?? 0;
        if (isset($where['name_like'])) {
            unset($where['name_like']);
        }
        if (isset($where['uid_like'])) {
            unset($where['uid_like']);
        }
        return parent::search($where, $authWhere)->where(function ($query) use ($name, $uid, $entId) {
            $query->when($name, function ($query) use ($name, $uid, $entId) {
                $query->orWhere('num', 'like', '%' . $name . '%')->orWhere('mark', 'like', '%' . $name . '%')
                    ->orWhereIn('link_id', function ($q) use ($name) {
                        $q->from('client_bill')->select('id')->where('mark', 'like', '%' . $name . '%');
                    })
                    ->when($uid, function ($query) use ($uid) {
                        $query->orWhereIn('uid', $uid);
                    })->orWhereIn('cate_id', function ($query) use ($name, $entId) {
                        $query->from('bill_category')->select(['id'])
                            ->where('entid', $entId)->where('name', 'like', '%' . $name . '%');
                    });
            });
        });
    }

    /**
     * 获取账目流水（按日分组）.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function listByDate(array $where, array $field, int $page, int $limit, mixed $sort, array $with = [])
    {
        $category = app(BillCategoryService::class);
        return $this->setTimeField('edit_time')->search($where)
            ->selectRaw("DATE_FORMAT(edit_time,'%Y-%m-%d') as days")->groupBy('days')->orderByDesc('days')
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })->when($limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->get()->each(function (&$value) use ($where, $field, $sort, $with, $category) {
                $where['time'] = Carbon::make($value['days'])->startOfDay()->format('Y/m/d H:i:s') . '-' . Carbon::make($value['days'])->endOfDay()->format('Y/m/d H:i:s');
                $list          = $this->getList($where, $field, 0, 0, $sort, $with);
                $income        = $expend = 0;
                foreach ($list as &$v) {
                    if ($v['types']) {
                        $income = bcadd((string) $income, (string) $v['num'], 2);
                    } else {
                        $expend = bcadd((string) $expend, (string) $v['num'], 2);
                    }
                    if ($v['cate']) {
                        if ($v['cate']['path']) {
                            $name      = implode('/', $category->column(['ids' => $v['cate']['path']], 'name'));
                            $v['cate'] = $name ? $name . '/' . $v['cate']['name'] : $v['cate']['name'];
                            unset($name);
                        } else {
                            $v['cate'] = $v['cate']['name'];
                        }
                    } else {
                        $v['cate'] = '';
                    }
                }
                $value['income'] = (float) $income;
                $value['expend'] = (float) $expend;
                $value['profit'] = (float) bcsub((string) $income, (string) $expend, 2);
                $value['data']   = $list;
            })?->toArray();
    }

    /**
     * 资金记录合计
     *
     * @param mixed $time
     * @param mixed $types
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function getSumSearch($time, $types, array|int $cateId = 0)
    {
        $cateService = app()->get(BillCategoryService::class);
        return $this->getModel()->where(function ($query) use ($time) {
            if ($time[0] == $time[1]) {
                $query->whereDate('edit_time', $time[0]);
            } else {
                $query->whereBetween('edit_time', [Carbon::make($time[0])->toDateTimeString(), Carbon::make($time[1])->endOfDay()->toDateTimeString()]);
            }
        })->when($cateId, function ($query) use ($cateId, $cateService) {
            $query->whereIn('cate_id', is_array($cateId) ? $cateId : $cateService->getSubCateIdByCache($cateId, true));
        })->when(true, function ($query) use ($types) {
            is_array($types) ? $query->whereIn('types', $types) : $query->where('types', $types);
        });
    }

    /**
     * @return mixed|string
     */
    protected function setModel()
    {
        return Bill::class;
    }
}
