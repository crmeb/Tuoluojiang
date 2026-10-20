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

use App\Mcp\Tools\Abstract\BaseTool;
use App\Http\Service\Customer\CustomerService;
use App\Mcp\Tools\Traits\InteractsWithService;

class CustomerDetailTool extends BaseTool
{
    use InteractsWithService;

    public function getDescription(): string
    {
        return '根据客户主键 ID 获取客户详细信息（id 为客户表主键，不是线索/合同/账单/回款 ID；不确定时先用客户列表或关键词搜索）';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => '客户主键 ID（非线索/合同/账单 ID）'],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments): array
    {
        $id = $arguments['id'] ?? 0;
        if (!$id) {
            return ['error' => true, 'message' => '客户ID不能为空'];
        }

        $service = app(CustomerService::class);
        $customer = $service->get(['id' => $id]);

        if (!$customer) {
            return [
                'error'   => true,
                'message' => '客户不存在：请确认传入的是客户主键 ID，而非线索/合同/账单 ID；可用客户列表工具按名称搜索',
            ];
        }

        return $customer->toArray();
    }
}
