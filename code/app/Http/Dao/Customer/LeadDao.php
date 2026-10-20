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
use App\Constants\System\ViewSearchEnum;
use App\Http\Dao\Schedule\ScheduleRemindDao;
use App\Http\Dao\Schedule\ScheduleTaskDao;
use App\Http\Model\Customer\Lead;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HigherOrderWhenProxy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 线索Dao.
 */
class LeadDao extends BaseDao
{
    use CustomerTrait;
    use ListSearchTrait;
    use TogetherSearchTrait;
    use BatchSearchTrait;
    use JoinSearchTrait;

    protected $table = 'customer_clue';

    protected array $otherSearch = [
        'statistics_type',
        'types',
        'scope_frame',
        'before_salesman',
        'customer_repeat_check',
        'subscribe_uid',
        'repeat',
        'work_customer',
        'followed',
        'follow',
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
     *
     * @param mixed $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param array $with 预加载关联
     * @param int $uid 用户ID
     * @return mixed
     * @throws BindingResolutionException
     */
    public function listSearch(array $where, int $page = 0, int $limit = 0, array $with = [], int $uid = 0)
    {
        // 1. 处理基础 where 条件
        $where = $this->getWhere($where);

        // 2. 收集闭包条件（用于 search 之后添加的特殊查询）
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
                    'followed'        => $this->buildFollowedCondition($value, $uid),
                    'follow'          => fn ($query) => $this->buildFollowCondition($query, $value),
                    'uid_scope'       => fn ($query) => $this->applyUidScopeCondition($query, $this->getTable(), $value),
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

        // 3. 过滤掉 null 的回调
        $callbacks = array_filter($callbacks);

        // 4. 统一调用 search() 生成查询（自动应用权限过滤）
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

    public function searchCustomerLabel($dao, $value)
    {
        return $dao->where(function ($q) use ($value) {
            foreach ($value as $v) {
                $q->orWhereJsonContains('customer_label', (string) $v)->orWhereJsonContains('customer_label', $v);
            }
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
    public function searchBeforeSalesman($dao, $value)
    {
        return is_array($value) ? $dao->whereIn('before_uid', $value) : $dao->where('before_uid', $value);
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

    /**
     * 急需跟进ID.
     * @throws BindingResolutionException
     */
    public function getUrgentFollowUpIds(array $where): array
    {
        return $this->scheduleSearch($where)->select([$this->getTable() . '.id'])->distinct()->pluck($this->getTable() . '.id')->toArray();
    }

    /**
     * 跟进过期客户.
     * @throws BindingResolutionException
     */
    public function getFollowExpire(array $where): mixed
    {
        return $this->scheduleSearch($where)->select([$this->getTable() . '.id'])->distinct()->pluck($this->getTable() . '.id');
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

    public function getJoinLabelList(int $labelId, int $page, int $limit)
    {
        return $this->getModel()->whereJsonContains('customer_label', $labelId)->select(['id', 'external_userid', 'customer_label', 'userid'])->forPage($page, $limit)->get();
    }

    /**
     * 未跟进客户线索查询.
     * @param mixed $cycle
     * @param mixed $page
     * @param mixed $limit
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function notFollowedSearch(int $cycle, int $page = 0, int $limit = 0): mixed
    {
        return $this->getModel()
            ->whereNotExists(function ($query) use ($cycle) {
                $query->from('customer_record')
                    ->whereColumn('customer_record.eid', 'customer_clue.id')
                    ->where('customer_record.link_type', ViewSearchEnum::VIEW_CLUE)
                    ->whereBetween('customer_record.created_at', [now()->subDays($cycle)->toDateTimeString(), now()->toDateTimeString()]);
            })
            // 排除已关联企微客户的线索（userid 和 external_userid 都为空表示未关联企微）
            ->where(function ($q) {
                $q->whereNull('userid')->orWhere('userid', '');
            })
            ->where(function ($q) {
                $q->whereNull('external_userid')->orWhere('external_userid', '');
            })
            ->where('uid', '>', 0)
            ->forPage($page, $limit)
            ->get();
    }

    /**
     * 批量查询线索信息.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getCustomerClueList(array $ids)
    {
        return $this->getModel()
            ->whereIn('id', $ids)
            ->where('external_userid', '<>', '')
            ->where('userid', '<>', '')
            ->select(['id', 'external_userid', 'userid'])
            ->get()
            ->toArray();
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
                    ->where('client_subscribe.types', CustomEnum::CLUE)
                    ->where('client_subscribe.subscribe_status', 1);
            }),
            'urgent_follow_up' => function ($query) {
                return $this->applyUrgentFollowUpCondition($query, $this->getTable());
            },
            default => fn ($query) => $query,
        };
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
            $value = is_array($value) ? $value['value'] : $value;
            // 1. 先分组统计每个值的重复次数（非相关子查询，仅执行一次）
            $countSubQuery = DB::table('customer_clue')
                ->select(
                    $value . ' as val',
                    DB::raw('COUNT(*) as duplicate_count')
                )
                ->where($value, '<>', '')
                ->groupBy($value)
                ->having('duplicate_count', '>', 1);

            // 2. 主查询关联统计结果，获取所有重复记录
            $query->joinSub(
                $countSubQuery,
                'count_sub',
                function ($join) use ($value) {
                    $join->on('customer_clue.' . $value, '=', 'count_sub.val');
                }
            )->orderByDesc('count_sub.duplicate_count')->orderByDesc($value);
        };
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
                        ->where('client_subscribe.types', CustomEnum::CLUE);
                });
            } else {
                $query->whereNotExists(function ($subQuery) use ($uid) {
                    $subQuery->from('client_subscribe')
                        ->whereColumn('client_subscribe.eid', $this->getTable() . '.id')
                        ->where('client_subscribe.uid', $uid)
                        ->where('client_subscribe.subscribe_status', 1)
                        ->where('client_subscribe.types', CustomEnum::CLUE);
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

    /**
     * 设置模型.
     * @return string
     */
    protected function setModel()
    {
        return Lead::class;
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
            $table->string('name', 255)->default('')->comment('线索名称');
            $table->string('source', 128)->default('')->comment('线索来源');
            $table->string('pool', 128)->default('')->comment('线索池');
            $table->string('phone', 128)->default('')->comment('联系电话');
            $table->unsignedTinyInteger('status')->default(1)->comment('线索状态');
            $table->unsignedTinyInteger('followed')->default(0)->comment('是否关注');
            $table->unsignedInteger('return_num')->default(0)->comment('退回次数');
            $table->string('mark', 255)->default('')->comment('备注');
            $table->string('userid', 255)->default('')->comment('企微用户ID');
            $table->string('external_userid', 255)->default('')->comment('企微客户ID');
            $table->json('customer_label')->nullable()->comment('企微客户标签');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['external_userid', 'userid']);
            $table->comment('客户线索表');

            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });

        if ($this->supportsJsonArrayIndex()) {
            $table = DB::getTablePrefix() . $this->table;
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `idx_customer_label` ((CAST(`customer_label` AS CHAR(20) ARRAY)))");
        }
    }

    private function supportsJsonArrayIndex(): bool
    {
        $version = (string) DB::selectOne('SELECT VERSION() AS version')->version;
        if (stripos($version, 'mariadb') !== false) {
            return false;
        }

        $version = preg_replace('/[^0-9.].*$/', '', $version) ?: '0.0.0';
        return version_compare($version, '8.0.17', '>=');
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
