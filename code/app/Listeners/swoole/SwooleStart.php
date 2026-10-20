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

namespace App\Listeners\swoole;

use App\Http\Service\System\RolesService;
use Hhxsv5\LaravelS\Swoole\Events\WorkerStartInterface;
use Illuminate\Support\Facades\Cache;
use Swoole\Http\Server;

/**
 * swoole启动事件。
 * Worker#0 在已安装环境下初始化 Casbin 规则；缺表/未装库时仅告警，不阻断启动（便于同目录多 Docker 实例）。
 */
class SwooleStart implements WorkerStartInterface
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    public function handle(Server $server, $workerId)
    {
        if (! $workerId && file_exists(public_path('install/install.lock'))) {
            $lockKey = 'swoole:init-rules:lock';
            if (! Cache::add($lockKey, getmypid(), 60)) {
                return;
            }

            try {
                app()->get(RolesService::class)->initRules();
            } catch (\Throwable $e) {
                // 同机多套 Docker 共用代码目录时，新实例库可能尚未安装；避免 Worker 因缺表直接崩溃
                echo sprintf(
                    "[%s] [WARN] initRules skipped: %s\n",
                    date('Y-m-d H:i:s'),
                    $e->getMessage()
                );
            } finally {
                Cache::forget($lockKey);
            }
        }
    }
}
