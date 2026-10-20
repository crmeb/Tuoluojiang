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

namespace App\Http\Service\Crud;

use App\Http\Dao\Crud\SystemCrudEventLogDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

/**
 * 触发器日志
 * Class SystemCrudEventLogService.
 * @email 136327134@qq.com
 * @date 2024/3/14
 */
class SystemCrudEventLogService extends BaseService
{
    /**
     * SystemCrudEventLogService constructor.
     */
    public function __construct(SystemCrudEventLogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 记录执行日志.
     * @email 136327134@qq.com
     * @date 2024/3/20
     */
    public function saveLog(int $crudId, int $eventId, string $action, string $result, array $parameter = [], array $log = [])
    {
        try {
            $this->dao->create([
                'crud_id'   => $crudId,
                'event_id'  => $eventId,
                'action'    => $action,
                'result'    => $result,
                'parameter' => $parameter,
                'log'       => $log,
            ]);
        } catch (\Throwable $exc) {
            Log::error('执行事件写入日志失败:' . $exc->getMessage());
        }
    }

    /**
     * 获取日志列表.
     * @return array
     * @throws BindingResolutionException
     */
    public function getLogList(array $where = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->joinSearch($where)->forPage($page, $limit)->select([
            'system_crud_event_log.*',
            'system_crud_event.name',
            'system_crud_event.event',
            'system_crud_event.action',
        ])->with(['crud' => fn ($q) => $q->select(['id', 'table_name'])])->groupBy('system_crud_event_log.id')->orderBy('system_crud_event_log.id', 'desc')->get()->toArray();

        $count = $this->dao->joinSearch($where)->count();
        return $this->listData($list, $count);
    }
}
