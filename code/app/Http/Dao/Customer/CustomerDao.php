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

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Dao\Schedule\ScheduleRemindDao;
use App\Http\Dao\Schedule\ScheduleTaskDao;
use App\Http\Model\Customer\Customer;
use App\Http\Service\Customer\CustomerTrait;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 客户Dao.
 */
class CustomerDao extends BaseDao
{
    use CustomerTrait;
    use ListSearchTrait;
    use TogetherSearchTrait;
    use BatchSearchTrait;
    use JoinSearchTrait;

    protected $otherSearch = [
        'statistics_type',
        'types',
        'scope_frame',
        'before_salesman',
        'repeat',
        'subscribe_uid',
        'work_customer',
        'member',
        'involved',
        'follow',
        'uid_scope',
    ];

    /**
     * 待办关联查询.
     * @param mixed $where
     * @return BaseModel|HigherOrderWhenProxy|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function scheduleSearch($where): mixed
    {
        $remindDay    = Carbon::now(config('app.timezone'))->toDateString();
        $this->aliasC = app($this->setModelC())->getTable();
        $this->aliasD = app($this->setModelD())->getTable();
        return $this->getJoinModel('id', 'eid')
            ->join($this->aliasC, $this->aliasB . '.uniqued', '=', $this->aliasC . '.uniqued')
            ->join($this->aliasD, $this->aliasC . '.sid', '=', $this->aliasD . '.pid', 'left')
            ->where($this->getFiled('uid'), '<>', 0)
            ->where($this->getFiled('types', $this->aliasB), 1)
            ->whereDate($this->getFiled('time', $this->aliasB), '<', $remindDay)
            ->where(function ($query) use ($where) {
                $uidField = $this->getFiled('uid');
                if (isset($where['uid']) && $where['uid']) {
                    if (is_array($where['uid'])) {
                        $query->whereIn($uidField, $where['uid']);
                    } else {
                        $query->where($uidField, $where['uid']);
                    }
                }
            })
            ->where(function (Builder $query) {
                $query->where($this->getFiled('status', $this->aliasD), '<>', 3);
                $query->orWhereNull($this->getFiled('id', $this->aliasD));
            });
    }

    /**
     * 急需跟进统计.
     * @throws BindingResolutionException
     */
    public function getUrgentFollowUpCount(array $where): int
    {
        return (int) $this->scheduleSearch($where)->distinct()->count('customer.id');
    }

