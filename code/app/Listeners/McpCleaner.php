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

namespace App\Listeners;

use App\Http\Controller\Mcp\McpRequest;
use Hhxsv5\LaravelS\Illuminate\Cleaners\BaseCleaner;
use Hhxsv5\LaravelS\Illuminate\Cleaners\CleanerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;

/**
 * MCP 请求结束清理，避免 Swoole 长生命周期下上下文串扰.
 *
 * 数据权限等通用请求态由 RequestStateCleaner 统一处理；此处仅保留 MCP 特有逻辑。
 */
class McpCleaner extends BaseCleaner implements CleanerInterface
{
    public function clean(): void
    {
        if ($this->currentApp->bound(McpRequest::class)) {
            $this->currentApp->make(McpRequest::class)->resetRequestCache();
        }

        // 清理 MCP 注入的 admin 守卫，避免跨请求身份串扰
        try {
            Auth::guard('admin')->forgetUser();
        } catch (\Throwable) {
        }
        Facade::clearResolvedInstance('auth');
    }
}
