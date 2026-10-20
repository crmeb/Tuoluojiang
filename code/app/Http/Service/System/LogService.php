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

namespace App\Http\Service\System;

use App\Http\Contract\System\LogInterface;
use App\Http\Dao\System\LogDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * 企业日志
 * Class LogService.
 */
class LogService extends BaseService implements LogInterface
{
    protected array $filter = [
        'api/ent/enterprise/log',
        'api/ent/system/log',
    ];

    public function __construct(LogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 日志查询.
     * @throws BindingResolutionException
     */
    public function getLogPageList(array $where, int $page = 1, int $limit = 20, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit] = $this->resolvePageLimit($page, $limit);
        return $this->dao->getLogList($where, $page, $limit);
    }

    /**
     * 日志表数据量大，必须在服务层兜住异常分页参数，避免一次 fetchAll 几十万行。
     */
    protected function resolvePageLimit(int $page, int $limit): array
    {
        if ($page === 1 && $limit === 20) {
            [$page, $limit] = $this->getPageValue();
        }

        $limitMax = max(1, (int) Config::get('database.page.limitMax', 100));
        $page     = max(1, $page);
        $limit    = $limit > 0 ? min($limit, $limitMax) : $limitMax;

        return [$page, $limit];
    }

    /**
     * 保存日志.
     * @throws BindingResolutionException
     */
    public function createLog(string $userId, int $entId, string $userName, string $type): bool
    {
        $request = app()->request;
        $route   = $request->route();
        $rule    = $route?->uri() ?? $request->path();
        if (in_array($rule, $this->filter)) {
            return true;
        }
        return $this->createLogFromData([
            'method'     => $request->method(),
            'uid'        => $userId,
            'entid'      => $entId,
            'user_name'  => $userName,
            'path'       => $rule,
            'event_name' => $route?->getName() ?: '未知',
            'last_ip'    => $request->server('HTTP_X_REAL_IP') ?: $request->ip(),
            'type'       => $type,
            'terminal'   => get_os(),
        ]);
    }

    /**
     * 保存已整理的日志数据.
     * @throws BindingResolutionException
     */
    public function createLogFromData(array $data): bool
    {
        if (! Schema::hasTable($this->dao->table)) {
            return false;
        }
        return (bool) $this->dao->getModel(false)->create($data);
    }

    /**
     * 清理过期日志，默认保留一年.
     */
    public function deleteExpiredLogs(int $months = 12): int
    {
        $expiredAt = now()->subMonths($months)->toDateTimeString();

        return $this->dao->deleteExpiredLogs($expiredAt);
    }
}
