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

use App\Http\Model\Customer\Invoice;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 发票Dao.
 */
class InvoiceDao extends BaseDao
{
    use ListSearchTrait;
    use TogetherSearchTrait;

    /**
     * 发票查询过滤.
     * @param null|mixed $sort
     * @return Builder
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function searchInvoice(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        $eids     = $where['eids'] ?? [];
        $cids     = $where['cids'] ?? [];
        $name     = $where['name_like'] ?? '';
        $invoiced = $where['invoiced'] ?? 0;
        $uidLike  = $where['uid_like'] ?? [];
        if (isset($where['eids'])) {
            unset($where['eids']);
        }
        if (isset($where['cids'])) {
            unset($where['cids']);
        }
        if (isset($where['uid_like'])) {
            unset($where['uid_like']);
        }
        if (isset($where['name_like'])) {
            unset($where['name_like']);
        }
        if (isset($where['invoiced'])) {
            unset($where['invoiced']);
        }
        return $this->search($where)->select($field)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->when($name !== '', function ($query) use ($eids, $cids, $name, $uidLike) {
            $query->where(function ($q) use ($eids, $cids, $name, $uidLike) {
                $q->orWhereIn('eid', $eids)
                    ->orWhereIn('cid', $cids)
                    ->orWhereIn('uid', $uidLike)
                    ->orWhere('title', 'like', '%' . $name . '%')
                    ->orWhere(function ($query) use ($name) {
                        $query->whereIn('id', function ($query) use ($name) {
                            $query->from('client_bill')->select(['invoice_id'])->where('bill_no', 'like', "%{$name}%");
                        });
                    });
            });
        })->when($limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->when($invoiced !== '', function ($query) use ($invoiced) {
            if ($invoiced) {
                $query->whereNotNull('num');
            } else {
                $query->whereNull('num');
            }
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
        })->with($with);
    }

    /**
     * 获取发票统计
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInvoiceNum(array $where, string $group = 'eid', string $field = 'eid, SUM(amount) as total')
    {
        return $this->search($where)->groupBy($group)->selectRaw($field)->get();
    }

    protected function setModel(): string
    {
        return Invoice::class;
    }
}
