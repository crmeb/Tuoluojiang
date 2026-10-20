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
 * 知识库分块验证.
 *
 * 场景：store/update 仅接收 Markdown（content_html），纯文本 content 由服务端解析生成；enabled 启停.
 */
class KnowledgeChunkRequest extends ApiValidate
{
    /**
     * 验证场景.
     */
    protected $scene = [
        'store'   => ['doc_id', 'content_html', 'image_urls'],
        'update'  => ['content_html', 'image_urls'],
        'enabled' => ['enabled'],
    ];

    /**
     * 验证提示.
     */
    public function message(): array
    {
        return [
            'doc_id.required'       => '请选择所属文档',
            'doc_id.integer'        => '文档ID格式错误',
            'doc_id.min'            => '文档ID格式错误',
            'content_html.required' => '请填写分块 Markdown 内容',
            'content_html.max'      => '分块 Markdown 长度超出限制',
            'image_urls.array'      => '分块图片链接格式错误',
            'image_urls.max'        => '分块图片链接数量超出限制',
            'image_urls.*.string'   => '分块图片链接格式错误',
            'image_urls.*.regex'    => '分块图片链接必须是图片地址',
            'enabled.required'      => '请选择启停状态',
            'enabled.in'            => '启停状态不正确',
        ];
    }

    /**
     * 验证规则.
     */
    protected function rules(): array
    {
        return [
            'doc_id'         => ['required', 'integer', 'min:1'],
            'content_html'   => ['required', 'string', 'max:50000'],
            'image_urls'     => ['nullable', 'array', 'max:10'],
            'image_urls.*'   => ['string', 'max:2000', 'regex:/\.(jpe?g|png|gif|webp|bmp|tiff?)(\?.*)?$/i'],
            'enabled'        => ['required', 'integer', 'in:0,1'],
        ];
    }
}
