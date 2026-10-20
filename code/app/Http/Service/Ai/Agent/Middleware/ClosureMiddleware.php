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
 * 将闭包适配为 AgentMiddlewareInterface，便于在 Service 内组链且保持 private 封装.
 */
final class ClosureMiddleware implements AgentMiddlewareInterface
{
    /** @var callable(AgentStreamContext, callable): AgentStreamContext */
    private $callback;

    /**
     * @param callable(AgentStreamContext, callable): AgentStreamContext $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function handle(AgentStreamContext $context, callable $next): AgentStreamContext
    {
        return ($this->callback)($context, $next);
    }
}
