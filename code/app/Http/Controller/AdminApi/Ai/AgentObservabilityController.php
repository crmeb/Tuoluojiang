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

namespace App\Http\Controller\AdminApi\Ai;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Ai\Agent\Trace\AgentTracer;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\WhereNumber;

/**
 * Agent 可观测性控制器.
 *
 * 路由前缀 ent/ai/agent，对应表 agent_runs、agent_steps 等.
 * 鉴权：auth.admin + ent.auth + ent.log（JWT 管理端登录）.
 * 提供单次运行详情与应用级 Agent 统计数据.
 */
#[Prefix('ent/ai/agent')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AgentObservabilityController extends AuthController
{
    public function __construct(AgentTracer $tracer)
    {
        parent::__construct();
        $this->service = $tracer;
    }

    /**
     * 获取单次 Agent 运行详情（含步骤列表）.
     *
     * @param int $runId 运行记录 ID
     */
    #[Get('runs/{runId}', '获取Agent运行详情')]
    #[WhereNumber('runId')]
    public function runDetail(int $runId)
    {
        $uid = (int) auth('admin')->id();

        return $this->success($this->service->detail($runId, $uid));
    }

    /**
     * 获取指定 AI 应用的 Agent 运行统计概览.
     *
     * @param int $appId AI 应用 ID
     */
    #[Get('applications/{appId}/stats', '获取AI应用Agent统计')]
    #[WhereNumber('appId')]
    public function appStats(int $appId)
    {
        $uid = (int) auth('admin')->id();
        $days = (int) $this->request->get('days', 30);

        return $this->success($this->service->appStats($appId, $uid, $days));
    }
}