    /**
     * 列表筛选数据.
     * @param mixed $where
     * @param mixed $page
     * @param mixed $limit
     * @param mixed $with
     * @param mixed $uid
     * @return Builder
     * @throws BindingResolutionException
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
                $callbacks[] = match ($field) {
                    'statistics_type' => $this->buildStatisticsTypeCondition($value, $uid),
                    'work_customer'   => fn ($query) => $this->buildWorkCustomerCondition($query, $value),
                    'repeat'          => fn ($query) => $this->buildRepeatCondition($query, $value),
                    'member'          => fn ($query) => $this->buildMemberCondition($query, $value),
                    'involved'        => fn ($query) => $this->buildInvolvedCondition($query, $value),
                    'follow'          => fn ($query) => $this->buildFollowCondition($query, $value),
                    'uid_scope'       => fn ($query) => $this->buildUidScopeCondition($query, $value),
                    'before_salesman' => fn ($query) => $this->searchBeforeSalesman($query, $value['value']),
                    default           => null,
                };
                unset($where[$field]);
            } elseif (is_array($value)) {
                if (isset($value['input_type'])) {
                    $callbacks[] = match ($value['input_type']) {
                        'select' => fn ($query) => $this->getMoreSelectSearch($query, $field, $value['value'], $value['type']),
                        'radio' => fn ($query) => $this->getSelectSearch($query, $field, $value['value']),
                        'checked' => fn ($query) => $this->getMemberSearch($query, $field, $value['value']),
                        'input' => fn ($query) => $this->getInputSearch($query, $field, $value['value']),
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

    /**
     * 合同订单名称查询 (优化为 whereExists).
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     */
    public function searchContractName($dao, $value)
    {
        return $dao->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('contract')
                ->whereColumn('contract.eid', 'customer.id')
                ->where('contract.contract_name', 'like', '%' . $value . '%');
        });
    }

    /**
     * 业务员查询.
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function searchSalesman($dao, $value)
    {
        return is_array($value) ? $dao->whereIn('uid', $value) : $dao->where('uid', $value);
    }

    public function searchCustomerStatus($dao, $value)
    {
        return $dao->where('customer_status', $value);
    }

    /**
     * 合同订单编号查询 (优化为 whereExists).
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     */
    public function searchContractNo($dao, $value)
    {
        return $dao->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('contract')
                ->whereColumn('contract.eid', 'customer.id')
                ->where('contract.contract_no', 'like', '%' . $value . '%');
        });
    }

    /**
     * 联系人电话查询 (优化为 whereExists).
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     */
    public function searchLiaisonTel($dao, $value)
    {
        return $dao->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('customer_liaison')
                ->whereColumn('customer_liaison.eid', 'customer.id')
                ->where('customer_liaison.liaison_tel', 'like', '%' . $value . '%');
        });
    }

    /**
     * 联系人名称查询 (优化为 whereExists).
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     */
    public function searchLiaison($dao, $value)
    {
        return $dao->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('customer_liaison')
                ->whereColumn('customer_liaison.eid', 'customer.id')
                ->where('customer_liaison.liaison_name', 'like', '%' . $value . '%');
        });
    }

    /**
     * 客户标签查询.
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function searchCustomerLabel($dao, $value)
    {
        return $dao->where(function ($q) use ($value) {
            foreach ($value as $v) {
                $q->orWhereJsonContains('customer_label', (string) $v)->orWhereJsonContains('customer_label', $v)->orWhereJsonContains('customer_label', (int) $v);
            }
        });
    }

    /**
     * 客户创建人查询.
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

    /**
     * 客户标签查询.
     * @param mixed $dao
     * @param mixed $value
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function searchBeforeSalesman($dao, $value)
    {
        return is_array($value) ? $dao->whereIn('before_uid', $value) : $dao->where('before_uid', $value);
    }

    /**
     * 急需跟进ID.
     * @throws BindingResolutionException
     */
    public function getUrgentFollowUpIds(array $where): array
    {
        return $this->scheduleSearch($where)->select(['customer.id'])->distinct()->pluck('customer.id')->toArray();
    }

    /**
     * 跟进过期客户.
     * @throws BindingResolutionException
     */
    public function getFollowExpire(array $where): mixed
    {
        return $this->scheduleSearch($where)->select(['customer.id'])->distinct()->pluck('customer.id');
    }

    /**
     * 批量查询客户信息.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCustomerList(array $ids)
    {
        return $this->getModel()
            ->whereIn('id', $ids)
            ->where('external_userid', '<>', '')
            ->where('userid', '<>', '')
            ->select(['id', 'external_userid', 'userid'])
            ->get()
            ->toArray();
    }

    public function selectData(array $where)
    {
        return $this->getModel()->orWhereIn('uid', $where['uid'])->orWhereJsonContains('member', $where['member']);
    }

    /**
     * 构建统计类型查询条件.
     */
    protected function buildStatisticsTypeCondition(string $value, int $uid): \Closure
    {
        return match ($value) {
            'concern' => fn ($query) => $query->whereExists(function ($subQuery) use ($uid) {
                $subQuery->from('client_subscribe')
                    ->whereColumn('client_subscribe.eid', $this->getTable() . '.id')
                    ->where('client_subscribe.uid', $uid)
                    ->where('client_subscribe.subscribe_status', 1)
                    ->where('client_subscribe.types', CustomEnum::CUSTOMER);
            }),
            'unsettled'        => fn ($query) => $query->where('customer_status', 0),
            'traded'           => fn ($query) => $query->where('customer_status', 1),
            'urgent_follow_up' => function ($query) {
                return $this->buildUrgentFollowUpCondition($query);
            },
            default => fn ($query) => $query,
        };
    }

    /**
     * “急需跟进”使用数据库内 exists 判断，避免先查出海量客户ID再 whereIn。
     */
    protected function buildUrgentFollowUpCondition($query, int|array|null $uid = null)
    {
        return $this->applyUrgentFollowUpCondition($query, $this->getTable(), $uid);
    }

    /**
     * 内部范围条件：全部权限场景用 active admin exists 替代超长 uid in (...)。
     */
    protected function buildUidScopeCondition($query, string $value)
    {
        return $this->applyUidScopeCondition($query, $this->getTable(), $value);
    }

    /**
     * 构建工作客户查询条件.
     * @param mixed $query
     * @param mixed $value
     */
    protected function buildWorkCustomerCondition($query, $value): \Closure
    {
        return function ($query) use ($value) {
            $value = is_array($value['value']) ? array_map('intval', $value['value']) : [(int) $value['value']];
            if (count($value) > 1) {
                return;
            }
            if (in_array(1, $value)) {
                $query->whereNotNull('userid')->where('userid', '<>', '');
            }
            if (in_array(2, $value)) {
                $query->where(function ($q) {
                    $q->whereNull('userid')->orWhere('userid', '');
                });
            }
        };
    }

    /**
     * 构建查重查询条件.
     * @param mixed $query
     * @param mixed $value
     */
    protected function buildRepeatCondition($query, $value): \Closure
    {
        return function ($query) use ($value) {
            $value         = is_array($value) ? $value['value'] : $value;
            $countSubQuery = DB::table('customer')
                ->select(
                    $value . ' as val',
                    DB::raw('COUNT(*) as duplicate_count')
                )
                ->where($value, '<>', '')
                ->groupBy($value)
                ->having('duplicate_count', '>', 1);

            $query->joinSub(
                $countSubQuery,
                'count_sub',
                function ($join) use ($value) {
                    $join->on('customer.' . $value, '=', 'count_sub.val');
                }
            )->orderByDesc('count_sub.duplicate_count')->orderByDesc($value);
        };
    }

    /**
     * 构建成员查询条件.
     * @param mixed $query
     * @param mixed $value
     */
    protected function buildMemberCondition($query, $value): \Closure
    {
        return function ($query) use ($value) {
            if (is_array($value)) {
                $query->where(function ($q) use ($value) {
                    foreach ($value as $item) {
                        $q->orWhereJsonContains('member', $item);
                    }
                });
            } else {
                $query->whereJsonContains('member', $value);
            }
        };
    }

    /**
     * 构建参与查询条件.
     * @param mixed $query
     * @param mixed $value
     */
    protected function buildInvolvedCondition($query, $value): \Closure
    {
        return function ($query) use ($value) {
            if (is_array($value)) {
                $query->where(function ($q) use ($value) {
                    foreach ($value as $item) {
                        $q->orWhereJsonContains('member', $item);
                    }
                    $q->orWhereIn('uid', $value);
                });
            } else {
                $query->where(function ($q) use ($value) {
                    $q->whereJsonContains('member', $value)->orWhere('uid', $value);
                });
            }
        };
    }

    /**
     * 构建跟进查询条件.
     * @param mixed $query
     * @param mixed $value
     */
    protected function buildFollowCondition($query, $value): \Closure
    {
        return function ($query) use ($value) {
            $value = is_array($value['value']) ? array_map('intval', $value['value']) : [(int) $value['value']];
            if (count($value) > 1) {
                return;
            }
            if (in_array(1, $value)) {
                $query->has('follows');
            }
            if (in_array(2, $value)) {
                $query->doesntHave('follows');
            }
        };
    }

    protected function setModel(): string
    {
        return Customer::class;
    }

    protected function setModelB(): string
    {
        return FollowUpDao::class;
    }

    protected function setModelC(): string
    {
        return ScheduleRemindDao::class;
    }

    protected function setModelD(): string
    {
        return ScheduleTaskDao::class;
    }
}
