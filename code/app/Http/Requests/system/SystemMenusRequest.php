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

/**
 * 菜单
 * Class SystemMenusRequest.
 */
class SystemMenusRequest extends ApiValidate
{
    /**
     * 设置规则.
     * @var array
     */
    protected $rules = [
        'menu_name' => 'required',
        'type'      => ['required', 'in:0,1'],
        'methods'   => 'required_if:type,1|in:GET,POST,PUT,DELETE',
        'pid'       => 'integer',
        'menu_path' => [
            'regex:' . Regex::PATH_URL,
        ],
        'api' => [
            'regex:' . Regex::API_URL,
        ],
        'sort'      => 'integer',
        'is_header' => 'integer',
        'is_show'   => 'integer',
        'status'    => 'integer',
        'uni_path'  => [
            'regex:' . Regex::PATH_URL,
        ],
    ];

    /**
     * 错误提示.
     * @var string[]
     */
    protected $message = [
        'menu_name.required' => '菜单名称必须填写',
        'methods.in'         => '输入的请求方式不正确',
        'pid.integer'        => '上级ID必须为数字',
        'menu_path.regex'    => '请输入正确的路径地址',
        'sort.integer'       => '排序值只能为数字',
        'api.regex'          => '请输入正确的接口地址',
        'is_header.integer'  => '是否为顶级菜单值只能为整数',
        'is_show.integer'    => '是否为隐藏菜单值只能为整数',
        'status.integer'     => '菜单是否有效值只能为整数',
        'uni_path.regex'     => '请输入正确的移动端路径地址',
    ];

    /**
     * 场景验证
     * @var \string[][]
     */
    protected $scene = [
        'api'  => ['api', 'menu_name', 'methods'],
        'menu' => ['menu_name', 'menu_path', 'uni_path'],
    ];
}
