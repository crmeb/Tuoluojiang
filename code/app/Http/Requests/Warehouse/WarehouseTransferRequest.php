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
 * 仓库调拨单校验.
 */
class WarehouseTransferRequest extends ApiValidate
{
    /**
     * 调拨保存规则.
     */
    protected $scene = [
        'save' => ['out_warehouse_id', 'in_warehouse_id', 'transfer_type', 'details', 'details.*.sku_id', 'details.*.stock_quality', 'details.*.quantity', 'images', 'attach_ids'],
    ];

    /**
     * 获取调拨验证规则.
     */
    public function rules(): array
    {
        return [
            'out_warehouse_id'        => 'required|integer|min:1',
            'in_warehouse_id'         => 'required|integer|min:1|different:out_warehouse_id',
            'transfer_type'           => 'nullable|integer|in:1,2',
            'details'                 => 'required|array',
            'details.*.sku_id'        => 'required|integer|min:1',
            'details.*.stock_quality' => 'nullable|integer|in:1,2',
            'details.*.quantity'      => 'required|integer|min:1',
            'images'                  => 'nullable|array|max:10',
            'attach_ids'              => 'nullable|array|max:10',
        ];
    }

    /**
     * 获取调拨验证提示.
     */
    public function message(): array
    {
        return [
            'out_warehouse_id.required' => '请选择调出仓库',
            'out_warehouse_id.integer'  => '调出仓库格式错误',
            'in_warehouse_id.required'  => '请选择调入仓库',
            'in_warehouse_id.integer'   => '调入仓库格式错误',
            'in_warehouse_id.different' => '调出仓库和调入仓库不能相同',
            'transfer_type.in'          => '调拨类型错误',
            'details.required'          => '请添加商品明细',
            'details.array'             => '商品明细格式错误',
            'details.*.sku_id.required'        => '请选择商品规格',
            'details.*.stock_quality.integer' => '库存属性格式错误',
            'details.*.stock_quality.in'      => '库存属性错误',
            'details.*.quantity.required'     => '请填写调拨数量',
            'details.*.quantity.integer'      => '调拨数量格式错误',
            'details.*.quantity.min'          => '调拨数量必须大于0',
            'images.array'                    => '凭证附件格式错误',
            'images.max'                      => '凭证附件最多上传10个',
            'attach_ids.array'                => '凭证附件格式错误',
            'attach_ids.max'                  => '凭证附件最多上传10个',
        ];
    }
}
