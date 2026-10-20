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

namespace App\Jobs\Work;

use App\Http\Service\Work\WorkMessageSeqService;
use App\Http\Service\Work\WorkMessageService;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Facades\Log;

/**
 * 会话存档消息定时获取.
 */
class WorkMessageSaveCronJob extends CronJob
{
    /**
     * 频率：每60秒运行一次
     * @return int
     */
    public function interval()
    {
        return 60000;
    }

    /**
     * 执行任务
     */
    public function run(): void
    {
        if (! sys_config('wechat_work_session_switch')) {
            return;
        }

        $workConfig = app()->get(WorkConfig::class);
        if (! $workConfig->getCorpId()) {
            return;
        }

        $sessionInfo = $workConfig->getAppConfig(WorkConfig::TYPE_SESSION);
        if (empty($sessionInfo['secret'])) {
            Log::error('会话存档secret配置不完整');
            return;
        }
        if (empty($sessionInfo['public_key_version'])) {
            Log::error('会话存档public_key_version配置不完整');
            return;
        }
        if (empty($sessionInfo['private_key'])) {
            Log::error('会话存档private_key配置不完整');
            return;
        }

        $seqService = app()->get(WorkMessageSeqService::class);
        $seq        = $seqService->value(['corp_id' => $workConfig->getCorpId()], 'seq');
        if (! $seq) {
            $seq = 0;
        } else {
            $seq = (int) $seq;
        }

        app()->get(WorkMessageService::class)->getWorkMessages($workConfig, $seq);
    }
}
