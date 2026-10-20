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

use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Requests\ApiValidate;
use Illuminate\Validation\Rule;

/**
 * 仓储业务单据校验.
 */
class WarehouseBillRequest extends ApiValidate
{
    /**
     * 仓储单据验证场景.
     */
    protected $scene = [
        'inbound' => [
            'warehouse_id', 'inbound_type', 'source_type', 'source_no', 'images', 'attach_ids',
            'details', 'details.*.sku_id', 'details.*.quantity', 'details.*.inbound_quantity',
            'details.*.stock_quantity', 'details.*.num', 'details.*.stock_quality',
            'details.*.unit_price', 'details.*.price', 'details.*.sales_price', 'details.*.sell_price',
            'details.*.gift_quantity', 'details.*.gift_num',
        ],
        'outbound' => [
            'warehouse_id', 'outbound_type', 'source_type', 'source_id', 'source_no', 'images', 'attach_ids',
            'details', 'details.*.sku_id', 'details.*.quantity', 'details.*.outbound_quantity',
            'details.*.stock_quantity', 'details.*.num', 'details.*.stock_quality',
        ],
        'convert'  => ['warehouse_id', 'convert_type', 'details', 'details.*.sku_id', 'details.*.quantity', 'details.*.remark', 'details.*.images'],
        'check'    => ['warehouse_id', 'images', 'attach_ids', 'is_draft', 'details', 'details.*.sku_id', 'details.*.real_good_stock', 'details.*.real_defect_stock'],
        'continue' => ['warehouse_id', 'images', 'attach_ids', 'is_draft', 'details', 'details.*.sku_id', 'details.*.real_good_stock', 'details.*.real_defect_stock'],
    ];

    /**
     * 获取仓储单据验证规则.
     */
    public function rules(): array
    {
        return [
            'warehouse_id'                => 'required|integer|min:1',
            'inbound_type'                => 'nullable|integer|in:' . implode(',', WarehouseEnum::INBOUND_TYPES),
            'outbound_type'               => 'nullable|integer|in:' . implode(',', WarehouseEnum::OUTBOUND_TYPES),
            'source_type'                 => 'nullable|integer|in:' . implode(',', WarehouseEnum::SOURCE_TYPES),
            'source_id'                   => 'nullable|integer|min:0',
            'source_no'                   => 'nullable|string|max:64',
            'details'                     => 'nullable|array',
            'details.*.sku_id'            => 'required|integer|min:1',
            'details.*.quantity'          => 'nullable|integer|min:1',
            'details.*.outbound_quantity' => 'nullable|integer|min:1',
            'details.*.inbound_quantity'  => 'nullable|integer|min:1',
            'details.*.stock_quantity'    => 'nullable|integer|min:1',
            'details.*.num'               => 'nullable|integer|min:1',
            'details.*.stock_quality'     => ['nullable', 'integer', Rule::in(WarehouseEnum::STOCK_QUALITY_OPTIONS)],
            'details.*.unit_price'        => 'nullable|numeric|min:0',
            'details.*.price'             => 'nullable|numeric|min:0',
            'details.*.sales_price'       => 'nullable|numeric|min:0',
            'details.*.sell_price'        => 'nullable|numeric|min:0',
            'details.*.gift_quantity'     => 'nullable|integer|min:0',
            'details.*.gift_num'          => 'nullable|integer|min:0',
            'details.*.real_good_stock'   => 'nullable|integer|min:0',
            'details.*.real_defect_stock' => 'nullable|integer|min:0',
            'details.*.remark'            => 'nullable|string|max:1000',
            'details.*.images'            => 'nullable|array|max:10',
            'convert_type'                => 'nullable|integer|in:1,2',
            'is_draft'                    => 'nullable|boolean',
            'images'                      => 'nullable|array|max:10',
            'attach_ids'                  => 'nullable|array|max:10',
        ];
    }

    /**
     * 获取仓储单据验证提示.
     */
    public function message(): array
    {
        return [
            'warehouse_id.required'               => '请选择仓库',
            'warehouse_id.integer'                => '仓库格式错误',
            'inbound_type.in'                     => '入库类型错误',
            'outbound_type.in'                    => '出库类型错误',
            'source_type.integer'                 => '来源类型格式错误',
            'source_type.in'                      => '来源类型错误',
            'details.required'                    => '请添加商品明细',
            'details.array'                       => '商品明细格式错误',
            'details.*.sku_id.required'           => '请选择商品规格',
            'details.*.sku_id.integer'            => '商品规格格式错误',
            'details.*.quantity.required'         => '请填写商品数量',
            'details.*.quantity.integer'          => '商品数量格式错误',
            'details.*.quantity.min'              => '商品数量必须大于0',
            'details.*.outbound_quantity.integer' => '出库数量格式错误',
            'details.*.outbound_quantity.min'     => '出库数量必须大于0',
            'details.*.inbound_quantity.integer'  => '入库数量格式错误',
            'details.*.inbound_quantity.min'      => '入库数量必须大于0',
            'details.*.stock_quantity.integer'    => '入库数量格式错误',
            'details.*.stock_quantity.min'        => '入库数量必须大于0',
            'details.*.num.integer'               => '入库数量格式错误',
            'details.*.num.min'                   => '入库数量必须大于0',
            'details.*.stock_quality.integer'     => '库存属性格式错误',
            'details.*.stock_quality.in'          => '库存属性格式错误',
            'details.*.unit_price.numeric'        => '入库单价格式错误',
            'details.*.unit_price.min'            => '入库单价不能小于0',
            'details.*.price.numeric'             => '入库单价格式错误',
            'details.*.price.min'                 => '入库单价不能小于0',
            'details.*.sales_price.numeric'       => '入库单价格式错误',
            'details.*.sales_price.min'           => '入库单价不能小于0',
            'details.*.sell_price.numeric'        => '入库单价格式错误',
            'details.*.sell_price.min'            => '入库单价不能小于0',
            'details.*.gift_quantity.integer'     => '赠送数量格式错误',
            'details.*.gift_quantity.min'         => '赠送数量不能小于0',
            'details.*.gift_num.integer'          => '赠送数量格式错误',
            'details.*.gift_num.min'              => '赠送数量不能小于0',
            'details.*.real_good_stock.integer'   => '良品实盘库存格式错误',
            'details.*.real_good_stock.min'       => '良品实盘库存不能小于0',
            'details.*.real_defect_stock.integer' => '残次品实盘库存格式错误',
            'details.*.real_defect_stock.min'     => '残次品实盘库存不能小于0',
            'details.*.remark.max'                => '商品备注最多1000个字符',
            'details.*.images.array'              => '商品凭证附件格式错误',
            'details.*.images.max'                => '商品凭证附件最多上传10个',
            'convert_type.in'                     => '转换类型错误',
            'is_draft.boolean'                    => '保存类型格式错误',
            'images.array'                        => '凭证附件格式错误',
            'images.max'                          => '凭证附件最多上传10个',
            'attach_ids.array'                    => '凭证附件格式错误',
            'attach_ids.max'                      => '凭证附件最多上传10个',
        ];
    }
}
