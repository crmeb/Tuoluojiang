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

namespace App\Mcp\Tools\Warehouse;

use App\Http\Service\Customer\ProductService;
use App\Mcp\Tools\Abstract\BaseTool;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Request;

/**
 * 获取产品（含 SKU）列表，可按仓库查询库存.
 *
 * 供订单/商机写工具组装 products 明细：产品名 → product_id/sku_id；
 * 传 warehouse_id 时附带该仓库的可用库存。
 */
class ProductListTool extends BaseTool
{
    public function getDescription(): string
    {
        return '获取产品列表及其 SKU 规格，可按仓库 ID 查询各 SKU 在该仓库的库存。为订单/商机填写产品明细（products）时调用：先按产品名称取得 sku_id 与单价口径，再组装明细；传 warehouse_id 时返回 good_stock/occupy_stock/available_stock 等库存字段，用于判断可售数量。';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'warehouse_id' => [
                    'type'        => 'integer',
                    'description' => '仓库 ID，传入后返回各 SKU 在该仓库的库存；为空只返回产品与规格主数据（不含库存）。仓库 ID 通过 warehouse_list 获取，不要臆造',
                ],
                'name' => [
                    'type'        => 'string',
                    'description' => '产品名称模糊搜索',
                ],
                'attr' => [
                    'type'        => 'string',
                    'description' => '规格/条码模糊搜索（匹配 SKU 名称或条码）',
                ],
                'pid' => [
                    'type'        => 'integer',
                    'description' => '产品分类 ID，按分类筛选',
                ],
                'filter_stock' => [
                    'type'        => 'integer',
                    'description' => '为 1 时只返回在指定仓库仍有可用库存（good_stock > occupy_stock）的 SKU，需配合 warehouse_id 使用',
                    'default'     => 0,
                ],
                'limit' => [
                    'type'        => 'integer',
                    'description' => '返回产品数量限制，默认 20，最大 100',
                    'default'     => 20,
                ],
                'offset' => [
                    'type'        => 'integer',
                    'description' => '偏移量，默认 0',
                    'default'     => 0,
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $limit  = min(100, max(1, (int) ($arguments['limit'] ?? 20)));
        $offset = max(0, (int) ($arguments['offset'] ?? 0));
        $page   = (int) ($offset / $limit) + 1;

        // ProductService 经 getPageValue() 从请求读取分页，与 OrderListTool 口径一致.
        Request::merge(['page' => $page, 'limit' => $limit]);

        $warehouseId = (int) ($arguments['warehouse_id'] ?? 0);
        $where       = [
            'warehouse_id' => $warehouseId,
            'filter_stock' => $warehouseId > 0 ? (int) ($arguments['filter_stock'] ?? 0) : 0,
        ];
        foreach (['name' => 'name_like', 'attr' => 'attr_like', 'pid' => 'pid'] as $argKey => $whereKey) {
            $value = $arguments[$argKey] ?? '';
            if ($value !== '' && $value !== null && $value !== 0) {
                $where[$whereKey] = $value;
            }
        }

        $result = app(ProductService::class)->getAttrTree($where);

        $list = [];
        foreach ((array) ($result['list'] ?? []) as $product) {
            if (! is_array($product)) {
                continue;
            }
            // attr_value 为 Collection，强转 array 会取到内部属性，需显式 toArray.
            $attrValues = $product['attr_value'] ?? [];
            if ($attrValues instanceof Arrayable) {
                $attrValues = $attrValues->toArray();
            }

            $skus = [];
            foreach ((array) $attrValues as $sku) {
                if (! is_array($sku)) {
                    continue;
                }
                $row = [
                    // unique 是产品明细的定位键（CustomEnum::PRODUCT_PARAMS），组装 products 时必须原样带回.
                    'unique'   => (string) ($sku['unique'] ?? ''),
                    'sku_id'   => (int) ($sku['sku_id'] ?? $sku['id'] ?? 0),
                    'sku'      => (string) ($sku['sku'] ?? ''),
                    'bar_code' => (string) ($sku['bar_code'] ?? ''),
                    'price'    => $sku['price'] ?? null,
                    'ot_price' => $sku['ot_price'] ?? null,
                ];
                if ($warehouseId > 0) {
                    $row['good_stock']      = (int) ($sku['good_stock'] ?? 0);
                    $row['occupy_stock']    = (int) ($sku['occupy_stock'] ?? 0);
                    $row['available_stock'] = (int) ($sku['available_stock'] ?? 0);
                }
                $skus[] = $row;
            }

            // filter_stock 过滤在 SKU 层生效，产品仍会保留；无可用 SKU 的产品对模型无意义.
            if ($skus === [] && ! empty($where['filter_stock'])) {
                continue;
            }

            $list[] = [
                'product_id' => (int) ($product['id'] ?? 0),
                'name'       => (string) ($product['name'] ?? ''),
                'unit_name'  => (string) ($product['unit_name'] ?? ''),
                'category'   => (string) ($product['category'] ?? ''),
                'skus'       => $skus,
            ];
        }

        return [
            'total'        => (int) ($result['count'] ?? count($list)),
            'warehouse_id' => $warehouseId,
            'list'         => $list,
            'hint'         => ($warehouseId > 0
                ? '库存字段仅对该仓库有效，available_stock 为可售数量。'
                : '未指定仓库，未返回库存；需要判断可售数量时先用 warehouse_list 取仓库 ID 再重查。')
                . '组装订单/商机 products 明细时，每项必须原样带上本结果里的 unique，并补齐 price、count、discount、total_price、ot_price、sku、remark 七项（total_price = price × count × discount / 100）。',
        ];
    }
}
