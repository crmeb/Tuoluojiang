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

namespace App\Task\todo;

use App\Constants\TodoEnum;
use App\Events\BusinessDataChangeEvent;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

class TodoNoticeSourceSyncTask extends Task
{
    public function __construct(protected int $noticeId) {}

    public function handle()
    {
        try {
            BusinessDataChangeEvent::dispatch(TodoEnum::TYPE_NOTICE, $this->noticeId, 'create');
            return true;
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), [
                'noticeId' => $this->noticeId,
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
            ]);
        }
    }
}
