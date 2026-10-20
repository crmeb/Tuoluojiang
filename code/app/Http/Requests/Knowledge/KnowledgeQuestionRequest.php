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

namespace App\Http\Requests\Knowledge;

use App\Http\Requests\ApiValidate;

/**
 * 知识库问答验证.
 */
class KnowledgeQuestionRequest extends ApiValidate
{
    /**
     * 验证场景.
     */
    protected $scene = [
        'ask' => ['question', 'kb_ids', 'top_k'],
    ];

    /**
     * 验证提示.
     */
    public function message(): array
    {
        return [
            'question.required' => '请填写问题',
            'question.max'      => '问题长度超出限制',
            'kb_ids.array'      => '知识库范围格式错误',
            'top_k.integer'     => '检索数量格式错误',
        ];
    }

    /**
     * 验证规则.
     */
    protected function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
            'kb_ids'   => ['nullable', 'array'],
            'top_k'    => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
