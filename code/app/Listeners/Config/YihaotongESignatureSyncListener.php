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

use App\Constants\CacheEnum;
use App\Events\Config\YihaotongConfigChangedEvent;
use App\Http\Service\Config\SystemConfigService;
use App\Jobs\Config\SignStaffJob;
use App\Jobs\Config\SignStaffSyncJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 一号通配置变更 → 同步电子签开通状态，并在已开通时派发员工绑定/同步队列.
 */
class YihaotongESignatureSyncListener
{
    public function __construct(protected ?SystemConfigService $configService = null) {}

    /**
     * @param YihaotongConfigChangedEvent $event 一号通配置变更事件
     */
    public function handle(YihaotongConfigChangedEvent $event): void
    {
        try {
            $result = ($this->configService ?? app(SystemConfigService::class))->syncSignatureStatus();

            if ((int) ($result['is_auth'] ?? 0) === 1) {
                $this->dispatchSignStaffJobs();
            }
        } catch (\Throwable $e) {
            Log::warning('YihaotongESignatureSyncListener failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }

    /**
     * 派发远端操作员回写与本地员工绑定任务（事务提交后再入队）.
     */
    protected function dispatchSignStaffJobs(): void
    {
        SignStaffSyncJob::dispatch();
        SignStaffJob::dispatch();
    }
}
