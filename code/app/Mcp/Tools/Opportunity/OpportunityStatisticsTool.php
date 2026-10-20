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

use App\Http\Service\Customer\OpportunityService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;

class OpportunityStatisticsTool extends BaseTool
{
    use InteractsWithService;

    public function getDescription(): string
    {
        return '获取商机数量、状态及转化统计';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'start_date' => ['type' => 'string', 'description' => '开始日期'],
                'end_date' => ['type' => 'string', 'description' => '结束日期'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(OpportunityService::class);
        $where   = $this->onlyFilled($arguments, ['status']);
        if ($date = $this->dateRange($arguments)) {
            $where['time'] = $date;
        }

        return [
            'total'     => $service->count($where),
            'active'    => $service->count($where + ['status' => 1]),
            'converted' => $service->count($where + ['status' => 2]),
            'closed'    => $service->count($where + ['status' => 3]),
        ];
    }
}
