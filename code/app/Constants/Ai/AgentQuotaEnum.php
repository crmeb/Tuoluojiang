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

namespace App\Constants\Ai;

/**
 * Agent 配额默认值.
 *
 * 可被应用 agent_json 覆盖；AgentRuntime 按「应用 + 用户 + 日」统计 Run 次数与 Token，
 * 并发槽按「应用 + 用户」隔离，并使用独立短 TTL（CONCURRENT_TTL_SECONDS）以防异常退出后占满.
 */
final class AgentQuotaEnum
{
    /** 单应用下每用户每日 Agent Run 次数上限（默认值）. */
    public const DEFAULT_DAILY_RUNS_PER_USER = 200;

    /** 单应用下每用户每日 Agent 消耗 Token 上限（默认值）. */
    public const DEFAULT_DAILY_TOKENS_PER_USER = 5000000;

    /** 单应用下每用户同时进行的 Agent Run 上限（默认值）. */
    public const DEFAULT_MAX_CONCURRENT_RUNS = 3;

    /** 日维度 Run/Token 计数 Cache TTL（秒），跨日自动失效需配合日期键. */
    public const CACHE_TTL_SECONDS = 172800;

    /**
     * 并发 Run 计数 Cache TTL（秒）.
     *
     * 须短于日配额 TTL：进程/Worker 异常退出导致未 release 时，靠过期自愈，避免槽位占满两天.
     */
    public const CONCURRENT_TTL_SECONDS = 900;
}
