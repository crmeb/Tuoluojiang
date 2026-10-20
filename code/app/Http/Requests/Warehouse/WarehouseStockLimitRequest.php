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
 * 库存上下限校验.
 */
class WarehouseStockLimitRequest extends ApiValidate
{
    /**
     * 库存上下限保存规则.
     */
    protected $scene = [
        'save'       => ['id', 'low_limit', 'high_limit'],
        'batch_save' => ['ids', 'ids.*', 'low_limit', 'high_limit'],
    ];

    /**
     * 获取库存上下限验证规则.
     */
    public function rules(): array
    {
        return [
            'id'         => 'required|integer|min:1',
            'ids'        => 'required|array|min:1',
            'ids.*'      => 'integer|min:1|distinct',
            'low_limit'  => 'nullable|integer|min:0',
            'high_limit' => 'nullable|integer|min:0',
        ];
    }

    /**
     * 获取库存上下限验证提示.
     */
    public function message(): array
    {
        return [
            'id.required'        => '请选择库存记录',
            'id.integer'         => '库存记录格式错误',
            'id.min'             => '库存记录格式错误',
            'ids.required'       => '请选择库存记录',
            'ids.array'          => '库存记录格式错误',
            'ids.min'            => '请至少选择一条库存记录',
            'ids.*.integer'      => '库存记录格式错误',
            'ids.*.min'          => '库存记录格式错误',
            'ids.*.distinct'     => '库存记录不能重复',
            'low_limit.integer'  => '库存下限格式错误',
            'low_limit.min'      => '库存下限不能小于0',
            'high_limit.integer' => '库存上限格式错误',
            'high_limit.min'     => '库存上限不能小于0',
        ];
    }
}
