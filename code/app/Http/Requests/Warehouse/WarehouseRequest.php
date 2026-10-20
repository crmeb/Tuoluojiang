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

namespace App\Http\Requests\Warehouse;

use App\Http\Requests\ApiValidate;

/**
 * 仓库基础资料校验.
 */
class WarehouseRequest extends ApiValidate
{
    /**
     * 仓库保存规则.
     */
    protected $scene = [
        'save'   => ['name', 'status', 'sort'],
        'update' => ['name', 'status', 'sort'],
    ];

    /**
     * 获取仓库验证规则.
     */
    public function rules(): array
    {
        return [
            'name'   => 'required|string|max:100',
            'status' => 'nullable|integer|in:0,1',
            'sort'   => 'nullable|integer|min:0',
        ];
    }

    /**
     * 获取仓库验证提示.
     */
    public function message(): array
    {
        return [
            'name.required' => '请填写仓库名称',
            'status.in'     => '仓库状态错误',
            'sort.integer'  => '排序格式错误',
        ];
    }
}
