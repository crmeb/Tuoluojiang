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

namespace App\Http\Requests\Customer;

use App\Http\Requests\ApiValidate;

/**
 * 产品请求验证
 * Class ProductRequest.
 */
class ProductRequest extends ApiValidate
{
    /**
     * 错误提醒.
     * @return string[]
     */
    public function message()
    {
        return [
            'name.required' => '请填写产品名称',
            'name.max'     => '产品名称长度超出限制',
        ];
    }

    /**
     * 规则.
     * @return array|string[]
     */
    protected function rules()
    {
        return [
            'name' => 'required|max:255',
        ];
    }
}
