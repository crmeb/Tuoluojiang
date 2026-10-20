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

namespace App\Http\Requests\system;

use App\Http\Requests\ApiValidate;
use Tuoluojiang\Open\utils\Regex;

class SystemAdminRequest extends ApiValidate
{
    /**
     * 场景.
     * @var \string[][]
     */
    protected $scene = [
        'login'    => ['account', 'password', 'captcha'],
        'save'     => ['account', 'password', 'avatar', 'real_name', 'roles'],
        'update'   => ['account', 'avatar', 'real_name', 'roles'],
        'password' => ['password', 'password_confirm'],
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account'  => 'required|min:5|max:50|alpha_num',
            'password' => [
                'required',
                'min:' . sys_config('login_password_length', 5),
                'max:50',
                'regex:' . Regex::loginRegex(),
            ],
            'password_confirm' => [
                'required',
                'regex:' . Regex::loginRegex(),
                'password_confirm_api:' . request('password'),
            ],
            'captcha'   => 'required|captcha_api:' . request('key') . ',admin',
            'avatar'    => 'required',
            'real_name' => 'required',
            'roles'     => 'required',
        ];
    }

    /**
     * 设置错误提醒.
     * @return string[]
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
            'password.max'                          => '密码长度不正确',
            'password.regex'                        => '输入的密码不符合规则,请输入' . get_password_message() . '的组合',
            'password_confirm.password_confirm_api' => '两次输入的密码不一致',
            'password_confirm.required'             => '请填写确认密码',
            'password_confirm.regex'                => '确认密码格式不正确,请输入' . get_password_message() . '的组合',
            'captcha.required'                      => '验证码必须填写',
            'captcha.captcha_api'                   => '验证码不正确',
            'avatar.required'                       => '请选择头像',
            'real_name.required'                    => '请填写管理员姓名',
            'roles.required'                        => '请选择管理员身份',
        ];
    }
}
