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

namespace App\Mcp\Tools\Personnel;

use App\Http\Service\Admin\AdminService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesPersonnelEmploymentStatus;

/**
 * 人员统计概览工具
 * Class PersonnelStatsOverviewTool.
 */
class PersonnelStatsOverviewTool extends BaseTool
{
    use ResolvesPersonnelEmploymentStatus;

    public function getDescription(): string
    {
        return '获取人员统计概览（基于数据权限统计）';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'employment_status' => $this->personnelEmploymentStatusSchema('active'),
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $service          = app(AdminService::class);
        $employmentStatus = $this->resolvePersonnelEmploymentStatus($arguments, 'active');
        $userId           = $this->getUserDbId();
        $userInfo         = $service->getInfo($userId, ['id']);
        $entId            = $userInfo['ent_id'] ?? 1;
        $where            = [
            'entid'  => $entId,
            'status' => 1,
            'types'  => $this->personnelTypesForEmploymentStatus($employmentStatus),
            'time'   => '',
        ];
        if (! $this->isAdmin()) {
            $where['ids'] = $this->getPersonnelDataUidsForEmploymentStatus($employmentStatus) ?: [-1];
        }

        $today          = date('Y/m/d') . '-' . date('Y/m/d');
        $todayNew       = 0;
        $todayResigned  = 0;
        $baseTodayWhere = array_merge($where, ['time' => $today]);

        if ($employmentStatus === 'resigned') {
            $todayResigned = $this->countPersonnel($service, $baseTodayWhere);
        } elseif ($employmentStatus === 'all') {
            $todayNew = $this->countPersonnel($service, array_merge($baseTodayWhere, [
                'types' => $this->personnelTypesForEmploymentStatus('active'),
            ]));
            $todayResigned = $this->countPersonnel($service, array_merge($baseTodayWhere, [
                'types' => $this->personnelTypesForEmploymentStatus('resigned'),
            ]));
        } else {
            $todayNew = $this->countPersonnel($service, $baseTodayWhere);
        }

        return [
            'employment_status' => $employmentStatus,
            'total'             => $this->countPersonnel($service, $where),
            'today_new'         => $todayNew,
            'today_resigned'    => $todayResigned,
        ];
    }

    private function countPersonnel(AdminService $service, array $where): int
    {
        return (int) ($service->adminList($where, 1, 1)['count'] ?? 0);
    }
}
