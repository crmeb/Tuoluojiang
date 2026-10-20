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
use App\Mcp\Tools\Traits\FiltersPersonnelOutput;

/**
 * 人员搜索工具
 * Class PersonnelSearchTool.
 */
class PersonnelSearchTool extends BaseTool
{
    use FiltersPersonnelOutput;

    public function getDescription(): string
    {
        return '模糊搜索人员（基于数据权限过滤）';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'keyword' => ['type' => 'string', 'description' => '搜索关键词'],
                'limit'   => ['type' => 'integer', 'description' => '返回结果数量，默认20', 'default' => 20],
            ],
            'required' => ['keyword'],
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(AdminService::class);
        $keyword = trim((string) ($arguments['keyword'] ?? ''));
        if ($keyword === '') {
            return ['list' => [], 'count' => 0];
        }

        $where       = ['entid' => 1, 'status' => 1, 'search' => $keyword, 'types' => [1, 2, 3], 'time' => ''];
        $limit       = min(100, max(1, (int) ($arguments['limit'] ?? 20)));
        $allowedUids = $this->isAdmin() ? [] : $this->getDataUids('personnel', 1);
        if (! $this->isAdmin()) {
            $where['ids'] = $allowedUids ?: [-1];
        }
        return $this->filterPersonnelListResult($service->adminList($where, 1, $limit));
    }
}
