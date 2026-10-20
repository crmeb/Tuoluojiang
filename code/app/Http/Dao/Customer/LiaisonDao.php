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

use App\Http\Model\Customer\Liaison;
use App\Http\Service\Customer\CustomerTrait;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 联系人Dao.
 */
class LiaisonDao extends BaseDao
{
    use ListSearchTrait;
    use BatchSearchTrait;
    use CustomerTrait;

    private $otherSearch = [
        'types',
        'scope_frame',
        'uid_scope',
    ];

    /**
     * 列表筛选数据.
     * @param mixed $where
     * @param mixed $page
     * @param mixed $limit
     * @param mixed $with
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function listSearch(array $where, int $page = 0, int $limit = 0, array $with = [], int $uid = 0)
    {
        $where = $this->getWhere($where);

        $callbacks = [];

        foreach ($where as $field => $value) {
            if ($value === '') {
                continue;
            }
            if (in_array($field, $this->otherSearch)) {
                if ($field === 'uid_scope') {
                    $callbacks[] = fn ($query) => $this->applyUidScopeCondition($query, 'customer_liaison', $value);
                }
                unset($where[$field]);
            } elseif ($field == 'contract_category') {
                $callbacks[] = fn ($query) => $value['value'] ? $query->whereIn('contract_category', $value['value']) : $query;
                unset($where[$field]);
            } elseif (is_array($value)) {
                if (isset($value['input_type'])) {
                    if ($value['value'] === '') {
                        unset($where[$field]);
                        continue;
                    }
                    $callbacks[] = match ($value['input_type']) {
                        'select'  => fn ($query) => $this->getMoreSelectSearch($query, $field, $value['value'], $value['type']),
                        'radio'   => fn ($query) => $this->getSelectSearch($query, $field, $value['value']),
                        'checked' => fn ($query) => $this->getMemberSearch($query, $field, $value['value']),
                        'input'   => fn ($query) => $this->getInputSearch($query, $field, $value['value']),
                        'date', 'datetime' => fn ($query) => $this->getDateSearch($query, $field, $value['value']),
                        'personnel' => fn ($query) => $this->getPersonnelSearch($query, $field, $value['value']),
                        'member'    => fn ($query) => $this->getMemberSearch($query, $field, $value['value']),
                        default     => fn ($query) => $query->where($field, $value['value']),
                    };
                } else {
                    $callbacks[] = fn ($query) => $query->whereIn($field, $value);
                }
                unset($where[$field]);
            } else {
                $callbacks[] = fn ($query) => $query->where($field, $value);
                unset($where[$field]);
            }
        }

        $callbacks = array_filter($callbacks);

        return $this->search($where)
            ->when(! empty($callbacks), function ($query) use ($callbacks) {
                foreach ($callbacks as $callback) {
                    $callback($query);
                }
            })
            ->when($limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })
            ->when($sort = sort_mode('id'), function ($query) use ($sort) {
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
            })
            ->with($with);
    }

    public function searchCustomerName($dao, $value)
    {
        return $value != '' ? $dao->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('customer')
                ->whereColumn('customer.id', 'customer_liaison.eid')
                ->where('customer.customer_name', 'like', '%' . $value . '%');
        }) : $dao;
    }

    /**
     * 创建人查询.
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function searchCreator($dao, $value)
    {
        return $value ? (is_array($value) ? $dao->whereIn('creator_uid', $value) : $dao->where('creator_uid', $value)) : $dao;
    }

    protected function setModel(): string
    {
        return Liaison::class;
    }
}
