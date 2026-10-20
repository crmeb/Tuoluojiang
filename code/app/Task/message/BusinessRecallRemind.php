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

namespace App\Task\message;

use App\Constants\ApproveEnum;
use App\Constants\NoticeEnum;
use App\Events\SystemMessageEvent;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Approve\ApproveProcessService;
use App\Http\Service\Approve\ApproveService;
use App\Http\Service\Approve\ApproveUserService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 撤回消息提醒
 * Class BusinessRecallRemind.
 */
class BusinessRecallRemind extends Task
{
    public function __construct(protected $entId, protected $userId, protected $applyInfo) {}

    public function handle()
    {
        try {
            Task::deliver(new StatusChangeTask(ApproveEnum::LINK_NOTICE, ApproveEnum::APPROVE_RECALL, $this->entId, $this->applyInfo['id']));
            $nodeIds = app()->get(ApproveProcessService::class)->column(['entid' => $this->entId, 'approve_id' => $this->applyInfo['approve_id'], 'types' => 1], 'uniqued');
            if (! $nodeIds) {
                return;
            }
            $userIds = app()->get(ApproveUserService::class)->column([
                'apply_id'   => $this->applyInfo['id'],
                'approve_id' => $this->applyInfo['approve_id'],
                'status'     => 1,
                'node_ids'   => $nodeIds,
            ], 'user_id');

            $adminService = app()->get(AdminService::class);
            $selfUserInfo = $adminService->get($this->userId, with: ['frame'])?->toArray();
            $approveName  = app()->get(ApproveService::class)->value($this->applyInfo['approve_id'], 'name');
            event(new SystemMessageEvent(
                type: NoticeEnum::BUSINESS_RECALL_TYPE,
                params: [
                    '申请人部门' => $selfUserInfo['frame']['name'] ?? '',
                    '申请人单位' => $selfUserInfo['frame']['name'] ?? '',
                    '申请人'     => $selfUserInfo['name'] ?? '',
                    '申请时间'   => $this->applyInfo['created_at'],
                    '业务类型'   => $approveName,
                ],
                receiverIds: $userIds,
                other: [
                    'id' => $this->applyInfo['id'],
                ],
                linkId: $this->applyInfo['id'],
                linkStatus: -1
            ));
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
