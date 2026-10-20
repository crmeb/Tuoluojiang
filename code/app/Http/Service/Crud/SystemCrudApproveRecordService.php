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

use App\Constants\Crud\CrudLogTypeEnum;
use App\Http\Dao\Crud\SystemCrudApproveRecordDao;
use App\Http\Service\Approve\ApproveUserService;
use App\Jobs\Crud\CrudLogJob;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 低代码审批数据记录.
 */
class SystemCrudApproveRecordService extends BaseService
{
    /**
     * SystemCrudApproveRecordDao constructor.
     */
    public function __construct(SystemCrudApproveRecordDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 处理审批后数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function afterApproved(int $applyId): void
    {
        $info = $this->dao->get(['approve_id' => $applyId])?->toArray();

        if (! $info) {
            return;
        }

        if (app()->get(SystemCrudApproveService::class)->value($applyId, 'status') == -1) {
            $userId = $info['user_id'];
        } else {
            $userId = app()->get(ApproveUserService::class)->value(['apply_id' => $applyId, 'status' => 2], 'user_id');
        }
        $crudService         = app()->get(CrudModuleService::class)->model($info['table_name']);
        $scheduleTableInfo   = app()->get(SystemCrudService::class)->get(['crud_id' => $info['crud_id']], ['table_name_en', 'id']);
        $scheduleTableNameEn = null;
        $scheduleTableId     = 0;
        if ($scheduleTableInfo) {
            $scheduleTableNameEn = $scheduleTableInfo['table_name_en'];
            $scheduleTableId     = $scheduleTableInfo['id'];
        }

        switch ($info['event']) {
            case 'create':
                $crudService->delete($info['data_id']);
                if ($scheduleTableNameEn) {
                    app()->get(CrudModuleService::class)->model($scheduleTableNameEn)->delete([$info['table_name'] . '_id' => $info['data_id']]);
                }
                break;
            case 'update':
                $crudService->update($info['data_id'], $info['original_data']);
                // 恢复附表
                if ($info['original_schedule_data'] && $scheduleTableNameEn) {
                    app()->get(CrudModuleService::class)->model($scheduleTableNameEn)->update([$info['table_name'] . '_id' => $info['data_id']], $info['original_schedule_data']);
                }
                foreach ($info['original_data'] as $k => $v) {
                    if (! isset($info['data'][$k])) {
                        continue;
                    }
                    CrudLogJob::dispatch([
                        'uid'                  => $userId,
                        'crud_id'              => $info['crud_id'],
                        'data_crud_id'         => $info['crud_id'],
                        'data_id'              => $info['data_id'],
                        'change_field_name_en' => $k,
                        'before_value'         => $info['data'][$k],
                        'after_value'          => $v,
                        'log_type'             => CrudLogTypeEnum::APPROVE_TYPE,
                    ]);
                }
                foreach ($info['original_schedule_data'] as $k => $v) {
                    if (! isset($info['schedule_data'][$k])) {
                        continue;
                    }
                    CrudLogJob::dispatch([
                        'uid'                  => $userId,
                        'crud_id'              => $info['crud_id'],
                        'data_crud_id'         => $scheduleTableId,
                        'data_id'              => $info['data_id'],
                        'change_field_name_en' => $k,
                        'before_value'         => $info['schedule_data'][$k],
                        'after_value'          => $v,
                        'log_type'             => CrudLogTypeEnum::APPROVE_TYPE,
                    ]);
                }

                break;
            case 'delete':
                $crudService->withTrashed()->restore($info['data_id']);
                // 恢复附表
                if ($scheduleTableNameEn) {
                    app()->get(CrudModuleService::class)->model($scheduleTableNameEn)->withTrashed()->restore([$info['table_name'] . '_id' => $info['data_id']]);
                }
                break;
            default:
                break;
        }
    }
}
