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
use App\Http\Service\Customer\PaymentService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class BillListTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取客户账目、回款及收款明细列表';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                'contract_id' => ['type' => 'integer', 'description' => '合同ID'],
                'cate_id' => ['type' => 'integer', 'description' => '账目分类ID'],
                'types' => ['type' => 'integer', 'description' => '账目类型'],
                'status' => ['type' => 'integer', 'description' => '收款状态'],
                'keyword' => ['type' => 'string', 'description' => '搜索关键词'],
                'start_date' => ['type' => 'string', 'description' => '账单开始日期'],
                'end_date' => ['type' => 'string', 'description' => '账单结束日期'],
                'page' => ['type' => 'integer', 'description' => '页码', 'default' => 1],
                'limit' => ['type' => 'integer', 'description' => '每页数量', 'default' => 20],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $this->applyPage($arguments);

        $where = array_merge([
            'eid'       => '',
            'cid'       => '',
            'cate_id'   => '',
            'time'      => '',
            'status'    => '',
            'field_key' => '',
            'name'      => '',
            'entid'     => 1,
            'date'      => '',
        ], $this->onlyFilled($arguments, [
            'customer_id' => 'eid',
            'contract_id' => 'cid',
            'cate_id',
            'types',
            'status',
            'keyword' => 'name',
        ]));
        if ($date = $this->dateRange($arguments)) {
            $where['date'] = $date;
        }

        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::CUSTOMER);
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        if (! empty($authorized['has_filter'])) {
            $where['uid'] = $authorized['user_ids'];
        }

        return app(PaymentService::class)->getBillList($where);
    }
}
