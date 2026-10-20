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

namespace App\Mcp\Tools\Traits;

use App\Http\Service\Approve\ApproveApplyService;
use App\Mcp\Tools\Traits\InteractsWithService as InteractsWithServiceTrait;

trait ResolvesApproveListQuery
{
    use InteractsWithServiceTrait;

    protected function fetchApproveList(array $arguments, array $fixedWhere): array
    {
        $this->applyPage($arguments);

        return app(ApproveApplyService::class)->getList(
            $this->buildApproveListWhere($arguments, $fixedWhere)
        );
    }

    /**
     * 组装审批列表 where：补齐 Admin 端默认空键，避免 getWhere 缺键报错.
     *
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $fixedWhere
     * @return array<string, mixed>
     */
    protected function buildApproveListWhere(array $arguments, array $fixedWhere): array
    {
        // Admin 列表接口始终传 name/frame_id/verify_status（默认为空串）；MCP onlyFilled 会省略空参.
        $where = array_merge([
            'name'          => '',
            'frame_id'      => '',
            'verify_status' => '',
        ], $fixedWhere, $this->onlyFilled($arguments, [
            'approve_id',
            'status',
            'name',
            'frame_id',
        ]));

        if ($time = $this->timeRange($arguments)) {
            $where['time'] = $time;
        }

        $where['entid']   = $this->getEntId();
        $where['examine'] = 1;

        return $where;
    }
}
