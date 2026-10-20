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
 * AI 模型供应商预设配置.
 *
 * 各 driver 的 base_url、默认模型列表、鉴权方式与控制台 Key 地址；与前端供应商选择页顺序一致.
 */
final class AiModelPresetEnum
{
    /**
     * 预设模型供应商(顺序与系统供应商选择页一致).
     */
    public const PRESETS = [
        AiModelEnum::DRIVER_YIHAOTONG => [
            'label'            => '一号通',
            'api_key_url'      => 'https://api.crmeb.com',
            'base_url'         => AiModelEnum::YIHAOTONG_BASE_URL,
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => [],
            'embedding_models' => [],
            'yihaotong'        => true,
        ],
        AiModelEnum::DRIVER_DEEPSEEK => [
            'label'            => 'DeepSeek',
            'api_key_url'      => 'https://platform.deepseek.com/api_keys',
            'base_url'         => 'https://api.deepseek.com',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['deepseek-chat', 'deepseek-reasoner'],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_MINIMAX => [
            'label'            => 'MiniMax CN',
            'api_key_url'      => 'https://platform.minimaxi.com/user-center/basic-information/interface-key',
            'base_url'         => 'https://api.minimaxi.com/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['MiniMax-M2.5', 'MiniMax-Text-01'],
            'embedding_models' => ['embo-01'],
        ],
        AiModelEnum::DRIVER_BIGMODEL => [
            'label'            => 'Bigmodel',
            'api_key_url'      => 'https://open.bigmodel.cn/usercenter/apikeys',
            'base_url'         => 'https://open.bigmodel.cn/api/paas/v4',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['glm-4.5', 'glm-4-flash', 'glm-4-plus'],
            'embedding_models' => ['embedding-3'],
        ],
        AiModelEnum::DRIVER_MIMO => [
            'label'            => 'Xiaomi MIMO',
            'api_key_url'      => 'https://platform.xiaomimimo.com/console/api-keys',
            'base_url'         => 'https://api.xiaomimimo.com/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_API_KEY,
            'chat_models'      => ['mimo-v2.5-pro', 'mimo-v2.5', 'mimo-v2-flash'],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_ZAI => [
            'label'            => 'Z.ai',
            'api_key_url'      => 'https://z.ai/manage-apikey/apikey-list',
            'base_url'         => 'https://api.z.ai/api/paas/v4',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['glm-4.5', 'glm-4-flash'],
            'embedding_models' => ['embedding-3'],
        ],
        AiModelEnum::DRIVER_KIMI => [
            'label'            => 'Kimi CN',
            'api_key_url'      => 'https://platform.moonshot.cn/console/api-keys',
            'base_url'         => 'https://api.moonshot.cn/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['kimi-k2.5', 'moonshot-v1-128k', 'moonshot-v1-32k', 'moonshot-v1-8k'],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_BYTEPLUS => [
            'label'            => 'BytePlus',
            'api_key_url'      => 'https://console.byteplus.com/ark/region:ark+ap-southeast-1/apiKey',
            'base_url'         => 'https://ark.ap-southeast.bytepluses.com/api/v3',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => [],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_TENCENT => [
            'label'            => '腾讯云',
            'api_key_url'      => 'https://console.cloud.tencent.com/lkeap/api',
            'base_url'         => 'https://api.lkeap.cloud.tencent.com/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['deepseek-v3.2', 'deepseek-v3-0324', 'deepseek-r1-0528'],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_PPIO => [
            'label'            => 'PPIO',
            'api_key_url'      => 'https://ppio.com/settings/key-management',
            'base_url'         => 'https://api.ppinfra.com/v3/openai',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => [],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_VOLCENGINE => [
            'label'            => '火山引擎',
            'api_key_url'      => 'https://console.volcengine.com/ark/region:ark+cn-beijing/apiKey',
            'base_url'         => 'https://ark.cn-beijing.volces.com/api/v3',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => [],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_MINIMAX_GLOBAL => [
            'label'            => 'MiniMax Global',
            'api_key_url'      => 'https://www.minimax.io/platform/user-center/basic-information/interface-key',
            'base_url'         => 'https://api.minimax.io/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['MiniMax-M2.5', 'MiniMax-Text-01'],
            'embedding_models' => ['embo-01'],
        ],
        AiModelEnum::DRIVER_ALIYUN => [
            'label'            => '阿里云',
            'api_key_url'      => 'https://bailian.console.aliyun.com/?apiKey=1#/api-key',
            'base_url'         => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['qwen-plus', 'qwen-turbo', 'qwen-max', 'qwen-long'],
            'embedding_models' => ['text-embedding-v3', 'text-embedding-v2'],
        ],
        AiModelEnum::DRIVER_SILICONFLOW => [
            'label'            => '硅基流动',
            'api_key_url'      => 'https://cloud.siliconflow.cn/account/ak',
            'base_url'         => 'https://api.siliconflow.cn/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['deepseek-ai/DeepSeek-V3', 'Qwen/Qwen2.5-72B-Instruct'],
            'embedding_models' => ['BAAI/bge-m3', 'BAAI/bge-large-zh-v1.5'],
        ],
        AiModelEnum::DRIVER_OPENROUTER => [
            'label'            => 'OpenRouter',
            'api_key_url'      => 'https://openrouter.ai/keys',
            'base_url'         => 'https://openrouter.ai/api/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => [],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_KIMI_GLOBAL => [
            'label'            => 'Kimi Global',
            'api_key_url'      => 'https://platform.moonshot.ai/console/api-keys',
            'base_url'         => 'https://api.moonshot.ai/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['kimi-k2.5', 'moonshot-v1-128k', 'moonshot-v1-32k', 'moonshot-v1-8k'],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_AWS => [
            'label'            => 'AWS',
            'api_key_url'      => 'https://console.aws.amazon.com/bedrock/home?region=us-east-1#/api-keys',
            'base_url'         => 'https://bedrock-runtime.us-east-1.amazonaws.com/openai/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['amazon.nova-pro-v1:0', 'anthropic.claude-sonnet-4-20250514-v1:0'],
            'embedding_models' => ['amazon.titan-embed-text-v2:0'],
        ],
        AiModelEnum::DRIVER_MOARK => [
            'label'            => '模力方舟',
            'api_key_url'      => 'https://moark.com/dashboard/settings/tokens',
            'base_url'         => 'https://api.moark.com/v1',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => ['Qwen2.5-72B-Instruct', 'DeepSeek-R1'],
            'embedding_models' => [],
        ],
        AiModelEnum::DRIVER_QIANFAN => [
            'label'            => '百度千帆',
            'api_key_url'      => 'https://console.bce.baidu.com/iam/#/iam/apikey/list',
            'base_url'         => 'https://qianfan.baidubce.com/v2',
            'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
            'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
            'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
            'auth'             => AiModelEnum::AUTH_BEARER,
            'chat_models'      => [
                'ernie-4.0-8k-latest',
                'ernie-4.0-turbo-8k',
                'ernie-3.5-8k',
                'deepseek-v3',
                'deepseek-r1',
            ],
            'embedding_models' => [
                'tao-8k',
            ],
        ],
    ];

    /**
     * 获取全部供应商配置.
     */
    public static function all(): array
    {
        return self::PRESETS + [
            AiModelEnum::DRIVER_OPENAI_COMPATIBLE => [
                'label'            => '自定义配置',
                'api_key_url'      => '',
                'base_url'         => '',
                'chat_path'        => AiModelEnum::DEFAULT_CHAT_PATH,
                'embedding_path'   => AiModelEnum::DEFAULT_EMBEDDING_PATH,
                'models_path'      => AiModelEnum::DEFAULT_MODELS_PATH,
                'auth'             => AiModelEnum::AUTH_BEARER,
                'chat_models'      => [],
                'embedding_models' => [],
                'custom'           => true,
            ],
        ];
    }

    /**
     * 获取指定供应商配置.
     */
    public static function get(string $driver): array
    {
        return self::all()[$driver] ?? [];
    }

    /**
     * 获取供应商驱动列表.
     */
    public static function drivers(): array
    {
        return array_keys(self::all());
    }
}
