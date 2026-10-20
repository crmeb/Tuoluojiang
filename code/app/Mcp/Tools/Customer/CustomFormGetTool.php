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

namespace App\Mcp\Tools\Customer;

use App\Constants\CustomEnum\CustomEnum;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesCustomFormFields;

class CustomFormGetTool extends BaseTool
{
    use ResolvesCustomFormFields;

    public function getDescription(): string
    {
        return '获取客户/线索/商机/订单等自定义创建表单字段清单 fields，供 customer_create / lead_create / opportunity_create / order_create 填写';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['types'],
            'properties' => [
                'types'           => [
                    'type'        => 'integer',
                    'description' => '业务类型：1=客户，4=线索，5=商机，2=订单',
                    'enum'        => [
                        CustomEnum::CUSTOMER,
                        CustomEnum::CONTRACT,
                        CustomEnum::CLUE,
                        CustomEnum::ODDS,
                    ],
                ],
                'include_liaison' => [
                    'type'        => 'boolean',
                    'description' => 'types=客户时是否包含默认联系人字段',
                    'default'     => true,
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $types = (int) ($arguments['types'] ?? 0);
        if (! in_array($types, [CustomEnum::CUSTOMER, CustomEnum::CONTRACT, CustomEnum::CLUE, CustomEnum::ODDS], true)) {
            return ['error' => true, 'message' => '无效的 types，支持 1=客户、4=线索、5=商机、2=订单'];
        }

        $includeLiaison = (bool) ($arguments['include_liaison'] ?? ($types === CustomEnum::CUSTOMER));

        return $this->buildMcpCustomFormPayload($types, [], $includeLiaison);
    }
}
