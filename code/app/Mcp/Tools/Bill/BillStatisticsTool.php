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

namespace App\Mcp\Tools\Bill;

use App\Constants\ModuleEnum;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\PaymentService;
use App\Mcp\Guards\McpDataPermissionGuard;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

/**
 * 客户账目 / 业绩汇总统计.
 * 汇总口径对齐管理端 POST client/customer/statistics（CustomerService::getStatistics）.
 */
class BillStatisticsTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取客户账目、回款、收款及业绩汇总统计；默认对齐管理端业绩统计（回款、续费、新客户、新订单、未收金额、部门业绩）';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'customer_id' => ['type' => 'integer', 'description' => '客户ID；指定后返回该客户账目统计'],
                'contract_id' => ['type' => 'integer', 'description' => '合同/订单ID；指定后返回该合同账目统计'],
                'time' => ['type' => 'string', 'description' => '时间范围，格式 YYYY/MM/DD-YYYY/MM/DD'],
                'start_date' => ['type' => 'string', 'description' => '开始日期，YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期，YYYY-MM-DD'],
                'user_id' => ['type' => 'array', 'description' => '销售人员用户ID列表，将按客户数据权限取交集', 'items' => ['type' => 'integer']],
                'uid' => ['type' => 'array', 'description' => '销售人员用户ID列表别名', 'items' => ['type' => 'integer']],
                'category_id' => ['type' => 'array', 'description' => '订单/合同分类ID列表', 'items' => ['type' => 'integer']],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $paymentService = app(PaymentService::class);
        if (! empty($arguments['contract_id'])) {
            $denied = $this->denyIfContractInvisible((int) $arguments['contract_id']);
            if ($denied) {
                return $denied;
            }

            return $paymentService->getContractStatistics((int) $arguments['contract_id'], $this->getEntId());
        }
        if (! empty($arguments['customer_id'])) {
            $denied = $this->denyIfCustomerInvisible((int) $arguments['customer_id']);
            if ($denied) {
                return $denied;
            }

            return $paymentService->getCustomerStatistics((int) $arguments['customer_id'], $this->getEntId());
        }

        $scoped = $this->resolveScopedCustomerUserIds($arguments);
        if (! ($scoped['success'] ?? false)) {
            return $scoped['error'];
        }

        $time = $this->timeRange($arguments);
        $data = app(CustomerService::class)->getStatistics(
            $time,
            $scoped['user_ids'] ?? [],
            $this->resolvePerformanceCategoryIds($arguments)
        );

        return array_merge($data, [
            '_meta' => [
                'metric' => 'sales_performance',
                'source' => 'CustomerService::getStatistics',
                'time'   => $time !== '' ? $time : 'current_month',
            ],
        ]);
    }

    private function denyIfCustomerInvisible(int $customerId): ?array
    {
        if ($this->isAdmin()) {
            return null;
        }

        $customer = app(CustomerService::class)->get(['id' => $customerId], ['id', 'uid']);
        if (! $customer) {
            return ['error' => true, 'message' => '客户不存在'];
        }

        if ($this->hasPermissionToUser((int) $customer->uid, ModuleEnum::CUSTOMER)) {
            return null;
        }

        return app(McpDataPermissionGuard::class)->permissionDeniedResult(ModuleEnum::CUSTOMER, [(int) $customer->uid]);
    }

    private function denyIfContractInvisible(int $contractId): ?array
    {
        if ($this->isAdmin()) {
            return null;
        }

        $contract = app(OrderService::class)->get(['id' => $contractId], ['id', 'uid', 'eid']);
        if (! $contract) {
            return ['error' => true, 'message' => '订单不存在'];
        }

        if ($this->hasPermissionToUser((int) $contract->uid, ModuleEnum::CUSTOMER)) {
            return null;
        }

        return app(McpDataPermissionGuard::class)->permissionDeniedResult(ModuleEnum::CUSTOMER, [(int) $contract->uid]);
    }
}
