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

namespace App\Http\Requests\Ai;

use App\Http\Requests\ApiValidate;

/**
 * AI 会话表单校验.
 *
 * 场景 store：创建会话，校验 app_id 必填.
 * 场景 update：更新会话，校验 title、status、top.
 * 场景 stream：流式对话，校验 message 必填且不超过 20000 字符.
 * 字段：app_id 应用 ID、title 会话标题、status 显示状态（0/1）、top 置顶（0/1）、message 对话内容.
 */
class AiConversationRequest extends ApiValidate
{
    protected $scene = [
        'store'  => ['app_id'],
        'update' => ['title', 'status', 'top'],
        'stream' => ['message'],
    ];

    public function message(): array
    {
        return [
            'app_id.required'  => '请选择AI应用',
            'message.required' => '请输入对话内容',
        ];
    }

    protected function rules(): array
    {
        return [
            'app_id'  => ['required', 'integer', 'min:1'],
            'title'   => ['nullable', 'string', 'max:255'],
            'status'  => ['nullable', 'integer', 'in:0,1'],
            'top'     => ['nullable', 'integer', 'in:0,1'],
            'message' => ['required', 'string', 'max:20000'],
        ];
    }
}
