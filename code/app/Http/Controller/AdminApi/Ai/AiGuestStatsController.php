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
use App\Http\Dao\Ai\AiApplicationDao;
use App\Http\Service\Ai\AiApplicationService;
use App\Http\Service\Ai\Guest\AiGuestStatsService;
use Illuminate\Contracts\View\View;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\WhereNumber;

/**
 * 外部 AI 访客会话统计（管理端）.
 *
 * 路由前缀 ent/ai/applications/{appId}/guest/*，需 auth.admin + ent.auth.
 * 通过 getShareSettings 校验编辑权限，仅外部应用可访问.
 */
#[Prefix('ent/ai/applications')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AiGuestStatsController extends AuthController
{
    public function __construct(
        private AiGuestStatsService $statsService,
        private AiApplicationService $appService
    ) {
        parent::__construct();
    }

    /**
     * 访客统计概览 JSON.
     *
     * @param int $appId AI 应用 ID
     */
    #[Get('{appId}/guest/stats', '获取AI应用访客会话统计')]
    #[WhereNumber('appId')]
    public function stats(int $appId)
    {
        [$days] = $this->request->getMore([
            ['days', 7],
        ], true);
        $this->appService->getShareSettings($appId);

        return $this->success($this->statsService->overview($appId, (int) $days));
    }

    /**
     * 访客会话日趋势 JSON.
     *
     * @param int $appId AI 应用 ID
     */
    #[Get('{appId}/guest/stats/daily', '获取AI应用访客会话日趋势')]
    #[WhereNumber('appId')]
    public function daily(int $appId)
    {
        [$days] = $this->request->getMore([
            ['days', 7],
        ], true);
        $this->appService->getShareSettings($appId);

        return $this->success([
            'app_id' => $appId,
            'days'   => (int) $days,
            'daily'  => $this->statsService->dailyTrend($appId, (int) $days),
        ]);
    }

    /**
     * 访客统计 HTML 看板（浏览器直接访问）.
     *
     * @param int $appId AI 应用 ID
     */
    #[Get('{appId}/guest/dashboard', '访客会话统计看板')]
    #[WhereNumber('appId')]
    public function dashboard(int $appId): View
    {
        $this->appService->getShareSettings($appId);
        [$days] = $this->request->getMore([
            ['days', 14],
        ], true);
        $days  = max(7, min(30, (int) $days));
        $stats = $this->statsService->overview($appId, $days);
        $app   = app(AiApplicationDao::class)->get($appId);

        return view('ai.guest-stats-dashboard', [
            'app'   => $app,
            'stats' => $stats,
            'days'  => $days,
        ]);
    }
}
