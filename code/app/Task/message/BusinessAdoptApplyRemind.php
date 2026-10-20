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
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 申请人【业务类型】审批通过提醒
 * Class BusinessAdoptApplyRemind.
 */
class BusinessAdoptApplyRemind extends Task
{
    public function __construct(protected $entId, protected $userId, protected $applyId) {}

    public function handle()
    {
        try {
            $applyInfo = app()->get(ApproveApplyService::class)->get($this->applyId, ['node_id', 'user_id', 'status', 'approve_id', 'created_at'])?->toArray();
            if (! $applyInfo) {
                return;
            }
            if ($applyInfo['status'] != 1) {
                return;
            }
            $selfUserInfo = app()->get(AdminService::class)->get($applyInfo['user_id'], with: ['frame'])?->toArray();
            $approveName  = app()->get(ApproveService::class)->value($applyInfo['approve_id'], 'name');
            event(new SystemMessageEvent(
                type: NoticeEnum::BUSINESS_ADOPT_APPLY_TYPE,
                params: [
                    '申请人部门' => $selfUserInfo['frame']['name'] ?? '',
                    '申请人'     => $selfUserInfo['name'] ?? '',
                    '申请时间'   => $applyInfo['created_at'],
                    '业务类型'   => $approveName,
                ],
                receiverIds: (int) $applyInfo['user_id'],
                other: [
                    'id' => $this->applyId,
                ],
                linkId: $this->applyId,
                linkStatus: $applyInfo['status'],
            ));
            Task::deliver(new StatusChangeTask(ApproveEnum::LINK_NOTICE, ApproveEnum::APPROVE_PASSED, $this->entId, $this->applyId));
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
