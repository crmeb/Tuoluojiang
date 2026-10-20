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
 * 知识库文档验证.
 *
 * 场景：batch_enabled 批量启停、batch_delete 批量删除.
 */
class KnowledgeDocumentRequest extends ApiValidate
{
    /**
     * 验证场景.
     */
    protected $scene = [
        'batch_enabled' => ['ids', 'enabled'],
        'batch_delete'  => ['ids'],
    ];

    /**
     * 验证提示.
     */
    public function message(): array
    {
        return [
            'ids.required'     => '请选择文档',
            'ids.array'        => '文档ID格式错误',
            'ids.min'          => '请选择文档',
            'ids.*.integer'    => '文档ID格式错误',
            'ids.*.min'        => '文档ID格式错误',
            'enabled.required' => '请选择启停状态',
            'enabled.in'       => '启停状态不正确',
        ];
    }

    /**
     * 验证规则.
     */
    protected function rules(): array
    {
        return [
            'ids'      => ['required', 'array', 'min:1'],
            'ids.*'    => ['integer', 'min:1'],
            'enabled'  => ['required', 'integer', 'in:0,1'],
        ];
    }
}
