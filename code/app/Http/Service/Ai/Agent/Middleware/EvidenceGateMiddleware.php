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
 * 证据闸门开关写入上下文（实际拦截在 ToolLoopExecutor，此处可单测配置契约）.
 */
final class EvidenceGateMiddleware implements AgentMiddlewareInterface
{
    public function handle(AgentStreamContext $context, callable $next): AgentStreamContext
    {
        try {
            $enabled = (bool) config('ai.accuracy.require_evidence', true);
        } catch (\Throwable) {
            $enabled = true;
        }
        $context->bag['require_evidence'] = $enabled;

        return $next($context);
    }
}
