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

use App\Constants\KnowledgeEnum;
use App\Http\Requests\ApiValidate;
use Illuminate\Validation\Rule;

/**
 * 知识库验证.
 */
class KnowledgeBaseRequest extends ApiValidate
{
    /**
     * 验证场景.
     */
    protected $scene = [
        'store'  => ['name', 'space_type', 'visibility', 'embedding_model_id', 'chunk_mode', 'chunk_size', 'chunk_overlap', 'status', 'sort', 'icon', 'color'],
        'update' => ['name', 'space_type', 'visibility', 'embedding_model_id', 'chunk_mode', 'chunk_size', 'chunk_overlap', 'status', 'sort', 'icon', 'color'],
    ];

    /**
     * 验证提示.
     */
    public function message(): array
    {
        return [
            'name.required'     => '请填写知识库名称',
            'name.max'          => '知识库名称长度超出限制',
            'icon.max'          => '知识库图标地址长度超出限制',
            'color.max'         => '知识库图标颜色长度超出限制',
            'space_type.in'     => '知识库空间类型错误',
            'visibility.array'  => '可见范围格式错误',
            'chunk_mode.in'     => '分块模式错误',
            'chunk_size.min'    => '分块长度不能小于100',
            'chunk_overlap.min' => '分块重叠长度不能小于0',
            'status.in'         => '知识库状态错误',
            'sort.integer'      => '排序格式错误',
        ];
    }

    /**
     * 验证规则.
     */
    protected function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:500'],
            'icon'               => ['nullable', 'string', 'max:500'],
            'color'              => ['nullable', 'string', 'max:32'],
            'space_type'         => ['nullable', Rule::in([KnowledgeEnum::SPACE_INTERNAL, KnowledgeEnum::SPACE_EXTERNAL])],
            'visibility'         => ['nullable', 'array'],
            'embedding_model_id' => ['nullable', 'integer', 'min:0'],
            'chunk_mode'         => ['nullable', Rule::in([KnowledgeEnum::CHUNK_AUTO, KnowledgeEnum::CHUNK_MANUAL])],
            'chunk_size'         => ['nullable', 'integer', 'min:100', 'max:5000'],
            'chunk_overlap'      => ['nullable', 'integer', 'min:0', 'max:1000'],
            'status'             => ['nullable', Rule::in([KnowledgeEnum::STATUS_DISABLED, KnowledgeEnum::STATUS_ENABLED])],
            'sort'               => ['nullable', 'integer', 'min:0'],
        ];
    }
}
