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
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveService;
use App\Http\Service\Approve\ApproveUserService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 抄送人【业务类型】审批通过提醒
 * Class BusinessAdoptCcRemind.
 */
class BusinessAdoptCcRemind extends Task
{
    public function __construct(protected $entId, protected $applyId, protected $nodeId) {}

    public function handle()
    {
        try {
            $applyService = app()->get(ApproveApplyService::class);
            $applyInfo    = $applyService->get($this->applyId, ['user_id', 'node_id', 'status', 'approve_id', 'created_at'], ['card' => fn ($q) => $q->with(['frame'])])?->toArray();
            if (! $applyInfo) {
                return;
            }

            $approveUser = app()->get(ApproveUserService::class)->select([
                'node_id'    => $this->nodeId,
                'apply_id'   => $this->applyId,
                'types'      => 2,
                'approve_id' => $applyInfo['approve_id'],
            ], ['*'])?->toArray();
            if (! $approveUser) {
                return;
            }
            $userIds = array_column($approveUser, 'user_id');
            if (! $userIds) {
                return;
            }
            event(new SystemMessageEvent(
                type: NoticeEnum::BUSINESS_ADOPT_CC_TYPE,
                params: [
                    '申请人部门' => $applyInfo['card']['frame']['name'] ?? '',
                    '申请人'     => $applyInfo['card']['name'] ?? '',
                    '申请时间'   => $applyInfo['created_at'],
                    '业务类型'   => app()->get(ApproveService::class)->value($applyInfo['approve_id'], 'name'),
                ],
                receiverIds: $userIds,
                other: [
                    'id' => $this->applyId,
                ],
                linkId: $this->applyId,
                linkStatus: $applyInfo['status'],
            ));
            Task::deliver(new StatusChangeTask(ApproveEnum::LINK_NOTICE, $applyInfo['status'], $this->entId, $this->applyId));
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
