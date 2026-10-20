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

namespace App\Http\Dao\Customer;

use App\Http\Model\Customer\Contract;
use App\Http\Model\Customer\Order;
use App\Http\Model\Customer\Payment;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 资金记录Dao.
 */
class PaymentDao extends BaseDao
{
    use ListSearchTrait;
    use JoinSearchTrait;
    use TogetherSearchTrait;

    /**
     * @param string[] $field
     * @param mixed $where
     * @param mixed $group
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupList($where, $group, array $field = ['*'], array $with = [], string $order = 'date')
    {
        $endDate = $where['end_date'] ?? '';
        if (isset($where['end_date'])) {
            unset($where['end_date']);
        }
        return $this->search($where)->when($with, function ($query) use ($with) {
            $query->with($with);
        })->when($endDate, function ($query) use ($endDate) {
            if (is_array($endDate)) {
                $query->whereBetween('end_date', $endDate);
            } else {
                $query->whereDate('end_date', $endDate);
            }
        })->when($order, function ($query) use ($order) {
            $query->orderBy($order, 'desc');
        })->groupBy($group)->select($field)->get();
    }

    /**
     * @param mixed $where
     * @param mixed $group
     * @param mixed $having
     * @param mixed $field
     * @return mixed
     * @throws \ReflectionException
     */
    public function getHaveList($where, $group, $having, $field = ['*'], array $with = [])
    {
        $types = $where['types'] ?? '';
        $type  = $where['type'] ?? '';
        if (isset($where['type'])) {
            unset($where['type']);
        }
        return $this->search($where)
            ->when($types && ! $type, function ($query) {
                $query->whereDate('end_date', '<>', '0000-00-00');
            })->orderBy('end_date', 'desc')
            ->groupBy($group)
            ->when($having && ! $type, function ($query) use ($having) {
                if (is_array($having)) {
                    $query->havingBetween('end_date', $having);
                } else {
                    $query->having('end_date', '<', $having);
                }
            })->when($types && $type, function ($query) use ($having) {
                $query->where(function (Builder $query) use ($having) {
                    $query->where(function ($query) {
                        $query->whereDate('end_date', '0000-00-00')->orWhereNull('end_date');
                    })->orWhere(function ($query) use ($having) {
                        $query->whereDate('end_date', '<>', '0000-00-00')->whereDate('end_date', '>', $having);
                    });
                });
            })->with($with)->select($field)->get();
    }

