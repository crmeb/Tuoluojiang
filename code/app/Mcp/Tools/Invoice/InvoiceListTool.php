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

namespace App\Mcp\Tools\Invoice;

use App\Constants\ModuleEnum;
use App\Http\Service\Customer\InvoiceService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class InvoiceListTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取发票列表';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => array_merge([
                'customer_id'  => ['type' => 'integer', 'description' => '客户ID'],
                'contract_id'  => ['type' => 'integer', 'description' => '合同ID'],
                'status'       => ['type' => 'integer', 'description' => '发票状态'],
                'invoice_type' => ['type' => 'integer', 'description' => '发票类型'],
                'types'        => ['type' => 'integer', 'description' => '发票类型别名'],
                'category_id'  => ['type' => 'integer', 'description' => '发票类目ID'],
                'view_search'  => ['type' => 'integer', 'description' => '视图类型：1=我负责的，2=我查看的', 'default' => 1],
                'scope_frame'  => ['type' => 'string', 'description' => '数据范围：all=全部，self/my=仅本人，dep=本部门，sub=直属下级，team=本人+直属下级', 'default' => 'self'],
                'time_field'   => ['type' => 'string', 'description' => '时间字段：time/bill_date/real_date/created_at，默认 time'],
                'keyword'      => ['type' => 'string', 'description' => '搜索关键词'],
                'start_date'   => ['type' => 'string', 'description' => '开票开始日期'],
                'end_date'     => ['type' => 'string', 'description' => '开票结束日期'],
                'page'         => ['type' => 'integer', 'description' => '页码', 'default' => 1],
                'limit'        => ['type' => 'integer', 'description' => '每页数量', 'default' => 20],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $this->applyPage($arguments);

        $where = $this->onlyFilled($arguments, [
            'customer_id' => 'eid',
            'contract_id' => 'cid',
            'status',
            'invoice_type' => 'types',
            'types',
            'keyword' => 'name_like',
            'category_id',
        ]);
        $where['entid']       = 1;
        $where['uid']         = $this->getUserDbId();
        $where['view_search'] = (int) ($arguments['view_search'] ?? 1);
        $where['scope_frame'] = $this->normalizeScopeFrame($arguments['scope_frame'] ?? 'self', 'self');
        $where['time_field']  = $arguments['time_field'] ?? 'time';

        if ($date = $this->dateRange($arguments)) {
            $where['time_data'] = $date;
        }

        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::CUSTOMER);
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        if (! empty($authorized['has_filter'])) {
            $where['uid'] = $authorized['user_ids'];
            unset($where['view_search'], $where['scope_frame']);
        }

        return app(InvoiceService::class)->getList($where, ['*'], uid: $this->getUserDbId());
    }
}
