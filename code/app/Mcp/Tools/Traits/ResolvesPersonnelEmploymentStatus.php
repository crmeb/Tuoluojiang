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

trait ResolvesPersonnelEmploymentStatus
{
    protected function personnelEmploymentStatusSchema(string $default = 'active'): array
    {
        return [
            'type'        => 'string',
            'description' => '人员状态筛选：active=在职，resigned=离职，all=全部',
            'enum'        => ['active', 'resigned', 'all'],
            'default'     => $default,
        ];
    }

    protected function resolvePersonnelEmploymentStatus(array $arguments, string $default = 'active'): string
    {
        $status = strtolower(trim((string) ($arguments['employment_status'] ?? $default)));

        return match ($status) {
            'active', 'employed', 'working', 'in_service', 'on', '1', '在职' => 'active',
            'resigned', 'quit', 'left', 'inactive', 'off', '4', '离职'       => 'resigned',
            'all', 'both', '全部'                                            => 'all',
            default                                                          => $default,
        };
    }

    protected function personnelTypesForEmploymentStatus(string $employmentStatus): array
    {
        return match ($employmentStatus) {
            'resigned' => [4],
            'all'      => [1, 2, 3, 4],
            default    => [1, 2, 3],
        };
    }

    protected function getPersonnelDataUidsForEmploymentStatus(string $employmentStatus): array
    {
        return $this->getMcpRequest()->getDataUids('personnel', 1, $employmentStatus === 'active');
    }
}
