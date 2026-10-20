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

/**
 * Class SystemRoleRequest.
 */
class SystemRoleRequest extends ApiValidate
{
    /**
     * 验证规则.
     * @var string[]
     */
    protected $rules = [
        'role_name'          => 'required',
        'rules'               => 'required',
        'status'              => 'integer',
        'module_permission'   => 'array',
    ];

    /**
     * 设置错误提示.
     * @var string[]
     */
    protected $message = [
        'role_name.required' => '角色名称必须填写',
        'rules.required'     => '至少选择一个权限',
        'status.integer'     => '状态值必须为数字',
    ];
}
