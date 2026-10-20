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
 * AI 应用提示内容生成类型枚举.
 *
 * 供 generate-prompt 接口按 type 区分生成目标（人设与回复、开场白、开场白问题等）.
 */
final class AiApplicationPromptEnum
{
    /** 生成类型：人设与回复提示词. */
    public const TYPE_PERSONA_REPLY = 'persona_reply';

    /** 生成类型：开场白文案. */
    public const TYPE_PROLOGUE = 'prologue';

    /** 生成类型：开场白引导问题. */
    public const TYPE_PROLOGUE_QUESTIONS = 'prologue_questions';

    /** 全部可生成类型. */
    public const TYPES = [
        self::TYPE_PERSONA_REPLY,
        self::TYPE_PROLOGUE,
        self::TYPE_PROLOGUE_QUESTIONS,
    ];

    /**
     * 返回类型对应的中文标签.
     *
     * @param string $type 生成类型
     */
    public static function label(string $type): string
    {
        return match ($type) {
            self::TYPE_PERSONA_REPLY      => '人设与回复提示词',
            self::TYPE_PROLOGUE           => '开场白',
            self::TYPE_PROLOGUE_QUESTIONS => '开场白问题',
            default                       => '提示内容',
        };
    }
}
