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

namespace App\Mcp\Tools\Contract;

use App\Constants\ModuleEnum;
use App\Http\Service\Customer\ContractService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class ContractListTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取合同列表';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                'view_search' => ['type' => 'integer', 'description' => '视图类型'],
                'scope_frame' => ['type' => 'string', 'description' => '数据范围'],
                'status' => ['type' => 'integer', 'description' => '合同状态'],
                'keyword' => ['type' => 'string', 'description' => '搜索关键词'],
                'start_date' => ['type' => 'string', 'description' => '签订开始日期'],
                'end_date' => ['type' => 'string', 'description' => '签订结束日期'],
                'page' => ['type' => 'integer', 'description' => '页码', 'default' => 1],
                'limit' => ['type' => 'integer', 'description' => '每页数量', 'default' => 20],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $this->applyPage($arguments);

        $where = $this->onlyFilled($arguments, [
            'customer_id' => 'eid',
            'status',
            'keyword' => 'name_like',
        ]);
        $where['view_search'] = (int) ($arguments['view_search'] ?? 2);
        $where['scope_frame'] = $this->normalizeScopeFrame($arguments['scope_frame'] ?? 'all');

        if ($date = $this->dateRange($arguments)) {
            $where['sign_time'] = $date;
        }

        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::CUSTOMER);
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        if (! empty($authorized['has_filter'])) {
            $where['uid'] = $authorized['user_ids'];
            unset($where['view_search'], $where['scope_frame']);
        }

        return app(ContractService::class)->getList($where, ['*'], ['id' => 'desc']);
    }
}
