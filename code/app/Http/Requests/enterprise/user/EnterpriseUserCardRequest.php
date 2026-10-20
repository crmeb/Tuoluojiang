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

namespace App\Http\Requests\enterprise\user;

use App\Http\Requests\ApiValidate;
use Tuoluojiang\Open\utils\Regex;

/**
 * 用户名片
 * Class EnterpriseUserCardRequest.
 */
class EnterpriseUserCardRequest extends ApiValidate
{
    /**
     * 场景.
     * @var \string[][]
     */
    protected $scene = [
        'edit'             => ['name', 'phone'],
        'update'           => ['phone', 'email'],
        'create_interview' => ['interview_date', 'is_part', 'type', 'name', 'phone'], // 新增面试
        'create_induction' => ['name', 'phone', 'frame', 'position', 'is_admin', 'superior_uid', 'is_part', 'type', 'work_time'], // 新增入职
        'create_departure' => ['name', 'phone', 'frame', 'is_admin', 'superior_uid', 'is_part', 'type', 'work_time', 'quit_time'], // 新增离职
        'update_basic'     => ['interview_date', 'is_part', 'position', 'type', 'name', 'phone'], // 基本信息
        'update_staff'     => ['name', 'phone', 'frame', 'position', 'is_admin', 'superior_uid', 'is_part', 'type', 'work_time'], // 职工信息
        'update_user'      => ['name', 'phone', 'frame', 'position', 'is_admin', 'superior_uid', 'is_part', 'type', 'work_time', 'treaty_time'], // 个人信息
        'update_education' => ['name', 'phone', 'frame', 'position', 'is_admin', 'superior_uid', 'is_part', 'type', 'work_time', 'treaty_time'], // 学历信息
        'update_bank'      => ['name', 'phone', 'frame', 'position', 'is_admin', 'superior_uid', 'is_part', 'type', 'work_time', 'treaty_time'], // 银行卡信息
        'update_social'    => ['name', 'phone', 'frame', 'position', 'is_admin', 'superior_uid', 'is_part', 'type', 'work_time', 'treaty_time'], // 社保信息
        'update_spare'     => ['name', 'phone', 'frame', 'position', 'is_admin', 'superior_uid', 'is_part', 'type', 'work_time', 'treaty_time'], // 紧急联系人
        'update_card'      => ['name', 'phone', 'frame', 'position', 'is_admin', 'superior_uid', 'is_part', 'type', 'work_time', 'treaty_time'], // 个人材料
    ];

    /**
     * 规则.
     * @return array
     */
    public function rules()
    {
        return [
            'name'               => 'required',
            'phone'              => ['required', 'size:11', 'regex:' . Regex::PHONE_NUMBER],
            'email'              => 'email:rfc,dns',
            'sex'                => 'required',
            'interview_date'     => 'required', // 面试时间
            'is_part'            => 'required', // 员工类型
            'position'           => 'required', // 员工职位
            'interview_position' => 'required', // 面试职位
            'type'               => 'required', // 员工状态
            'work_time'          => 'required', // 入职时间
            'quit_time'          => 'required', // 离职时间
        ];
    }

    /**
     * 提示.
     * @return string[]
     */
    public function message()
    {
        return [
            'name.required'               => '请填写用户姓名',
            'phone.required'              => '请填写手机号',
            'phone.size'                  => '请填写正确的手机号',
            'phone.regex'                 => '请填写正确的手机号',
            'email.email'                 => '请填写正确的邮箱',
            'sex.required'                => '用户性别必须填写',
            'interview_date.required'     => '请选择面试时间',
            'interview_position.required' => '请填写面试职位',
            'is_part.required'            => '请选择员工类型',
            'position.required'           => '请选择职位',
            'type.required'               => '请选择员工状态',
            'work_time.required'          => '请选择入职时间',
            'quit_time.required'          => '请选择离职时间',
        ];
    }
}
