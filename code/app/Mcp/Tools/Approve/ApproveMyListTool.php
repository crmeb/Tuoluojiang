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

namespace App\Mcp\Tools\Approve;

use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesApproveListQuery;

class ApproveMyListTool extends BaseTool
{
    use ResolvesApproveListQuery;

    public function getDescription(): string
    {
        return '查看我发起的审批列表（专用工具，无需传 types 参数）';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'approve_id' => ['type' => 'integer', 'description' => '审批类型 ID'],
                'status'     => ['type' => 'integer', 'description' => '审批状态'],
                'time'       => ['type' => 'string', 'description' => '时间范围'],
                'start_date' => ['type' => 'string', 'description' => '开始日期 YYYY-MM-DD'],
                'end_date'   => ['type' => 'string', 'description' => '结束日期 YYYY-MM-DD'],
                'name'       => ['type' => 'string', 'description' => '申请人姓名关键词'],
                'frame_id'   => ['type' => 'integer', 'description' => '部门 ID'],
                'page'       => ['type' => 'integer', 'description' => '页码，默认 1'],
                'limit'      => ['type' => 'integer', 'description' => '每页数量，默认 20，最大 100'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        return $this->fetchApproveList($arguments, ['types' => 0]);
    }
}
