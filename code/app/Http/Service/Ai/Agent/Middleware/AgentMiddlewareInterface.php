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

namespace App\Http\Service\Ai\Agent\Middleware;

/**
 * Agent 流式前置中间件契约（对齐 laravel/ai HasMiddleware 语义，T-3）.
 */
interface AgentMiddlewareInterface
{
    /**
     * @param callable(AgentStreamContext): AgentStreamContext $next
     */
    public function handle(AgentStreamContext $context, callable $next): AgentStreamContext;
}
