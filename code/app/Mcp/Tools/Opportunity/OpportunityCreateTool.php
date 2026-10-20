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

namespace App\Mcp\Tools\Opportunity;

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Service\Customer\OpportunityService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesCustomFormFields;

class OpportunityCreateTool extends BaseTool
{
    use ResolvesCustomFormFields;

    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '新增商机，字段根据租户自定义表单动态生成，创建人固定为当前 MCP 用户';
    }

    public function getInputSchema(): array
    {
        return $this->buildCreateInputSchema(CustomEnum::ODDS, [
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
        $data     = $this->buildCreatePayload($arguments, CustomEnum::ODDS);
        $products = (array) ($arguments['products'] ?? []);
        $res      = app(OpportunityService::class)->saveOdds($data, $this->getUserDbId(), $products);

        return ['id' => (int) $res->id, 'success' => true];
    }
}
