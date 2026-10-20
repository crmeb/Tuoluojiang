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

namespace App\Mcp\Tools\Order;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\System\ConfigEnum;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\ProductAttrValueService;
use App\Http\Service\Warehouse\WarehouseService;
use App\Http\Service\Warehouse\WarehouseStockService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesCustomFormFields;

class OrderCreateTool extends BaseTool
{
    use ResolvesCustomFormFields;

    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '新增订单，字段根据租户自定义表单动态生成，创建人固定为当前 MCP 用户；仅用于订单，禁止用于创建合同（合同为独立模块，当前无创建工具）';
    }

    public function getInputSchema(): array
    {
        return $this->buildCreateInputSchema(CustomEnum::CONTRACT, [
            'products' => [
                'type'        => 'array',
                'description' => '产品明细列表。先用 product_list 解析出每个 SKU 的 unique 与价格，再逐项组装；不提供产品时可省略该参数，禁止只传产品名称',
                'items'       => [
                    'type'       => 'object',
                    'required'   => CustomEnum::PRODUCT_PARAMS,
                    'properties' => [
                        'unique'      => ['type' => 'string', 'description' => 'SKU 唯一标识，取自 product_list 返回的 unique，必须原样带回'],
                        'price'       => ['type' => 'string', 'description' => '成交单价'],
                        'count'       => ['type' => 'number', 'description' => '数量'],
                        'discount'    => ['type' => 'number', 'description' => '折扣百分比，不打折传 100'],
                        'total_price' => ['type' => 'string', 'description' => '小计 = price × count × discount / 100'],
                        'ot_price'    => ['type' => 'string', 'description' => '原价，取自 product_list 返回的 ot_price'],
                        'sku'         => ['type' => 'string', 'description' => '规格名称，取自 product_list 返回的 sku'],
                        'remark'      => ['type' => 'string', 'description' => '备注，无则传空字符串'],
                    ],
                ],
            ],
        ]);
    }

    public function execute(array $arguments): array
    {
        $data     = $this->buildCreatePayload($arguments, CustomEnum::CONTRACT);
        $products = (array) ($arguments['products'] ?? []);
        $this->assertStockSufficient($products, (int) ($arguments['outbound_warehouse_id'] ?? 0));
        $res      = app(OrderService::class)->saveContract($data, $this->getUserDbId(), $products);

        return ['id' => (int) $res->id, 'success' => true];
    }

    /**
     * 下单前校验所选仓库的可售库存。
     *
     * 开启「添加订单时自动生成出库单」后，saveContract 会在同一事务里占用库存，
     * 库存不足时底层只抛「可用库存不足」，看不出是哪个规格、差多少。这里提前拦下并说清楚。
     *
     * @param array<int, array<string, mixed>> $products
     */
    private function assertStockSufficient(array $products, int $warehouseId): void
    {
        if ($products === [] || $warehouseId <= 0) {
            return;
        }
        // 不自动出库时订单不占用库存，无需拦截.
        if ((int) sys_config(ConfigEnum::ORDER_AUTO_OUTBOUND_RULE['key'], 0) !== 1) {
            return;
        }

        $wanted = [];
        foreach ($products as $product) {
            if (! is_array($product)) {
                continue;
            }
            $unique = trim((string) ($product['unique'] ?? ''));
            if ($unique === '') {
                continue;
            }
            $wanted[$unique] = ($wanted[$unique] ?? 0) + max(0, (int) ($product['count'] ?? 0));
        }
        if ($wanted === []) {
            return;
        }

        $skus = app(ProductAttrValueService::class)
            ->select(['unique' => array_keys($wanted)], with: ['product'])?->toArray() ?: [];
        $stockService = app(WarehouseStockService::class);
        $warehouse    = app(WarehouseService::class)->get($warehouseId, ['name']);
        $shortages    = [];
        foreach ($skus as $sku) {
            $unique = (string) ($sku['unique'] ?? '');
            $need   = (int) ($wanted[$unique] ?? 0);
            if ($need <= 0) {
                continue;
            }
            $available = $stockService->effectiveGoodStock($warehouseId, (int) ($sku['id'] ?? 0));
            if ($available >= $need) {
                continue;
            }
            $name = (string) ($sku['product']['name'] ?? '');
            $spec = (string) ($sku['sku'] ?? '');
            $shortages[] = ($spec !== '' ? $name . '「' . $spec . '」' : $name)
                . '需要 ' . $need . '，可售 ' . $available;
        }

        if ($shortages !== []) {
            throw new \RuntimeException(
                '出库仓库「' . (string) ($warehouse->name ?? $warehouseId) . '」可售库存不足：'
                . implode('；', $shortages)
                . '。请减少数量、更换仓库或补货后重试'
            );
        }
    }
}
