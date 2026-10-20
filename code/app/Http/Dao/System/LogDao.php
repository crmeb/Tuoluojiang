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

namespace App\Http\Dao\System;

use App\Http\Model\Admin\Admin;
use App\Http\Model\System\Log;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;

/**
 * Class LogDao.
 */
class LogDao extends BaseDao
{
    use ListSearchTrait;
    use JoinSearchTrait;

    public $table = 'enterprise_log';

    protected array $logIndexCache = [];

    /**
     * 设置模型.
     * @return BaseModel
     * @throws BindingResolutionException
     */
    public function getModel(bool $need = true)
    {
        $model = parent::getModel();
        if ($need) {
            $model->setTable($this->table);
            return $this->getJoinModel('uid', 'uid', model: $model);
        }
        return $model->setTable($this->table);
    }

    /**
     * 查询列表.
     * @throws BindingResolutionException
     */
    public function getLogList(array $where, int $page = 0, int $limit = 0, array $with = []): array
    {
        [$count, $list] = $this->getMergedPageList($where, $page, $limit, $this->getLogSelectFields());
        if ($list) {
            $list = Log::hydrate($list)->toArray();
        }
        return compact('list', 'count');
    }

    /**
     * 清理超过保留时间的日志.
     */
    public function deleteExpiredLogs(string $expiredAt, int $batchSize = 5000): int
    {
        $deleted = 0;
        do {
            $ids = DB::table($this->table)
                ->where('created_at', '<', $expiredAt)
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id')
                ->all();

            if (! $ids) {
                break;
            }

            $deleted += DB::table($this->table)->whereIn('id', $ids)->delete();
        } while (count($ids) === $batchSize);

        return $deleted;
    }

    /**
     * @throws BindingResolutionException
     */
    public function searchInfo($where, $page, $limit): array
    {
        if (! $limit) {
            return [(clone $this->buildLogTableQuery($where))->count(), []];
        }
        return $this->getMergedPageList($where, $page, $limit, ['id', 'created_at']);
    }

    /**
     * 构建日志查询条件.
     */
    protected function buildLogTableQuery(array $where, string $forceIndex = '')
    {
        $query = DB::table($this->table);
        if ($forceIndex && $this->logIndexExists($forceIndex)) {
            $query->forceIndex($forceIndex);
        }
        $query->when(isset($where['entid']), function ($query) use ($where) {
            $query->where('entid', $where['entid']);
        })->when(isset($where['event_name']) && $where['event_name'], function ($query) use ($where) {
            $query->where('event_name', 'like', '%' . $where['event_name'] . '%');
        })->when(isset($where['user_name']) && $where['user_name'], function ($query) use ($where) {
            $query->where('user_name', 'like', '%' . $where['user_name'] . '%');
        })->when(isset($where['path']) && $where['path'], function ($query) use ($where) {
            $query->where('path', 'like', '%' . $where['path'] . '%');
        });
        $this->scopeTime($query, $where['time'] ?? '', 'created_at');
        return $query;
    }

    /**
     * 判断日志列表排序索引是否存在.
     */
    protected function logIndexExists(string $indexName): bool
    {
        if (isset($this->logIndexCache[$indexName])) {
            return $this->logIndexCache[$indexName];
        }
        $table = DB::getTablePrefix() . $this->table;
        $index = DB::getPdo()->quote($indexName);
        return $this->logIndexCache[$indexName] = ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = {$index}"));
    }

    /**
     * 分页读取日志，避免一次加载过多数据.
     */
    protected function getMergedPageList(array $where, int $page, int $limit, array $fields): array
    {
        $sortByTime = ! empty($where['time']);
        $forceIndex = isset($where['entid']) ? ($sortByTime ? 'idx_entid_created_id' : 'idx_entid_id') : ($sortByTime ? 'idx_created_id' : '');
        $query      = $this->buildLogTableQuery($where, $forceIndex);
        $count      = (clone $query)->count();

        if (! $limit) {
            return [$count, []];
        }

        $query->select($fields);
        if ($sortByTime) {
            $query->orderByDesc('created_at')->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }
        $list = $query->forPage($page ?: 1, $limit)->get()->map(fn ($item) => (array) $item)->toArray();
        return [$count, $list];
    }

    /**
     * 日志列表返回字段.
     */
    protected function getLogSelectFields(): array
    {
        return [
            'id',
            'event_name',
            'uid',
            'terminal',
            'method',
            'last_ip',
            'created_at',
            'path',
            'user_name',
        ];
    }

    /**
     * 查询构造器手动补齐日志时间筛选.
     */
    protected function scopeTime($query, string $time, string $field)
    {
        if (! $time) {
            return;
        }
        if (str_contains($time, '-')) {
            [$startTime, $endTime] = array_pad(explode('-', $time, 2), 2, '');
            $startTime             = str_replace('/', '-', trim($startTime));
            $endTime               = str_replace('/', '-', trim($endTime));
            if ($startTime && $endTime) {
                if (! str_contains($startTime, ':') && ! str_contains($endTime, ':')) {
                    $query->whereDate($field, '>=', $startTime)->whereDate($field, '<', date('Y-m-d', datetime_timestamp($endTime) + 86400));
                    return;
                }
                $query->whereBetween($field, [$startTime, $startTime === $endTime ? date('Y-m-d H:i:s', datetime_timestamp($endTime) + 86400) : $endTime]);
                return;
            }
            if ($startTime) {
                $query->where($field, '>=', $startTime);
                return;
            }
            if ($endTime) {
                $query->where($field, '<', $endTime);
                return;
            }
        }
        match ($time) {
            'today'      => $query->whereBetween($field, [now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()]),
            'yesterday'  => $query->whereBetween($field, [now()->subDay()->startOfDay()->toDateTimeString(), now()->subDay()->endOfDay()->toDateTimeString()]),
            'week'       => $query->whereBetween($field, [now()->startOfWeek()->toDateTimeString(), now()->endOfWeek()->toDateTimeString()]),
            'last week'  => $query->whereBetween($field, [now()->subWeek()->startOfWeek()->toDateTimeString(), now()->subWeek()->endOfWeek()->toDateTimeString()]),
            'month'      => $query->whereBetween($field, [now()->startOfMonth()->toDateTimeString(), now()->endOfMonth()->toDateTimeString()]),
            'last month' => $query->whereBetween($field, [now()->subMonth()->startOfMonth()->toDateTimeString(), now()->subMonth()->endOfMonth()->toDateTimeString()]),
            'year'       => $query->whereBetween($field, [now()->startOfYear()->toDateTimeString(), now()->endOfYear()->toDateTimeString()]),
            'last year'  => $query->whereBetween($field, [now()->subYear()->startOfYear()->toDateTimeString(), now()->subYear()->endOfYear()->toDateTimeString()]),
            'quarter'    => $query->whereBetween($field, [now()->startOfQuarter()->toDateTimeString(), now()->endOfQuarter()->toDateTimeString()]),
            'lately7'    => $query->whereBetween($field, [now()->subDays(6)->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()]),
            'lately30'   => $query->whereBetween($field, [now()->subDays(29)->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()]),
            default      => null,
        };
    }

    /**
     * 设置模型.
     *
     * @return mixed|string
     */
    protected function setModel()
    {
        return Log::class;
    }

    protected function setModelB(): string
    {
        return Admin::class;
    }
}
