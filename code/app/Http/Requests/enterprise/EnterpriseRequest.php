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

/**
 * 企业验证器
 * Class EnterpriseRequest.
 */
class EnterpriseRequest extends ApiValidate
{
    /**
     * 提醒.
     * @var string[]
     */
    protected $message = [
        'enterprise_name.required'           => '请填写公司名称',
        'lead.required'                      => '请填写公司法人代表姓名',
        'address.required'                   => '请填写公司地址',
        'phone.required'                     => '请填写手机号',
        'phone.size'                         => '手机号长度不正确',
        'phone.regex'                        => '请填写正确的手机号',
        'verification_code.regex'            => '请填写验证码',
        'verification_code.integer'          => '验证码必须为数字',
        'verification_code.size'             => '验证码长度必须为6位',
        'verification_code.verification_api' => '验证码错误',
        'business_license.required'          => '请上传营业执照',
        'scale.required'                     => '请选择规模',
        'scale.integer'                      => '规模类型必须为数字',
        'short_name.required'                => '请填写企业简称',
        'short_name.max'                     => '企业简称长度必须为6位',
    ];

    /**
     * 验证场景.
     * @var \string[][]
     */
    protected $scene = [
        'register' => ['enterprise_name', 'lead', 'address', 'business_license', 'phone', 'verification_code'],
        'create'   => ['enterprise_name', 'lead', 'address', 'business_license', 'phone'],
        'update'   => ['enterprise_name', 'phone'],
    ];

    /**
     * 规则.
     * @var array
     */
    protected function rules()
    {
        return [
            'enterprise_name'   => 'required',
            'lead'              => 'required',
            'address'           => 'required',
            'business_license'  => 'required',
            'phone'             => ['required', 'size:11', 'regex:' . Regex::PHONE_NUMBER],
            'verification_code' => 'required|integer|verification_api:' . request('phone'),
            'scale'             => 'required|integer',
            'short_name'        => 'required|max:6',
        ];
    }
}
