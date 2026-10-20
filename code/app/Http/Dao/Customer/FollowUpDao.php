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

use App\Constants\DataPermissionLevelEnum;
use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Context\DataPermissionContext;
use App\Http\Model\Customer\FollowUp;
use App\Http\Model\Schedule\Schedule;
use App\Http\Model\Schedule\ScheduleTask;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;

/**
 * 跟踪记录Dao.
 */
class FollowUpDao extends BaseDao
{
    use ListSearchTrait;
    use JoinSearchTrait;

    /**
     * 待办关联查询.
     * @param mixed $where
     * @return BaseModel|HigherOrderWhenProxy|mixed
     * @throws BindingResolutionException
     */
    public function scheduleSearch($where): mixed
    {
        $this->aliasC = app($this->setModelC())->getTable();
        return $this->getJoinModel('id', 'link_id')
            ->join($this->aliasC, $this->aliasB . '.id', '=', $this->aliasC . '.schedultid', 'left')
            ->where($this->getFiled('types'), $where['types'] ?? 1)
            ->where(function (Builder $query) use ($where) {
                $statusField = $this->getFiled('status', $this->aliasC);
                $query->where($statusField, $where['schedule_status'] ?? 0)->orWhereNull($statusField);
            })->when(isset($where['entid']), function ($query) use ($where) {
                $query->where($this->getFiled('entid', $this->aliasB), $where['entid']);
            });
    }

    public function search($where, ?bool $authWhere = null)
    {
        if (isset($where['name_like']) && $where['name_like']) {
            unset($where['exist']);
        }
        $eid      = $where['eid'] ?? '';
        $status   = $where['status'] ?? '';
        $userId   = $where['user_id'] ?? '';
        $linkType = $where['link_type'] ?? '';
        if (is_array($where)) {
            unset($where['eid'], $where['status'], $where['user_id'], $where['link_type']);
        }
        $this->setTimeField('client_follow.created_at');
        $query = parent::search($where, $authWhere)
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($linkType !== '', function ($query) use ($linkType, $eid) {
                if ($linkType !== 'customer') {
                    return $query->where('link_type', $linkType)->where('eid', $eid);
                }
                $query->where(function ($que) use ($eid) {
                    $que->where('link_type', 'customer')
                        ->where('eid', $eid);
                    $oddsSubQuery = function ($sub) use ($eid) {
                        $sub->select('id')
                            ->from('customer_odds')
                            ->where('eid', $eid);
                    };
                    $que->orWhere(function ($q) use ($oddsSubQuery) {
                        $q->where('link_type', 'odds')
                            ->whereIn('eid', $oddsSubQuery);
                    });
                });
            })
            ->when($userId !== '', function (Builder $query) use ($userId) {
                $method = is_array($userId) ? 'whereIn' : 'where';
                $query->{$method}('user_id', $userId);
            });

        return $this->applyCustomerDataPermission($query);
    }

    public function getLastFollow(array $where)
    {
        return parent::search($where)->with('latest')->groupBy('eid')->get()->pluck('latest');
    }

    /**
     * @return mixed
     */
    public function getClientFollow(int $eid, string $linkType = 'customer')
    {
        // 获取今日数据
        $startTime = date('Y-m-d 00:00:00');
        $endTime   = date('Y-m-d 23:59:59');
        return $this->getModel()->whereBetween('created_at', [$startTime, $endTime])->where('link_type', $linkType)->where('eid', $eid)->count();
    }

    /**
     * 跟进记录按关联线索/客户/商机套用客户模块数据权限.
     * @param mixed $query
     */
    protected function applyCustomerDataPermission($query)
    {
        if (! DataPermissionContext::isEnabled()) {
            $this->initCustomerDataPermissionContext();
        }
        if (DataPermissionContext::getModule() !== ModuleEnum::CUSTOMER) {
            return $query;
        }

        $dataLevel = DataPermissionContext::getDataLevel();
        if ($dataLevel === DataPermissionLevelEnum::ALL) {
            return $query;
        }
        if ($dataLevel === DataPermissionLevelEnum::NONE) {
            return $query->whereRaw('0 = 1');
        }

        $uids = DataPermissionContext::getUids();
        if (! $uids) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $query) use ($uids) {
            $query->where(function (Builder $query) use ($uids) {
                $query->where('client_follow.link_type', ViewSearchEnum::VIEW_CLUE)
                    ->whereExists(function ($subQuery) use ($uids) {
                        $subQuery->from('customer_clue')
                            ->whereColumn('customer_clue.id', 'client_follow.eid')
                            ->where(function ($query) use ($uids) {
                                $this->applyUidFields($query, ['customer_clue.uid', 'customer_clue.creator_uid', 'customer_clue.before_uid'], $uids);
                            });
                    });
            })->orWhere(function (Builder $query) use ($uids) {
                $query->where('client_follow.link_type', ViewSearchEnum::VIEW_CUSTOMER)
                    ->whereExists(function ($subQuery) use ($uids) {
                        $subQuery->from('customer')
                            ->whereColumn('customer.id', 'client_follow.eid')
                            ->where(function ($query) use ($uids) {
                                $this->applyCustomerUidFields($query, $uids);
                            });
                    });
            })->orWhere(function (Builder $query) use ($uids) {
                $query->where('client_follow.link_type', ViewSearchEnum::VIEW_ODDS)
                    ->whereExists(function ($subQuery) use ($uids) {
                        $subQuery->from('customer_odds')
                            ->whereColumn('customer_odds.id', 'client_follow.eid')
                            ->where(function ($query) use ($uids) {
                                $query->where(function ($query) use ($uids) {
                                    $this->applyUidFields($query, ['customer_odds.uid', 'customer_odds.creator_uid', 'customer_odds.before_uid'], $uids);
                                })->orWhereExists(function ($customerQuery) use ($uids) {
                                    $customerQuery->from('customer')
                                        ->whereColumn('customer.id', 'customer_odds.eid')
                                        ->where(function ($query) use ($uids) {
                                            $this->applyCustomerUidFields($query, $uids);
                                        });
                                });
                            });
                    });
            });
        });
    }

    protected function initCustomerDataPermissionContext(): void
    {
        if (! auth('admin')->check()) {
            return;
        }
        $userId = auth('admin')->id();
        if (! $userId) {
            return;
        }

        app()->get(ModulePermissionService::class)->hydrateDataPermissionContext((int) $userId, ModuleEnum::CUSTOMER);
    }

    protected function applyUidFields($query, array $fields, array $uids): void
    {
        foreach ($fields as $index => $field) {
            $method = $index === 0 ? 'whereIn' : 'orWhereIn';
            $query->{$method}($field, $uids);
        }
    }

    protected function applyCustomerUidFields($query, array $uids): void
    {
        $this->applyUidFields($query, ['customer.uid'], $uids);
        foreach ($uids as $uid) {
            $query->orWhereJsonContains('customer.member', $uid)
                ->orWhereJsonContains('customer.member', (string) $uid);
        }
    }

    /**
     * @return mixed|string
     */
    protected function setModel()
    {
        return FollowUp::class;
    }

    protected function setModelB(): string
    {
        return Schedule::class;
    }

    protected function setModelC(): string
    {
        return ScheduleTask::class;
    }
}
