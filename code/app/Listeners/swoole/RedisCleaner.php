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

namespace App\Listeners\swoole;

use Hhxsv5\LaravelS\Illuminate\Cleaners\BaseCleaner;
use Illuminate\Support\Facades\Redis;

/**
 * Swoole 环境下的 Redis 连接清理器.
 *
 * 采用失败再重连：不在每个请求主动 PING，仅在命令失败时 reconnect。
 */
class RedisCleaner extends BaseCleaner
{
    /**
     * 惰性检查并恢复失效连接.
     */
    public function clean(): void
    {
        $this->ensureConnection('default');
        $this->ensureConnection('cache');
    }

    /**
     * 探测连接是否可用，失败则重连.
     *
     * @param string $connection 连接名称
     */
    private function ensureConnection(string $connection): void
    {
        try {
            Redis::connection($connection)->client();
        } catch (\Throwable $e) {
            try {
                Redis::connection($connection)->reconnect();
            } catch (\Throwable $reconnectError) {
                logger()->warning("Redis 连接重连失败: {$connection}", [
                    'error' => $reconnectError->getMessage(),
                ]);
            }
        }
    }
}
