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
use Illuminate\Support\Facades\DB;

/**
 * Swoole 环境下的数据库连接清理器.
 *
 * 采用失败再重连：不在每个请求主动 SELECT 1，仅在连接不可用时 reconnect，
 * 降低常驻 Worker 的额外开销并避免无意义的连接抖动。
 */
class DatabaseCleaner extends BaseCleaner
{
    /**
     * 惰性检查并恢复失效连接.
     */
    public function clean(): void
    {
        $this->ensureConnection('mysql');
    }

    /**
     * 探测连接是否可用，失败则重连.
     *
     * @param string $connection 连接名称
     */
    private function ensureConnection(string $connection): void
    {
        try {
            if (! DB::connection($connection)->getPdo() instanceof \PDO) {
                DB::connection($connection)->reconnect();
            }
        } catch (\Throwable $e) {
            try {
                DB::connection($connection)->reconnect();
            } catch (\Throwable $reconnectError) {
                logger()->warning("数据库连接重连失败: {$connection}", [
                    'error' => $reconnectError->getMessage(),
                ]);
            }
        }
    }
}
