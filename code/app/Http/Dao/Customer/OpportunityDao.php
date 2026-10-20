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
use App\Http\Model\Customer\Opportunity;
use App\Http\Service\Customer\CustomerTrait;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HigherOrderWhenProxy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 商机Dao.
 */
class OpportunityDao extends BaseDao
{
    use ListSearchTrait;
    use CustomerTrait;
    use TogetherSearchTrait;
    use BatchSearchTrait;
    use JoinSearchTrait;

    public $table = 'customer_odds';

    private $otherSearch = [
        'statistics_type',
        'scope_frame',
        'before_salesman',
        'customer_repeat_check',
        'customer_name',
        'is_work',
        'followed',
        'odds_types',
        'follow',
        'product_name',
        'uid_scope',
    ];

    /**
     * 设置模型.
     * @return BaseModel
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getModel(bool $need = true)
    {
        if (! Schema::hasTable($this->table)) {
            $this->createTable();
        }
        return parent::getModel();
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
    public function listSearch(array $where, int $page = 0, int $limit = 0, array $with = ['product'], int $uid = 0)
    {
        $where = $this->getWhere($where);

        // 处理商机类型筛选条件：将 odds_types 字符串值转换为数组格式
        if (isset($where['odds_types']) && $where['odds_types'] !== '') {
            $where['odds_types'] = [
                'input_type' => 'select',
                'value'      => $where['odds_types'],
                'type'       => 'single',
            ];
        }

        $callbacks = [];

        foreach ($where as $field => $value) {
            if ($value === '') {
                continue;
            }
            if (in_array($field, $this->otherSearch)) {
                $callbacks[] = match ($field) {
                    'statistics_type' => $this->buildStatisticsTypeCondition($value, $uid),
                    'before_salesman' => fn ($query) => $this->searchBeforeSalesman($query, $value['value']),
                    'customer_repeat_check', 'customer_name' => $this->buildCustomerNameCondition($value),
                    'is_work'    => fn ($query) => $this->buildIsWorkCondition($query),
                    'followed'   => $this->buildFollowedCondition($value, $uid),
                    'odds_types' => $this->buildOddsTypesCondition($value),
                    'follow'     => fn ($query) => $this->buildFollowCondition($query, $value),
                    'product_name' => $this->buildProductNameCondition($value),
                    'uid_scope'    => fn ($query) => $this->applyUidScopeCondition($query, $this->getTable(), $value),
                    default      => null,
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
     * 待办关联查询.
     * @param mixed $where
     * @return BaseModel|HigherOrderWhenProxy|mixed
     * @throws BindingResolutionException
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

    public function searchCreator($dao, $value)
    {
        return $value ? (is_array($value) ? $dao->whereIn('creator_uid', $value) : $dao->where('creator_uid', $value)) : $dao;
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
                    ->where('client_subscribe.types', CustomEnum::ODDS)
                    ->where('client_subscribe.subscribe_status', 1);
            }),
            'urgent_follow_up' => function ($query) use ($uid) {
                return $this->applyUrgentFollowUpCondition($query, $this->getTable());
            },
            default => fn ($query) => $query,
        };
    }

    /**
     * 构建客户名称查询条件.
     * @param mixed $value
     */
    protected function buildCustomerNameCondition($value): \Closure
    {
        return fn ($query) => $query->whereExists(function ($subquery) use ($value) {
            $subquery->selectRaw(1)
                ->from('customer')
                ->whereColumn('customer_odds.eid', 'customer.id')
                ->where('customer.customer_name', 'like', "%{$value['value']}%");
        });
    }

    /**
     * 构建工作客户查询条件.
     * @param mixed $query
     */
    protected function buildIsWorkCondition($query): \Closure
    {
        return fn ($query) => $query->where('userid', '!=', '')->where('external_userid', '!=', '')->where('status', 1);
    }

    /**
     * 构建关注查询条件.
     * @param mixed $value
     */
    protected function buildFollowedCondition($value, int $uid): \Closure
    {
        return function ($query) use ($value, $uid) {
            if ($value['value']) {
                $query->whereExists(function ($subQuery) use ($uid) {
                    $subQuery->from('client_subscribe')
                        ->whereColumn('client_subscribe.eid', $this->getTable() . '.id')
                        ->where('client_subscribe.uid', $uid)
                        ->where('client_subscribe.subscribe_status', 1)
                        ->where('client_subscribe.types', CustomEnum::ODDS);
                });
            } else {
                $query->whereNotExists(function ($subQuery) use ($uid) {
                    $subQuery->from('client_subscribe')
                        ->whereColumn('client_subscribe.eid', $this->getTable() . '.id')
                        ->where('client_subscribe.uid', $uid)
                        ->where('client_subscribe.subscribe_status', 1)
                        ->where('client_subscribe.types', CustomEnum::ODDS);
                });
            }
        };
    }

    /**
     * 构建商机类型查询条件.
     * @param mixed $value
     */
    protected function buildOddsTypesCondition($value): \Closure
    {
        return function ($query) use ($value) {
            // 兼容处理：支持直接字符串/数组值，也支持包含 value 的数组格式
            $actualValue = is_array($value) ? ($value['value'] ?? $value) : $value;
            $actualValue = is_array($actualValue)
                ? array_values(array_filter($actualValue, fn ($item) => $item !== ''))
                : $actualValue;

            if ($actualValue === '' || $actualValue === []) {
                return;
            }

            $query->where(function ($q) use ($actualValue) {
                if (is_array($actualValue)) {
                    $q->whereIn('types', $actualValue);
                } else {
                    $q->where('types', $actualValue);
                }
            });
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

    /**
     * 构建产品名称查询条件.
     *
     * @param mixed $value
     * @return \Closure
     */
    protected function buildProductNameCondition($value): \Closure
    {
        return fn ($query) => $query->whereExists(function ($subQuery) use ($value) {
            $subQuery->from('customer_product_assist')
                ->whereColumn('customer_product_assist.link_id', $this->getTable() . '.id')
                ->where('customer_product_assist.link_type', CustomEnum::ODDS)
                ->where('customer_product_assist.product_name', 'like', '%' . $value['value'] . '%');
        });
    }

    /**
     * 设置模型.
     * @return string
     */
    protected function setModel()
    {
        return Opportunity::class;
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

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('id');
            $table->unsignedInteger('uid')->default(0)->index()->comment('业务员用户ID');
            $table->unsignedInteger('before_uid')->default(0)->index()->comment('前业务员用户ID');
            $table->unsignedInteger('creator_uid')->default(0)->index()->comment('创建用户ID');
            $table->string('name', 255)->default('')->comment('商机名称');
            $table->unsignedInteger('eid')->default(0)->comment('客户ID');
            $table->string('source', 32)->default('')->comment('商机类型：1、线索；0、客户；');
            $table->string('types', 128)->default('')->comment('商机类型');
            $table->string('status', 128)->default('')->comment('商机状态');
            $table->unsignedTinyInteger('followed')->default(0)->comment('是否关注');
            $table->text('description')->default('')->comment('商机描述');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('客户商机表');

            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
