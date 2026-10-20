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

return [
    /*
    |--------------------------------------------------------------------------
    | 插件编排调参（ThinDecisionBuilder / RAG / MCP / SQL）
    |--------------------------------------------------------------------------
    | MCP 白名单语义（McpToolAllowlist）：
    | - decision.mcp_allowlist 永远是具体名单；空数组 = 不发现业务工具（仍可注入 common）.
    | - 禁止把空名单翻成 toolsForApp(null) 全量 listTools.
    | - 能力别名命中优先于模块全集；模块未知时不并集倾倒全部绑定模块.
    */
    'plugins' => [
        'rag' => [
            /** 注入 system 的参考资料字符预算（按 mb_strlen 估算）. */
            'token_budget'      => (int) env('AI_RAG_TOKEN_BUDGET', 2000),
            /** 最多注入的片段条数. */
            'max_chunks'        => (int) env('AI_RAG_MAX_CHUNKS', 5),
            /** 单片段最大字符数. */
            'max_chunk_chars'   => (int) env('AI_RAG_MAX_CHUNK_CHARS', 600),
            /**
             * AI 会话额外最低相关度（0~1）；与知识库绑定阈值取 max。
             * 0 表示仅使用绑定侧阈值.
             */
            'min_score'         => (float) env('AI_RAG_MIN_SCORE', 0.55),
        ],
        'tool_result' => [
            /** 工具结果回注模型的最大字符数（只读默认更紧）. */
            'max_chars'       => (int) env('AI_TOOL_RESULT_MAX_CHARS', 1500),
            /** 写操作/续查场景可放宽的结果上限. */
            'max_chars_write' => (int) env('AI_TOOL_RESULT_MAX_CHARS_WRITE', 2500),
            /** SQL/列表类结果最多保留行数. */
            'max_rows'        => (int) env('AI_TOOL_RESULT_MAX_ROWS', 20),
            /** 多轮循环中保留最近几轮完整 tool 结果，更早的二次压缩. */
            'keep_recent_rounds' => (int) env('AI_TOOL_RESULT_KEEP_RECENT_ROUNDS', 2),
        ],
        'tools' => [
            /** 单次请求 tools schema 硬顶（含 identity）；0=不限制. */
            'max_count' => (int) env('AI_TOOLS_MAX_COUNT', 64),
            /** 工具循环中轮 focus 后再压一轮的上限. */
            'focus_max_count' => (int) env('AI_TOOLS_FOCUS_MAX_COUNT', 48),
            /** 是否向模型提供 load_tools：无业务工具时由模型按模块补挂核心短名单. */
            'load_tools_enabled' => (bool) env('AI_LOAD_TOOLS_ENABLED', true),
            /** 单次 load_tools 最多补挂数（仅 moduleCore，禁止整模块倾倒）. */
            'load_tools_max' => (int) env('AI_LOAD_TOOLS_MAX', 12),
            /** 单轮对话 load_tools 累计补挂上限（不含首轮注入）. */
            'load_tools_total_max' => (int) env('AI_LOAD_TOOLS_TOTAL_MAX', 24),
            /**
             * 是否允许从正文特殊标记回吸为 tool_calls（弱模型兼容）.
             * 默认 false：只剥离泄露标记，不把文本解析成可执行工具调用（T-2）.
             */
            'allow_text_tool_parse' => (bool) env('AI_ALLOW_TEXT_TOOL_PARSE', false),
        ],
        'tool_loop' => [
            /** 只读查询默认可工具轮次. */
            'read_max_rounds'  => (int) env('AI_TOOL_LOOP_READ_MAX_ROUNDS', 3),
            /** 写操作默认可工具轮次. */
            'write_max_rounds' => (int) env('AI_TOOL_LOOP_WRITE_MAX_ROUNDS', 5),
            /** 半截写（已取业务 form 未 create）相对基准轮次可追加的轮数. */
            'incomplete_write_extend' => (int) env('AI_TOOL_LOOP_INCOMPLETE_WRITE_EXTEND', 2),
        ],
        'sql' => [
            'max_rows' => (int) env('AI_SQL_MAX_ROWS', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG 命中图片：多模态注入模型 + 随回复附带（客服二维码等）
    |--------------------------------------------------------------------------
    | 双通道分工（入库已将文档级图建成 image_only 分片，避免错挂文本）：
    | - vision.*：仅对 prompt 内图载体分片缩放后转 base64 注入多模态模型；
    |   会话模型默认 supports_vision=true，显式 false 或非会话类型不注入；
    | - 挂图展示：仅 image_only / 短正文配图随 images 事件推前端（纯结构判定）.
    */
    'reply_images' => [
        'enabled'       => (bool) env('AI_REPLY_IMAGES_ENABLED', true),
        'max'           => (int) env('AI_REPLY_IMAGES_MAX', 6),
        'max_per_chunk' => (int) env('AI_REPLY_IMAGES_MAX_PER_CHUNK', 10),
        /**
         * 随回复挂图的最低分片相关度；应不低于 ai.plugins.rag.min_score，
         * 避免弱相关召回把产品说明截图塞进数据续问.
         */
        'min_score'     => (float) env('AI_REPLY_IMAGES_MIN_SCORE', 0.72),
        /**
         * 仅图片承载型分片随回复挂图（image_only / 正文过短配图）；
         * false 回退旧行为：所有达标分片的图片全部下发.
         */
        'only_image_carriers'   => (bool) env('AI_REPLY_IMAGES_ONLY_CARRIERS', true),
        /**
         * 分片纯文本少于该字数且有 image_urls 即视为图片承载型；
         * 亦作为内嵌图 reply_display 打标上限（不论关键词）.
         */
        'image_chunk_min_chars' => (int) env('AI_REPLY_IMAGES_CHUNK_MIN_CHARS', 50),
        'vision' => [
            /**
             * 检索图片注入模型（多模态 image_url 内容块）。
             * 会话模型默认支持 vision；不支持的会话模型可在 ai_model.json 设 supports_vision=false.
             */
            'enabled'          => (bool) env('AI_REPLY_IMAGES_VISION_ENABLED', true),
            /** 单轮最多注入图片数（仅 prompt 内图载体分片）. */
            'max_images'       => (int) env('AI_REPLY_IMAGES_VISION_MAX', 4),
            /** 注入图片的分片最低相关度；0 表示跟随已注入 prompt 的分片. */
            'min_score'        => (float) env('AI_REPLY_IMAGES_VISION_MIN_SCORE', 0),
            /** 缩放后单图最大字节数，超限跳过以免请求体过大. */
            'max_image_bytes'  => (int) env('AI_REPLY_IMAGES_VISION_MAX_BYTES', 1048576),
            /** 注入前缩放长边上限（像素）. */
            'max_edge'         => (int) env('AI_REPLY_IMAGES_VISION_MAX_EDGE', 1280),
            /**
             * 本地文件缺失时是否允许把远程 http(s) 图片地址原样交给模型抓取；
             * 默认关闭：不可达地址会导致供应商端整轮报错.
             */
            'allow_remote_url' => (bool) env('AI_REPLY_IMAGES_VISION_ALLOW_REMOTE', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI HTTP 调用日志（messages/tools 体积拆分，便于体感优化验收）
    |--------------------------------------------------------------------------
    */
    'http_log' => [
        'enabled'  => (bool) env('AI_HTTP_LOG_ENABLED', true),
        'channel'  => (string) env('AI_HTTP_LOG_CHANNEL', 'ai'),
        'max_body' => (int) env('AI_HTTP_LOG_MAX_BODY', 20000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider failover（T-4）
    |--------------------------------------------------------------------------
    | 主模型 429/5xx/超时且尚未产生有效正文时，按 model_ids 顺序切换并退避重试.
    | model_ids 为空时仅重试同一模型（受 max_attempts 限制）.
    */
    'failover' => [
        'enabled'               => (bool) env('AI_FAILOVER_ENABLED', true),
        'model_ids'             => array_values(array_filter(array_map(
            'intval',
            explode(',', (string) env('AI_FAILOVER_MODEL_IDS', ''))
        ))),
        'max_attempts'          => (int) env('AI_FAILOVER_MAX_ATTEMPTS', 2),
        'backoff_base_seconds'  => (float) env('AI_FAILOVER_BACKOFF_BASE', 0.5),
        'backoff_max_seconds'   => (float) env('AI_FAILOVER_BACKOFF_MAX', 4.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | 准确度硬闸（A-P0 / T-3 EvidenceGate）
    |--------------------------------------------------------------------------
    */
    'accuracy' => [
        'require_evidence' => (bool) env('AI_ACCURACY_REQUIRE_EVIDENCE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 流式正文暂扣策略（工具轮次）
    |--------------------------------------------------------------------------
    | 工具轮次正文默认暂扣，避免旁白与工具状态并行刷屏；
    | 缓冲达到阈值即放闸改为实时转发，若随后出现 tool_calls 再撤回已直播内容.
    */
    'streaming' => [
        /** 暂扣缓冲放闸阈值（字符）：达到即解除暂扣实时转发；0 表示首片立即放闸. */
        'hold_release_chars' => (int) env('AI_STREAM_HOLD_RELEASE_CHARS', 80),
    ],

    /*
    |--------------------------------------------------------------------------
    | 会话上下文体积控制（历史 / 记忆注入）
    |--------------------------------------------------------------------------
    | 降低单轮请求体积，缩短首包与总耗时；按 mb_strlen 估算字符。
    */
    'context' => [
        /** 历史 user 消息单条最大字符；0 表示不截断. */
        'history_user_max_chars'      => (int) env('AI_HISTORY_USER_MAX_CHARS', 500),
        /** 历史 assistant 消息单条最大字符；0 表示不截断. */
        'history_assistant_max_chars' => (int) env('AI_HISTORY_ASSISTANT_MAX_CHARS', 800),
        /**
         * 写流程（审批/创建等）最近若干条历史消息使用更宽字符上限，降低槽位丢失.
         * 0 表示写流程不分层，仍用 history_*_max_chars.
         */
        'write_keep_recent_messages'           => (int) env('AI_WRITE_KEEP_RECENT_MESSAGES', 6),
        'write_history_user_max_chars'         => (int) env('AI_WRITE_HISTORY_USER_MAX_CHARS', 1200),
        'write_history_assistant_max_chars'    => (int) env('AI_WRITE_HISTORY_ASSISTANT_MAX_CHARS', 1600),
        /** 记忆 prompt 总预算（会话摘要 + 长期记忆）. */
        'memory_prompt_budget'        => (int) env('AI_MEMORY_PROMPT_BUDGET', 1200),
        /** 单条长期记忆注入时最大字符. */
        'memory_item_max_chars'       => (int) env('AI_MEMORY_ITEM_MAX_CHARS', 160),
        /** 会话摘要注入时最大字符. */
        'session_summary_max_chars'   => (int) env('AI_SESSION_SUMMARY_MAX_CHARS', 400),
        /** 相关长期记忆最多注入条数（偏好始终优先）. */
        'memory_inject_limit'         => (int) env('AI_MEMORY_INJECT_LIMIT', 5),
        /**
         * 流式对话已带 history 时是否仍注入会话摘要.
         * 默认 false，避免与最近轮次原文重复占预算.
         */
        'inject_session_summary'      => (bool) env('AI_INJECT_SESSION_SUMMARY', false),
        /**
         * inject_org_tools=true 时 AI 会话常驻的精简组织工具（不挂全量 context_tools）.
         * thin-react 默认 inject_org_tools=false；组织树/数据范围优先 who_am_i 预取与 MCP resources/read.
         */
        'org_tools_always_on' => [
            'personnel_search',
            'org_department_users',
        ],
    ],

    // 外部 AI 访客会话：鉴权 Header、限流、指纹与 Widget 默认配置（见 AiGuestConfig）.
    'guest' => [
        'header'            => 'X-Ai-Guest-Token',
        /** 前端设备指纹 Header（SHA-256 hex，由 UA+屏幕+时区+语言+shareKey 生成）. */
        'fingerprint_header' => 'X-Ai-Guest-Fp',
        'rate_limit' => [
            /** 单访客+IP+指纹 每分钟流式对话次数. */
            'stream_per_minute'    => (int) env('AI_GUEST_STREAM_LIMIT', 15),
            /** 单 IP 每分钟流式对话次数（防换 token 刷量）. */
            'stream_ip_per_minute' => (int) env('AI_GUEST_IP_STREAM_LIMIT', 30),
            /** 单 IP 每日流式对话次数. */
            'stream_ip_daily'      => (int) env('AI_GUEST_IP_DAILY_LIMIT', 200),
            /** 单访客每分钟创建会话次数. */
            'create_per_minute'    => (int) env('AI_GUEST_CREATE_LIMIT', 5),
            /** 单指纹每分钟流式对话次数. */
            'stream_fp_per_minute' => (int) env('AI_GUEST_FP_STREAM_LIMIT', 20),
            /** 单 IP 每分钟拉取应用公开信息次数. */
            'app_info_per_minute'  => (int) env('AI_GUEST_APP_INFO_LIMIT', 60),
        ],
        /** 外部应用未配置 guest_use_limit 时的当日访客消息总上限（全应用共享）. */
        'default_daily_messages' => (int) env('AI_GUEST_DAILY_MESSAGES', 30),
        'risk' => [
            /** 同 IP 1 小时内出现不同 guest_token 数量阈值. */
            'ip_token_threshold'   => (int) env('AI_GUEST_IP_TOKEN_THRESHOLD', 5),
            'ip_token_window_sec'  => (int) env('AI_GUEST_IP_TOKEN_WINDOW', 3600),
            /** 同 IP 1 小时内不同指纹数量阈值. */
            'ip_fp_threshold'      => (int) env('AI_GUEST_IP_FP_THRESHOLD', 8),
            /** 重复消息检测窗口（秒）. */
            'duplicate_window_sec' => (int) env('AI_GUEST_DUP_MSG_WINDOW', 120),
            /** 风险分 >= 该值直接拒绝. */
            'reject_threshold'     => (int) env('AI_GUEST_RISK_REJECT', 70),
            /** 风险分 >= 该值自动封禁 IP/token/指纹. */
            'ban_threshold'        => (int) env('AI_GUEST_RISK_BAN', 85),
        ],
        'ban' => [
            'ttl_sec'              => (int) env('AI_GUEST_BAN_TTL', 3600),
            'violation_threshold'  => (int) env('AI_GUEST_VIOLATION_THRESHOLD', 3),
            'violation_window_sec' => (int) env('AI_GUEST_VIOLATION_WINDOW', 3600),
        ],
        'content' => [
            'max_message_length' => (int) env('AI_GUEST_MAX_MESSAGE_LENGTH', 4000),
            'replacement'        => '***',
            /** 系统级敏感词，与应用 guest_json.sensitive_words 合并. */
            'sensitive_words'    => [],
            /** Prompt 注入检测正则（访客专用）. */
            'injection_phrases' => [
                'ignore previous instructions',
                'ignore all previous instructions',
                'ignore above instructions',
                'disregard prior instructions',
                'disregard previous instructions',
                'disregard previous prompts',
                'system: you are',
                'system：you are',
                '<|im_start|>',
                '<|system|>',
                '[INST]',
            ],
        ],
        'defaults' => [
            'allowed_origins'    => [],
            'widget_position'    => 'bottom-right',
            'widget_color'       => '#3370ff',
            'widget_title'       => 'AI 助手',
            'max_message_length' => (int) env('AI_GUEST_MAX_MESSAGE_LENGTH', 4000),
            'sensitive_words'    => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent（thin-react / Skills / 写表单抽槽）
    |--------------------------------------------------------------------------
    | thin-react：渐进 load_tools + 按需 search_knowledge；golden 仅回归说明。
    | skills：扫描 resources/agent/skills 下 SKILL.md；仅显式注入 format（如 gpt-vis）。
    | schema_slot_filler：写确认前从话术提炼标量槽位；超时/失败不阻断确认卡。
    */
    'agent' => [
        'thin_react' => [
            'golden_path' => env('AI_AGENT_THIN_REACT_GOLDEN_PATH')
                ?: resource_path('agent/eval/thin-react-golden.json'),
        ],
        'skills' => [
            'enabled'   => (bool) env('AI_AGENT_SKILLS_ENABLED', true),
            'path'      => env('AI_AGENT_SKILLS_PATH') ?: resource_path('agent/skills'),
            'max_chars' => (int) env('AI_AGENT_SKILLS_MAX_CHARS', 4000),
        ],
        'schema_slot_filler' => [
            'timeout_ms' => (int) env('AI_SCHEMA_SLOT_TIMEOUT_MS', 3000),
            'max_tokens' => (int) env('AI_SCHEMA_SLOT_MAX_TOKENS', 400),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 面向用户回复语言（全会话注入 system）
    |--------------------------------------------------------------------------
    | 不同模型默认输出语言不一致；此处统一注入语言约束。
    | 空字符串或 off/none/0/false 表示不注入；其它值（含 zh-CN）按简体中文约束处理。
    */
    'response' => [
        'default_language' => (string) env('AI_RESPONSE_DEFAULT_LANGUAGE', 'zh-CN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI 流式对话
    |--------------------------------------------------------------------------
    | auto_disable_thinking=true 时：
    | - MCP / SQL / thin_react / tool_first：强制关 thinking（未回传 reasoning_content
    |   时 GLM 多轮会重复深思，禁止依赖未实现的推理链回传）；
    | - 纯闲聊同样关闭，避免豆包 Seed 在简单问题上刷 reasoning_tokens；
    | - 仅「有 RAG 且无工具」等非 Agent 场景沿用应用/模型开关。
    */
    'stream' => [
        'auto_disable_thinking' => (bool) env('AI_STREAM_AUTO_DISABLE_THINKING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 会话结束后的推荐问题
    |--------------------------------------------------------------------------
    | 一轮回答正常结束后，结合本轮问答与应用配置再请求一次模型，
    | 用 SSE type=suggestions 推送恰好 3 条可点击续问。失败或关闭时不推送。
    | 该请求在 [DONE] 前同步等待。各型号最轻思考参数见 AiModelThinkingEnum，不在 Service 内分支。
    */
    'follow_up' => [
        'enabled'            => (bool) env('AI_FOLLOW_UP_ENABLED', true),
        'timeout_ms'         => (int) env('AI_FOLLOW_UP_TIMEOUT_MS', 20000),
        'max_question_chars' => (int) env('AI_FOLLOW_UP_MAX_QUESTION_CHARS', 800),
        'max_answer_chars'   => (int) env('AI_FOLLOW_UP_MAX_ANSWER_CHARS', 1600),
        'max_item_chars'     => (int) env('AI_FOLLOW_UP_MAX_ITEM_CHARS', 40),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI 应用提示内容生成（generate-prompt，SSE 流式）
    |--------------------------------------------------------------------------
    | 豆包 seed 默认开启 thinking，会额外产生大量 reasoning_tokens；
    | 表单生成场景默认关闭 thinking，并按类型限制 max_tokens。
    | 流式超时与会话共用 AiConversationEnum::STREAM_TIMEOUT；timeout 仅作文档/兼容保留。
    */
    'generate_prompt' => [
        'timeout'           => (int) env('AI_GENERATE_PROMPT_TIMEOUT', 180),
        'disable_thinking'  => (bool) env('AI_GENERATE_PROMPT_DISABLE_THINKING', true),
        'max_tokens'        => [
            'persona_reply'      => (int) env('AI_GENERATE_PROMPT_MAX_TOKENS_PERSONA', 900),
            'prologue'           => (int) env('AI_GENERATE_PROMPT_MAX_TOKENS_PROLOGUE', 160),
            'prologue_questions' => (int) env('AI_GENERATE_PROMPT_MAX_TOKENS_QUESTIONS', 280),
        ],
    ],
];
