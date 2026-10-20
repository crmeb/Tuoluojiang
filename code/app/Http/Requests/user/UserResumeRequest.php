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

namespace App\Http\Requests\user;

use App\Http\Requests\ApiValidate;
use Tuoluojiang\Open\utils\Regex;

class UserResumeRequest extends ApiValidate
{
    /**
     * 场景.
     * @var \string[][]
     */
    protected $scene = [
        'register'        => ['account', 'password'],
        'update_phone'    => ['phone', 'verification_code'],
        'update_email'    => ['email'],
        'update_password' => ['password', 'password_confirm'],
        'only_phone'      => ['phone'],
    ];

    /**
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
                'password_confirm_api:' . request('password_confirm'),
            ],
            'password_confirm' => [
                'required',
                'regex:' . Regex::loginRegex(),
            ],
            'avatar'          => 'dimensions:min_width=100,min_height=200',
            'real_name'       => 'max:21',
            'education'       => 'max:100',
            'nation'          => 'max:20',
            'birthplace'      => 'max:20',
            'card_id'         => ['size:18', 'regex:' . Regex::CARD_ID],
            'province'        => 'max:20',
            'city'            => 'max:20',
            'area'            => 'max:20',
            'current_address' => 'max:255',
            'home_address'    => 'max:255',
            'telephone'       => 'max:20',
            'phone'           => [
                'size:11',
                'regex:' . Regex::PHONE_NUMBER,
            ],
            'verification_code'      => 'required|integer|verification_api:' . request('phone'),
            'email'                  => 'email:rfc,dns',
            'standby_contacts'       => 'max:20',
            'standby_contacts_phone' => ['size:11', 'regex:' . Regex::PHONE_NUMBER],
            'bank'                   => 'max:50',
            'bank_number'            => ['max:21', 'regex:' . Regex::BANK_NUMBER],
            'age'                    => 'max:100',
            'marriage'               => 'integer',
            'sex'                    => 'integer',
        ];
    }

    /**
     * 提示.
     * @return string[]
     */
    public function message()
    {
        return [
            'account.required'                   => '请填写账号',
            'account.min'                        => '账号最小长度为5位',
            'account.max'                        => '您的账号超出限制最大长度位50位',
            'account.alpha_num'                  => '账号只能位数组和字母的组合',
            'password.required'                  => '请填写密码',
            'password.min'                       => '密码长度不正确,最少' . sys_config('login_password_length', 5) . '个字符',
            'password.regex'                     => '密码格式不正确,请输入' . get_password_message() . '的组合',
            'password.password_confirm_api'      => '两次输入的密码不一致',
            'password_confirm.required'          => '请填写确认密码',
            'password_confirm.regex'             => '确认密码格式不正确,请输入' . get_password_message() . '的组合',
            'avatar.dimensions'                  => '头像尺寸不符合规则,请上传宽大于100高大于200的头像',
            'real_name.max'                      => '姓名长度超出限制最大个7个中文字符',
            'education.max'                      => '学籍长度超出限制最大100个字符',
            'nation.max'                         => '民族长度超出限制最大20个字符',
            'birthplace.max'                     => '籍贯长度超出限制最大20个字符',
            'card_id.size'                       => '身份证号码必须为18位',
            'card_id.regex'                      => '请输入正确的身份证号码',
            'province.max'                       => '省份长度超出限制最大20个字符',
            'city.max'                           => '市长度超出限制最大20个字符',
            'area.max'                           => '区长度超出限制最大20个字符',
            'current_address.max'                => '现居住地长度超出限制最大长度255个字符',
            'home_address.max'                   => '家庭住址长度超出限制最大长度255个字符',
            'telephone.max'                      => '电话长度超出限制最大20位',
            'phone.size'                         => '手机号码超出限制最大11位',
            'phone.regex'                        => '请输入正确的手机号码',
            'email.email'                        => '请输入正确的email地址',
            'standby_contacts.max'               => '备用人手机号码超出限制最大20个字符',
            'standby_contacts_phone.size'        => '备用联系人手机号码不正确',
            'standby_contacts_phone.regex'       => '备用联系人手机号码不符合规范',
            'bank.max'                           => '银行开户行超出限制最大50个字符',
            'bank_number.max'                    => '银行卡号超出限制最大21个字符',
            'bank_number.regex'                  => '请输入正确的银行卡号',
            'age.max'                            => '年龄不能大于100岁',
            'marriage.integer'                   => '婚姻情况只能位数字',
            'sex.integer'                        => '性别只能位数字',
            'verification_code.required'         => '验证码必须填写',
            'verification_code.integer'          => '验证码必须为数字',
            'verification_code.verification_api' => '验证码错误',
        ];
    }
}
