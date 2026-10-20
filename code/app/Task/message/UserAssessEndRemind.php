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

use App\Constants\AssessEnum;
use App\Constants\NoticeEnum;
use App\Events\SystemMessageEvent;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Assess\UserAssessService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 绩效结束发送结束提醒和给自己提醒绩效分数
 * Class UserAssessEndRemind.
 */
class UserAssessEndRemind extends Task
{
    public function __construct(protected $entId, protected $userId, protected $id) {}

    public function handle()
    {
        try {
            if (! $this->userId) {
                return;
            }
            $userAssess = app()->get(UserAssessService::class)->get($this->id, ['id', 'name', 'test_uid', 'period', 'score', 'start_time', 'end_time', 'status']);
            $timeStr    = get_period_type_str((int) $userAssess['period']);
            $userInfo   = app()->get(AdminService::class)->get((int) $userAssess['test_uid'], with: ['frame'])->toArray();
            event(new SystemMessageEvent(
                type: NoticeEnum::ASSESS_RESULT_END_TYPE,
                params: [
                    '考核名称'       => $userAssess->name,
                    '考核类型'       => $timeStr . '考核',
                    '考核周期'       => $timeStr,
                    '开始时间'       => $userAssess->start_time,
                    '结束时间'       => $userAssess->end_time,
                    '被考核人'       => $userInfo['name'] ?? '',
                    '被考核人人部门' => $userInfo['frame']['name'] ?? '',
                    '最终得分'       => $userAssess['score'],
                ],
                receiverIds: (int) $userAssess['test_uid'],
                other: ['id' => $this->id],
                linkId: $this->id,
                linkStatus: $userAssess['status']
            ));
            Task::deliver(new StatusChangeTask(AssessEnum::LINK_NOTICE, AssessEnum::ASSESS_FINISH, $this->entId, $this->id));
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
