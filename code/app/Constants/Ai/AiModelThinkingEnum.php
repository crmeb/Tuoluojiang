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
 * 模型思考能力目录：短补全（如推荐问题）应使用的最轻思考参数.
 *
 * 型号差异只维护在本目录，Service 按前缀取结果，不写具体模型分支。
 * 更长的模型 ID 前缀优先，例如 glm-5-3 先于 glm-5。
 */
final class AiModelThinkingEnum
{
    /**
     * 供应商本身支持 thinking.type，且未单独登记型号时默认关闭思考.
     *
     * @var list<string>
     */
    private const PROVIDERS_DISABLE_THINKING = [
        AiModelEnum::DRIVER_VOLCENGINE,
    ];

    /**
     * 模型 ID 前缀 => 合并进请求的附加参数.
     *
     * 匹配前会把模型 ID 中的 `.`、`_` 归一成 `-`，因此 glm-5.3 与 glm-5-3 共用一条。
     * GLM-5.3 禁止 thinking.type=disabled，只能把 reasoning_effort 降到 low。
     *
     * @var array<string, array<string, mixed>>
     */
    private const MODEL_LIGHT_EXTRAS = [
        'glm-5-3' => [
            'max_tokens'       => 512,
            'thinking'         => ['type' => 'enabled'],
            'reasoning_effort' => 'low',
        ],
        'glm-5' => [
            'thinking' => ['type' => 'disabled'],
        ],
        'glm-4-7' => [
            'thinking' => ['type' => 'disabled'],
        ],
        'glm-4-6' => [
            'thinking' => ['type' => 'disabled'],
        ],
        'glm-4-5' => [
            'thinking' => ['type' => 'disabled'],
        ],
        'doubao' => [
            'thinking' => ['type' => 'disabled'],
        ],
        'seed' => [
            'thinking' => ['type' => 'disabled'],
        ],
    ];

    /**
     * 推荐问题等短补全的最轻思考参数；未登记的型号返回空数组.
     *
     * @return array<string, mixed>
     */
    public static function followUpExtras(string $provider, string $modelId): array
    {
        $modelId  = str_replace(['_', '.'], '-', strtolower(trim($modelId)));
        $provider = strtolower(trim($provider));
        $matched  = null;
        $length   = -1;
        foreach (self::MODEL_LIGHT_EXTRAS as $prefix => $extras) {
            if ($modelId !== '' && str_starts_with($modelId, $prefix) && strlen($prefix) > $length) {
                $matched = $extras;
                $length  = strlen($prefix);
            }
        }
        if ($matched !== null) {
            return $matched;
        }
        if (in_array($provider, self::PROVIDERS_DISABLE_THINKING, true)) {
            return ['thinking' => ['type' => 'disabled']];
        }

        return [];
    }
}
