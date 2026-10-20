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

namespace App\Listeners;

use App\Http\Service\Approve\ApproveApplyService;
use App\Task\approve\ApprovedTask;
use App\Task\message\BusinessAdoptCcRemind;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 审批自动抄送
 * Class ApplySaveSuccess.
 */
class AutoCopy
{
    /**
     * 执行事件.
     * @param mixed $userService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(array $process, $applyId, $entId, $userService)
    {
        $applyService = app()->get(ApproveApplyService::class);
        if ($process) {
            $types = array_column($process, 'types');
            if (!in_array(1, $types)) {
                $userService->update(['apply_id' => $applyId, 'node_id' => array_column($process, 'uniqued')], ['status' => 1]);
                $applyService->update(['id' => $applyId], ['status' => 1, 'node_id' => $process[count($process) - 1]['uniqued']]);
                Task::deliver(new ApprovedTask((int)$applyId));
            } else {
                foreach ($process as $key => $value) {
                    if (!$key) {
                        if ($value['types'] == 2) {
                            $userService->update(['apply_id' => $applyId, 'node_id' => $value['uniqued']], ['status' => 1]);
                            // 抄送人【业务类型】审批通过提醒
                            Task::deliver(new BusinessAdoptCcRemind($entId, $applyId, $value['uniqued']));
                            Task::deliver(new ApprovedTask((int)$applyId));
                            if (!isset($process[$key + 1])) {
                                $applyService->update(['id' => $applyId], ['node_id' => $value['uniqued'], 'status' => 1]);
                            } else {
                                $applyService->update(['id' => $applyId], ['node_id' => $process[$key + 1]['uniqued']]);
                            }
                        }
                    } else {
                        if ($value['types'] == 2 && $process[$key - 1]['types'] == 2) {
                            $userService->update(['apply_id' => $applyId, 'node_id' => $value['uniqued']], ['status' => 1]);

                            // 抄送人【业务类型】审批通过提醒
                            Task::deliver(new BusinessAdoptCcRemind($entId, $applyId, $value['uniqued']));
                            Task::deliver(new ApprovedTask((int)$applyId));

                            if (!isset($process[$key + 1])) {
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
        } else {
            Task::deliver(new ApprovedTask((int)$applyId));
            $applyService->update(['id' => $applyId], ['status' => 1]);
        }
    }
}
