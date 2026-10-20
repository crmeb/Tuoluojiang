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
 * AI 会话与应用相关枚举.
 *
 * 涵盖应用状态、插件来源位图、应用/会话类型、消息角色与状态、流式与 MCP 缓存常量.
 */
final class AiConversationEnum
{
    /** 应用或会话：禁用/隐藏. */
    public const STATUS_DISABLED = 0;

    /** 应用或会话：启用/正常. */
    public const STATUS_ENABLED = 1;

    /** 插件来源：无额外插件（仅基础对话）. */
    public const SOURCE_NORMAL = 0;

    /** 插件来源：数据库/SQL 查询（位图第 1 位）. */
    public const SOURCE_SQL = 1;

    /** 插件来源：MCP 工具（位图第 2 位）. */
    public const SOURCE_MCP = 2;

    /** 插件来源：知识库 RAG（位图第 3 位）. */
    public const SOURCE_KNOWLEDGE = 4;

    /** 全部插件来源常量列表（含历史数据库插件，仅用于解析存量数据）. */
    public const SOURCE_TYPES = [
        self::SOURCE_NORMAL,
        self::SOURCE_SQL,
        self::SOURCE_MCP,
        self::SOURCE_KNOWLEDGE,
    ];

    /**
     * AI 应用关联时可绑定的插件类型（含只读数据库查询）.
     */
    public const BINDABLE_SOURCE_TYPES = [
        self::SOURCE_NORMAL,
        self::SOURCE_SQL,
        self::SOURCE_MCP,
        self::SOURCE_KNOWLEDGE,
    ];

    /** 应用类型：内部应用（需 auth_ids，走 JWT 登录会话）. */
    public const APP_TYPE_INTERNAL = 0;

    /** 应用类型：外部应用（支持 share_key 访客免登录）. */
    public const APP_TYPE_EXTERNAL = 1;

    /** 全部应用类型. */
    public const APP_TYPES = [
        self::APP_TYPE_INTERNAL,
        self::APP_TYPE_EXTERNAL,
    ];

    /** 会话类型：登录用户（user_id>0）. */
    public const SESSION_TYPE_USER = 0;

    /** 会话类型：外部访客（user_id=0，guest_token 标识）. */
    public const SESSION_TYPE_GUEST = 1;

    /** 全部会话类型. */
    public const SESSION_TYPES = [
        self::SESSION_TYPE_USER,
        self::SESSION_TYPE_GUEST,
    ];

    /**
     * 判断位图 sourceType 是否包含指定来源.
     */
    public static function hasSource(int $sourceType, int $source): bool
    {
        return ($sourceType & $source) === $source;
    }

    /**
     * 将位图转为已启用插件类型数组（不含 SOURCE_NORMAL）.
     *
     * @return array<int, int>
     */
    public static function sourceTypes(int $sourceType): array
    {
        if ($sourceType === self::SOURCE_NORMAL) {
            return [self::SOURCE_NORMAL];
        }

        return array_values(array_filter(
            self::SOURCE_TYPES,
            fn (int $source): bool => $source !== self::SOURCE_NORMAL && self::hasSource($sourceType, $source)
        ));
    }

    /**
     * 将前端多选数组或整数转为 source_type 位图存储值.
     */
    public static function sourceTypeValue(mixed $value): int
    {
        if (! is_array($value)) {
            return (int) $value & (self::SOURCE_SQL | self::SOURCE_MCP | self::SOURCE_KNOWLEDGE);
        }

        $types = array_values(array_unique(array_intersect(array_map('intval', $value), self::SOURCE_TYPES)));
        if (! $types || in_array(self::SOURCE_NORMAL, $types, true)) {
            return self::SOURCE_NORMAL;
        }

        return array_reduce($types, fn (int $carry, int $type): int => $carry | $type, 0);
    }

    /** OpenAI 消息角色：系统提示. */
    public const ROLE_SYSTEM = 'system';

    /** OpenAI 消息角色：用户输入. */
    public const ROLE_USER = 'user';

    /** OpenAI 消息角色：模型回复. */
    public const ROLE_ASSISTANT = 'assistant';

    /** OpenAI 消息角色：工具返回. */
    public const ROLE_TOOL = 'tool';

    /** 消息状态：流式完成. */
    public const MESSAGE_DONE = 1;

    /** 消息状态：用户中断. */
    public const MESSAGE_INTERRUPTED = 2;

    /** 消息状态：失败. */
    public const MESSAGE_FAILED = 3;

    /** 消息状态：待 Agent 信息确认（input_required）. */
    public const MESSAGE_AWAITING_INPUT = 4;

    /** SSE 流式单次请求最大时长（秒）. */
    public const STREAM_TIMEOUT = 300;

    /** 准备/工具阻塞阶段 SSE 心跳间隔（秒），避免前端感知为死链. */
    public const STREAM_HEARTBEAT_SECONDS = 2.0;

    /** MCP 工具列表缓存 TTL（秒），有结果时. */
    public const MCP_TOOLS_CACHE_SECONDS = 300;

    /** MCP 工具列表空结果短 TTL（秒）. */
    public const MCP_TOOLS_EMPTY_CACHE_SECONDS = 10;

    /** AI 会话相关 Cache 标签. */
    public const CACHE_TAG = 'ai_conversation';
}
