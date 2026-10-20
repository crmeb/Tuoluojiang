<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Requests\enterprise;

use App\Http\Requests\ApiValidate;
use Tuoluojiang\Open\utils\Regex;

class UserEntRequest extends ApiValidate
{
    /**
     * 提醒.
     * @var string[]
     */
    protected $message = [
        'phone.required' => '请填写手机号',
        'phone.size'     => '请填写正确的手机号',
        'phone.regex'    => '请填写正确的手机号',
    ];

    /**
     * 规则.
     * @var array
     */
    protected function rules()
    {
        return [
            'phone' => ['required', 'size:11', 'regex:' . Regex::PHONE_NUMBER],
        ];
    }
}
