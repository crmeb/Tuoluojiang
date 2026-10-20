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
 * Agent 长期/会话记忆枚举.
 *
 * MemoryManager 按 type/scope 读写 agent_memory 表，注入后续对话上下文.
 */
final class AgentMemoryEnum
{
    /** 记忆类型：当前会话摘要. */
    public const TYPE_SESSION_SUMMARY = 'session_summary';

    /** 记忆类型：用户偏好. */
    public const TYPE_PREFERENCE = 'preference';

    /** 记忆类型：任务结论. */
    public const TYPE_CONCLUSION = 'conclusion';

    /** 记忆类型：结构化事实. */
    public const TYPE_FACT = 'fact';

    /** 作用域：仅当前会话可见. */
    public const SCOPE_SESSION = 'session';

    /** 作用域：跨会话跟随用户. */
    public const SCOPE_USER = 'user';

    /** 注入上下文时最多携带的记忆条数. */
    public const DEFAULT_MAX_MEMORIES = 20;

    /** 生成会话摘要时参考的最近对话轮数. */
    public const DEFAULT_SESSION_TURNS = 5;
}
