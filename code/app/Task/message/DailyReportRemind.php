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

use App\Constants\DailyEnum;
use App\Constants\NoticeEnum;
use App\Events\SystemMessageEvent;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 日报汇报提醒
 * Class DailyReportRemind.
 */
class DailyReportRemind extends Task
{
    public function __construct(protected $entId, protected $uuid, protected $id, protected $data, protected $isStore = false) {}

    public function handle()
    {
        try {
            $userList = app(FrameService::class)->getUserFrameAdminList($this->uuid);
            if ($userList) {
                $userInfo = app(AdminService::class)->get(['uid' => $this->uuid], with: ['frame'])?->toArray();
                $typeStr  = match ((int) $this->data['types']) {
                    1       => '周报',
                    2       => '月报',
                    3       => '汇报',
                    default => '日报',
                };
                event(new SystemMessageEvent(
                    type: $this->isStore ? NoticeEnum::DAILY_SHOW_REMIND_TYPE : NoticeEnum::DAILY_UPDATE_REMIND_TYPE,
                    params: [
                        '汇报人'     => $userInfo['name'],
                        '汇报人部门' => $userInfo['frame']['name'] ?? '',
                        '汇报类型'   => $typeStr,
                        '工作内容'   => implode("\n", $this->data['finish']),
                        '工作计划'   => implode("\n", $this->data['plan']),
                        '备注内容'   => $this->data['mark'],
                    ],
                    receiverIds: $userList,
                    other: ['id' => $this->id],
                    linkId: (int) $this->id,
                    linkStatus: 1
                ));
                Task::deliver(new StatusChangeTask(DailyEnum::LINK_NOTICE, DailyEnum::DAILY_SUB, $this->entId, $this->id));
                Task::deliver(new StatusChangeTask(DailyEnum::Not_Link_Notice, DailyEnum::DAILY_SUB, $this->entId, $this->id, uuid_to_uid($this->uuid, $this->entId), 'today'));
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
