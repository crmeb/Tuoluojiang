<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Requests;

use App\Constants\System\ViewSearchEnum;
use Illuminate\Validation\Rule;

/**
 * 字典数据验证器.
 */
class ViewSearchRequest extends ApiValidate
{
    /**
     * 开启自动验证
     * @var bool
     */
    public $authValidate = true;

    /**
     * 提示.
     * @var string[]
     */
    protected $message = [
        'title.required'    => '请填写视图名称',
        'title.max'         => '视图名称长度不能大于64个字符',
        'category.required' => '缺少视图类别',
        'category.in'       => '无效的视图类别',
    ];

    /**
     * 规则.
     * @return array
     */
    public function rules()
    {
        return [
            'title'    => 'required|max:64',
            'category' => ['required', Rule::in(ViewSearchEnum::values())],
        ];
    }
}
