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
 * 顺序执行 Agent 流式前置中间件.
 */
final class AgentMiddlewarePipeline
{
    /** @var list<AgentMiddlewareInterface> */
    private array $middlewares;

    /**
     * @param list<AgentMiddlewareInterface> $middlewares
     */
    public function __construct(array $middlewares = [])
    {
        $this->middlewares = $middlewares;
    }

    /**
     * @param list<AgentMiddlewareInterface> $middlewares
     */
    public static function make(array $middlewares): self
    {
        return new self($middlewares);
    }

    public function run(AgentStreamContext $context): AgentStreamContext
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            static function (callable $next, AgentMiddlewareInterface $middleware): callable {
                return static function (AgentStreamContext $ctx) use ($middleware, $next): AgentStreamContext {
                    return $middleware->handle($ctx, $next);
                };
            },
            static fn (AgentStreamContext $ctx): AgentStreamContext => $ctx
        );

        return $pipeline($context);
    }
}
