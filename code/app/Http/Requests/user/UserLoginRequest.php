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

namespace App\Http\Requests\user;

use App\Http\Requests\ApiValidate;
use Tuoluojiang\Open\utils\Regex;

/**
 * Class UserLoginRequest.
 */
class UserLoginRequest extends ApiValidate
{
    /**
     * 场景.
     * @var string[][]
     */
    protected $scene = [
        'login'                => ['account', 'password', 'captcha'],
        'phoneLogin'           => ['phone', 'verification_code'],
        'updatePassword'       => ['phone', 'password', 'password_confirm'],
        'commonUpdatePassword' => ['phone', 'password', 'verification_code'],
        'phone'                => ['phone'],
        'registerUser'         => ['phone', 'verification_code', 'password', 'password_confirm'],
    ];

    /**
     * 验证规则.
     * @return array
     */
    public function rules()
    {
        return [
            'account'  => 'required|min:5|max:50|alpha_num',
            'password' => [
                'required',
                'min:' . sys_config('login_password_length', 5),
                'regex:' . Regex::loginRegex(),
            ],
            'password_confirm' => [
                'required',
                'regex:' . Regex::loginRegex(),
                'password_confirm_api:' . request('password'),
            ],
            'captcha'           => 'required|captcha_api:' . request('key') . ',user',
            'phone'             => ['size:11', 'regex:' . Regex::PHONE_NUMBER],
            'verification_code' => 'required|numeric|verification_api:' . request('phone'),
        ];
    }

    /**
     * 错误提示.
     * @return array
     */
    public function message()
    {
        return [
            'account.required'                      => '账号必须填写',
            'account.min'                           => '账号长度不正确',
            'account.max'                           => '账号长度超出限制',
            'account.alpha_num'                     => '账号不正确',
            'password.required'                     => '密码必须填写',
            'password.min'                          => '密码长度不正确,最少' . sys_config('login_password_length', 5) . '个字符',
            'password.regex'                        => '输入的密码不符合规则,请输入' . get_password_message() . '的密码组合',
            'password_confirm.required'             => '确认密码必须填写',
            'password_confirm.regex'                => '确认密码不符合规则,请输入' . get_password_message() . '的密码组合',
            'password_confirm.password_confirm_api' => '两次输入的密码不一致',
            'captcha.required'                      => '验证码必须填写',
            'captcha.captcha_api'                   => '验证码不正确',
            'phone.size'                            => '手机号码超出限制最大11位',
            'phone.regex'                           => '请输入正确的手机号码',
            'verification_code.required'            => '短信验证码必须填写',
            'verification_code.numeric'             => '短信验证码必须为数字',
            'verification_code.size'                => '短信验证码必须为6位',
            'verification_code.verification_api'    => '请输入正确的短信验证码',
        ];
    }
}