    /**
     * @param mixed $where
     * @return mixed
     */
    public function selectModel($where, array $field = [], array $with = [])
    {
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
        })->select($field ?: '*');
    }

    /**
     * 回款/合同订单续费合计
     * @param mixed $where
     */
    public function getSum($where, int $status = 1, string $field = 'num'): mixed
    {
        return $this->search($where)->where('status', $status)->sum($field);
    }

    /**
     * 关联搜索.
     * @return BaseModel|HigherOrderWhenProxy
     * @throws BindingResolutionException
     */
    public function getJoinSearch(array $where, array $with = [], array|string|null $group = null)
    {
        return $this->getJoinModel('cid', 'id')
            ->where($this->getFiled('status'), 1)
            ->when(isset($where['entid']) && $where['entid'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('entid'), $where['entid']);
            })->when(isset($where['uid']) && $where['uid'], function ($query) use ($where) {
                if (is_array($where['uid'])) {
                    $query->whereIn($this->getFiled('uid'), $where['uid']);
                } else {
                    $query->where($this->getFiled('uid'), $where['uid']);
                }
            })->when(isset($where['types']) && $where['types'], function ($query) use ($where) {
                if (is_array($where['types'])) {
                    $query->whereIn($this->getFiled('types'), $where['types']);
                } else {
                    $query->where($this->getFiled('types'), $where['types']);
                }
            })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                $query->whereBetween($this->getFiled('date'), explode('-', $where['date']));
            })->when(isset($where['end_date']) && $where['end_date'] !== '', function ($query) use ($where) {
                if (is_array($where['end_date'])) {
                    $query->whereBetween($this->getFiled('end_date'), $where['end_date']);
                } else {
                    $query->whereDate($this->getFiled('end_date'), $where['end_date']);
                }
            })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                if (is_array($where['status'])) {
                    $query->whereBetween($this->getFiled('status'), $where['status']);
                } else {
                    $query->where($this->getFiled('status'), $where['status']);
                }
            })->when($with, function ($query) use ($with) {
                $query->with($with);
            })->when($group, function ($query) use ($group) {
                $query->groupBy($group);
            })->when(isset($where['contract_category']) && $where['contract_category'], function ($query) use ($where) {
                $query->where(function ($q) use ($where) {
                    foreach ($where['contract_category'] as $v) {
                        $q->orWhereJsonContains($this->getFiled('contract_category', $this->aliasB), $v);
                    }
                });
            });
    }

    /**
     * 业绩排行榜.
     * @throws BindingResolutionException
     */
    public function getRankList(array $where, int $page, int $limit): array
    {
        $model  = $this->getJoinSearch($where);
        $sum    = $model->sum('num');
        $prefix = Config::get('database.connections.mysql.prefix');
        return $model
            ->selectRaw($prefix . $this->getFiled('id') . ', '
                        . $prefix . $this->getFiled('uid') . ','
                        . 'sum(' . $prefix . $this->getFiled('num') . ') as `price`')
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })->when($limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->with([
                'salesman' => fn ($query) => $query->withTrashed(),
                'frame',
            ])->groupBy($this->getFiled('uid'))
            ->orderByDesc(DB::raw('sum(' . $prefix . $this->getFiled('num') . ')'))
            ->get()->each(function ($item) use ($sum, $where) {
                $item['name']       = $item['salesman']['name'] ?? '';
                $item['avatar']     = $item['salesman']['avatar'] ?? '';
                $item['frame_name'] = $item['frame']['name'] ?? '';
                $item['expend']     = sprintf('%.2f', $this->getJoinSearch(['uid' => $item['uid'], 'date' => $where['date'], 'types' => 2])->sum('num'));
                $item['ratio']      = (float) bcmul(bcdiv($item['price'], (string) $sum, 4), '100', 2);
                $item['net_amount'] = sprintf('%.2f', bcsub((string) $item['price'], $item['expend'], 2));
                unset($item['salesman'], $item['frame']);
            })
            ->toArray();
    }

    /**
     * 排行榜数量.
     * @throws BindingResolutionException
     */
    public function getRankCount(array $where): int
    {
        $table = (new Payment())->getTable();

        return $this->getJoinSearch($where)->distinct()->count($table . '.uid');
    }

    /**
     * 待开票列表.
     * @throws \ReflectionException
     */
    public function unInvoiceList(array $param, int $page = 0, int $limit = 0, array $sort = ['date', 'id']): array
    {
        $eid       = $param['eid'] ?? 0;
        $entId     = $param['entid'] ?? 0;
        $invoiceId = $param['invoice_id'] ?? 0;
        $joinTable = 'client_invoice';
        $where     = ['client_bill.eid' => $eid, 'client_bill.status' => 1, 'client_bill.entid' => $entId];
        return $this->search($where)->whereIn('client_bill.types', [0, 1])->where(function ($query) use ($invoiceId, $joinTable) {
            $status = [-1, 2];
            $field  = $joinTable . '.status';
            $query->when(! $invoiceId, function ($query) use ($field, $status) {
                $query->orWhere('client_bill.invoice_id', '<', 1)->orWhereIn($field, $status);
            }, function ($query) use ($invoiceId, $field, $status) {
                $query->where('client_bill.invoice_id', $invoiceId)->whereIn($field, $status);
            });
        })->select(['client_bill.*', "{$joinTable}.status"])
            ->leftJoin($joinTable, fn ($q) => $q->on($joinTable . '.id', '=', 'client_bill.invoice_id'))
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })->when($limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->when($sort, function ($query) use ($sort) {
                foreach ($sort as $item) {
                    $query->orderByDesc($item);
                }
            })->with(['renew', 'card', 'treaty', 'client', 'attachs' => function ($query) {
                $query->select(['id', 'att_dir as src', 'relation_id', 'real_name']);
            }, 'invoice'])->get()->toArray();
    }

    /**
     * 搜索.
     * @param mixed $where
     * @return BaseModel|HigherOrderWhenProxy
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function search($where, ?bool $authWhere = null)
    {
        $name          = $where['name'] ?? '';
        $uidLike       = $where['uid_like'] ?? [];
        $filteredWhere = $where;
        if (is_array($filteredWhere)) {
            unset($filteredWhere['name'], $filteredWhere['uid_like']);
        }
        return parent::search($filteredWhere, $authWhere)->when($name !== '', function ($query) use ($name, $uidLike) {
            $query->where(function ($query) use ($name, $uidLike) {
                $query->orWhereIn('eid', function ($query) use ($name) {
                    $query->from('customer')->select(['id'])->where('customer_name', 'like', '%' . $name . '%');
                });
                $query->orWhereIn('cid', function ($query) use ($name) {
                    $query->from('contract')->select(['id'])
                        ->where('title', 'like', '%' . $name . '%')->orWhere('contract_no', 'like', '%' . $name . '%');
                });
                $query->orWhere('bill_no', 'like', '%' . $name . '%')->orWhere('mark', 'like', '%' . $name . '%')->orWhereIn('uid', $uidLike);
            });
        });
    }

    /**
     * 合计
     * @param mixed $where
     * @throws BindingResolutionException
     */
    public function getJoinSum($where): mixed
    {
        return $this->getJoinSearch($where)->sum($this->getFiled('num'));
    }

    /**
     * 获取账单合计.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getBillSum(array $where, string $field = 'num', string $group = 'eid')
    {
        return parent::search($where)->groupBy($group)->select($group)->selectRaw('SUM(' . $field . ') as total')->get();
    }

    protected function setModel(): string
    {
        return Payment::class;
    }

    /**
     * 设置副表模型.
     */
    protected function setModelB(): string
    {
        return Order::class;
    }

    private function getMonth($startDate, $endDate)
    {
        $startDate   = Carbon::parse($startDate, 'Asia/Shanghai'); // 开始时间
        $endDate     = Carbon::parse($endDate, 'Asia/Shanghai');   // 结束时间
        $months      = [];
        $currentDate = $startDate->copy(); // 复制开始日期，避免修改原实例
        // 循环遍历时间段，逐月添加
        while ($currentDate->lte($endDate)) {
            // 记录当前月份（格式：年-月，如 2023-03）
            $months[] = $currentDate->format('Y-m');
            // 跳到下一个月的第一天（避免月份天数差异导致的问题）
            $currentDate->addMonthNoOverflow()->firstOfMonth();
        }
        return $months;
    }
}
