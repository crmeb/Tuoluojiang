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

namespace App\Http\Service\Approve;

use App\Http\Dao\Approve\ApproveUserDao;
use App\Http\Service\Crud\SystemCrudApproveProcessService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 审批流程人员.
 */
class ApproveUserService extends BaseService
{
    public function __construct(ApproveUserDao $dao)
    {
        $this->dao = $dao;
    }

    public function getUserList(array $where, array $field = ['*'], $sort = null, array $with = [])
    {
        return $this->dao->search($where)->when($sort, function ($query) use ($sort) {
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
        })->when($with, function ($query) use ($with) {
            $query->with($with);
        })->select($field)->get();
    }

    public function getValue(array $where, $field = null, $sort = null)
    {
        return $this->dao->search($where)->when($sort, function ($query) use ($sort) {
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
        })->value($field);
    }

    /**
     * 批量保存数据.
     * @param mixed $isCrud
     * @param mixed $data
     * @param mixed $approveId
     * @param mixed $applyId
     * @return bool
     * @throws BindingResolutionException
     */
    public function saveMore($data, $approveId, $applyId, $isCrud = false)
    {
        if ($isCrud) {
            $processService = app()->get(SystemCrudApproveProcessService::class);
        } else {
            $processService = app()->get(ApproveProcessService::class);
        }
        foreach ($data as $k => $value) {
            if ($value['users']) {
                foreach ($value['users'] as $key => $item) {
                    $processInfo = $processService->get(
                        ['uniqued' => $value['uniqued']],
                        ['name', 'types', 'settype', 'director_order', 'director_level', 'no_hander', 'dep_head', 'self_select', 'select_range', 'select_mode', 'examine_mode', 'pass_ratio']
                    )?->toArray();
                    if (! $processInfo) {
                        continue;
                    }
                    $this->dao->create([
                        'types'        => $value['types'],
                        'card_id'      => isset($item['value']) ? (int) $item['value'] : $item['id'],
                        'user_id'      => isset($item['value']) ? (int) $item['value'] : $item['id'],
                        'node_id'      => $value['uniqued'],
                        'approve_id'   => $approveId,
                        'apply_id'     => $applyId,
                        'info'         => $item,
                        'status'       => 0,
                        'process_info' => json_encode($processInfo),
                        'level'        => $k + 1,
                        'sort'         => $processInfo['examine_mode'] == 3 ? $key + 1 : 1,
                    ]);
                }
            }
        }
        return true;
    }

    /**
     * 获取用户审批状态
     * @param mixed $cardId
     * @param mixed $nodeId
     * @param mixed $id
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getVerifyStatus($cardId, $nodeId, $id)
    {
        $userInfo = $this->dao->get(['user_id' => $cardId, 'node_id' => $nodeId, 'apply_id' => $id, 'not_status' => -1], ['status', 'level', 'sort', 'process_info']);
        if (! $userInfo) {
            $verify_status = -1;
        } else {
            $userInfo = $userInfo->toArray();
            // 1、或签；2、会签；3、依次审批；(0、无此条件)
            switch ($userInfo['process_info']['examine_mode']) {
                case 1:
                case 2:
                    $verify_status = $userInfo['status'];
                    break;
                case 3:
                    if ($this->dao->exists(['node_id' => $nodeId, 'types' => 1, 'sort' => $userInfo['sort'] - 1, 'apply_id' => $id])) {
                        $nextStatus = $this->dao->value(['node_id' => $nodeId, 'types' => 1, 'sort' => $userInfo['sort'] - 1, 'apply_id' => $id], 'status');
                        if ($nextStatus == 1) {
                            $verify_status = $userInfo['status'];
                        } else {
                            $verify_status = -1;
                        }
                    } else {
                        $verify_status = $userInfo['status'];
                    }
                    break;
                default:
                    $verify_status = -1;
            }
        }
        return $verify_status;
    }

    /**
     * 自动处理审批申请.
     * @throws BindingResolutionException
     */
    public function processApply(array $process, int $applyId)
    {
        $applyService = app()->get(ApproveApplyService::class);
        $types        = array_column($process, 'types');
        if (! in_array(1, $types)) {
            $this->dao->update(['apply_id' => $applyId, 'node_id' => array_column($process, 'uniqued')], ['status' => 1]);
            $applyService->update(['id' => $applyId], ['status' => 1, 'node_id' => $process[count($process) - 1]['uniqued']]);
        } else {
            foreach ($process as $key => $value) {
                if (! $key) {
                    if ($value['types'] == 2) {
                        $this->dao->update(['apply_id' => $applyId, 'node_id' => $value['uniqued']], ['status' => 1]);
                        if (! isset($process[$key + 1])) {
                            $applyService->update(['id' => $applyId], ['node_id' => $value['uniqued'], 'status' => 1]);
                        } else {
                            $applyService->update(['id' => $applyId], ['node_id' => $value['uniqued']]);
                        }
                    }
                } else {
                    if ($value['types'] == 2 && $process[$key - 1]['types'] == 2) {
                        $this->dao->update(['apply_id' => $applyId, 'node_id' => $value['uniqued']], ['status' => 1]);
                        if (! isset($process[$key + 1])) {
                            $applyService->update(['id' => $applyId], ['status' => 1, 'node_id' => $value['uniqued']]);
                        } else {
                            $applyService->update(['id' => $applyId], ['node_id' => $value['uniqued']]);
                        }
                    } else {
                        break;
                    }
                }
            }
        }
    }

    /**
     * 获取分组唯一值
     * @param mixed $applyId
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUniques(int $applyId): array
    {
        return $this->dao->search(['apply_id' => $applyId])->groupBy('node_id')
            ->orderBy('level')
            ->pluck('node_id')?->toArray();
    }
}
