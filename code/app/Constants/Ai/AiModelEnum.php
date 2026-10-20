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
 * AI 模型配置枚举.
 *
 * 定义模型驱动标识、配置类型、鉴权方式、默认 API 路径与启用状态，供 AiModelService 使用.
 */
final class AiModelEnum
{
    /**
     * 预设配置.
     */
    public const CONFIG_PRESET = 1;

    /**
     * 自定义配置.
     */
    public const CONFIG_CUSTOM = 2;

    /**
     * 一号通配置.
     */
    public const CONFIG_YIHAOTONG = 3;

    /**
     * 一号通 OpenAI 兼容接口地址.
     */
    public const YIHAOTONG_BASE_URL = 'https://api.crmeb.net/v1';

    /**
     * 一号通 API Key 缓存键.
     */
    public const YIHAOTONG_API_KEY_CACHE = 'ai:yihaotong:api_key';

    /**
     * 一号通 API Key 缓存时长（秒）.
     */
    public const YIHAOTONG_API_KEY_TTL = 180;

    /**
     * DeepSeek 驱动.
     */
    public const DRIVER_DEEPSEEK = 'deepseek';

    /**
     * MiniMax 国内站.
     */
    public const DRIVER_MINIMAX = 'minimax';

    /**
     * 智谱 Bigmodel.
     */
    public const DRIVER_BIGMODEL = 'bigmodel';

    /**
     * 小米 MIMO.
     */
    public const DRIVER_MIMO = 'mimo';

    /**
     * Z.ai.
     */
    public const DRIVER_ZAI = 'zai';

    /**
     * Kimi 国内站.
     */
    public const DRIVER_KIMI = 'kimi';

    /**
     * BytePlus.
     */
    public const DRIVER_BYTEPLUS = 'byteplus';

    /**
     * 腾讯云.
     */
    public const DRIVER_TENCENT = 'tencent';

    /**
     * PPIO.
     */
    public const DRIVER_PPIO = 'ppio';

    /**
     * 火山引擎.
     */
    public const DRIVER_VOLCENGINE = 'volcengine';

    /**
     * MiniMax 国际站.
     */
    public const DRIVER_MINIMAX_GLOBAL = 'minimax_global';

    /**
     * 阿里云.
     */
    public const DRIVER_ALIYUN = 'aliyun';

    /**
     * 硅基流动.
     */
    public const DRIVER_SILICONFLOW = 'siliconflow';

    /**
     * OpenRouter.
     */
    public const DRIVER_OPENROUTER = 'openrouter';

    /**
     * Kimi 国际站.
     */
    public const DRIVER_KIMI_GLOBAL = 'kimi_global';

    /**
     * AWS Bedrock.
     */
    public const DRIVER_AWS = 'aws';

    /**
     * 模力方舟.
     */
    public const DRIVER_MOARK = 'moark';

    /**
     * 百度千帆驱动(兼容旧配置).
     */
    public const DRIVER_QIANFAN = 'qianfan';

    /**
     * OpenAI 兼容驱动.
     */
    public const DRIVER_OPENAI_COMPATIBLE = 'openai_compatible';

    /**
     * 一号通驱动.
     */
    public const DRIVER_YIHAOTONG = 'yihaotong';

    /**
     * Bearer 鉴权.
     */
    public const AUTH_BEARER = 'bearer';

    /**
     * api-key 请求头鉴权.
     */
    public const AUTH_API_KEY = 'api_key';

    /**
     * 禁用状态.
     */
    public const STATUS_DISABLED = 0;

    /**
     * 启用状态.
     */
    public const STATUS_ENABLED = 1;

    /**
     * 会话模型（chat_models.model_type）.
     */
    public const MODEL_TYPE_CHAT = 1;

    /**
     * 向量模型（chat_models.model_type）.
     */
    public const MODEL_TYPE_EMBEDDING = 2;

    /**
     * 默认聊天接口路径.
     */
    public const DEFAULT_CHAT_PATH = '/chat/completions';

    /**
     * 默认向量接口路径.
     */
    public const DEFAULT_EMBEDDING_PATH = '/embeddings';

    /**
     * 默认模型列表接口路径.
     */
    public const DEFAULT_MODELS_PATH = '/models';

    /**
     * 非完整 URL：请求时拼接 chat_path / embedding_path.
     */
    public const FULL_URL_OFF = 0;

    /**
     * 完整 URL：base_url 即为完整接口地址，不再拼接 path.
     */
    public const FULL_URL_ON = 1;

    /**
     * 关闭深度思考.
     */
    public const THINKING_OFF = 0;

    /**
     * 启用深度思考.
     */
    public const THINKING_ON = 1;

    /**
     * 跟随模型/供应商默认思考配置（不强制开启或关闭）.
     */
    public const THINKING_DEFAULT = 2;

    /**
     * 高级配置：默认输入上下文窗口（Token）.
     */
    public const DEFAULT_INPUT_TOKENS = 1048576;

    /**
     * 高级配置：默认工具调用轮数上限.
     */
    public const DEFAULT_MAX_TOOL_ROUNDS = 500;

    /**
     * 高级配置 json 字段名：输入上下文窗口.
     */
    public const ADVANCED_INPUT_TOKENS = 'input_tokens';

    /**
     * 高级配置 json 字段名：输出 Token 上限（对应 max_tokens）.
     */
    public const ADVANCED_OUTPUT_TOKENS = 'output_tokens';

    /**
     * 高级配置 json 字段名：工具调用轮数.
     */
    public const ADVANCED_MAX_TOOL_ROUNDS = 'max_tool_rounds';

    /**
     * 高级配置 json 字段名：采样温度.
     */
    public const ADVANCED_TEMPERATURE = 'temperature';

    /**
     * 高级配置 json 字段名：Top P.
     */
    public const ADVANCED_TOP_P = 'top_p';

    /**
     * 高级配置 json 字段名：Top K.
     */
    public const ADVANCED_TOP_K = 'top_k';

    /**
     * 高级配置 json 字段名：是否支持图片输入（vision）.
     * 会话模型缺省开启；显式 false 可关闭；向量模型缺省关闭.
     */
    public const ADVANCED_SUPPORTS_VISION = 'supports_vision';
}
