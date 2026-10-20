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

namespace App\Listeners\Config;

use App\Constants\Ai\AiModelEnum;
use App\Events\Config\YihaotongConfigChangedEvent;
use App\Jobs\Ai\YihaotongAiModelSyncJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 一号通配置变更 → 清 AI Key 缓存，并派发默认 AI 模型配置同步任务.
 */
class YihaotongAiModelSyncListener
{
    /**
     * @param YihaotongConfigChangedEvent $event 一号通配置变更事件
     */
    public function handle(YihaotongConfigChangedEvent $event): void
    {
        try {
            Cache::forget(AiModelEnum::YIHAOTONG_API_KEY_CACHE);
            $this->dispatchAiModelSyncJob();
        } catch (\Throwable $e) {
            Log::warning('YihaotongAiModelSyncListener failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }

    /**
     * 派发一号通默认 AI 模型 upsert 任务（事务提交后再入队）.
     */
    protected function dispatchAiModelSyncJob(): void
    {
        YihaotongAiModelSyncJob::dispatch();
    }
}
