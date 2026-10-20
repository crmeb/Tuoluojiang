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

namespace App\Http\Requests\enterprise\user;

use App\Http\Requests\ApiValidate;

/**
 * 工作报告
 * Class EnterpriseUserDailyRequest.
 */
class EnterpriseUserDailyRequest extends ApiValidate
{
    /**
     * 验证规则.
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'finish' => 'required',
            // 'plan'    => 'required|array',
            'members' => 'required|array',
        ];
    }

    /**
     * 错误提醒.
     * @return array
     */
    public function message()
    {
        return [
            'finish.required'  => '请填写工作总结',
            'plan.required'    => '请填写工作计划',
            'members.required' => '请选择汇报人',
            'members.array'    => '请选择正确的汇报人',
        ];
    }
}
