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

namespace App\Mcp\Tools\Lead;

use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Service\Customer\LeadService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesPersonnelArguments;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;
use Illuminate\Support\Facades\Request;

class LeadListTool extends BaseTool
{
    use ResolvesPersonnelArguments;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取线索列表，支持通过动态搜索条件筛选';
    }

    public function getInputSchema(): array
    {
        $searchFields = $this->getSearchFields();

        $properties = [
            'limit' => ['type' => 'integer', 'description' => '返回线索数量限制，默认20', 'default' => 20],
            'offset' => ['type' => 'integer', 'description' => '偏移量，默认0', 'default' => 0],
            'view_search' => [
                'type' => 'integer',
                'description' => '视图类型：1=我负责的，2=我查看的，3=我关注的，4=未成交，5=已成交，6=急需跟进，7=线索公海，9=我协作的，10=我创建的，11=我参与的',
                'default' => 2,
            ],
            'scope_frame' => [
                'type' => 'string',
                'description' => '数据范围：all=全部，self=仅本人，dep=本部门(含无限下级)，sub=直属下级，team=本人+直属下级',
                'default' => 'all',
            ],
        ];

        $properties = array_merge($properties, $this->targetUserSchemaProperties());

        foreach ($searchFields as $field) {
            $fieldName = $field['field'];
            $properties[$fieldName] = $this->getDynamicSearchFieldSchema($field);
        }

        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    protected function getSearchFields(): array
    {
        return $this->getSearchFieldsByCustomType(ViewSearchEnum::VIEW_CLUE);
    }

    public function execute(array $arguments): array
    {
        $service = app(LeadService::class);

        $limit = min(100, max(1, (int) ($arguments['limit'] ?? 20)));
        $offset = max(0, (int) ($arguments['offset'] ?? 0));
        $page = (int) ($offset / $limit) + 1;

        Request::merge([
            'page' => $page,
            'limit' => $limit,
        ]);

        $where = [
            'types'       => ViewSearchEnum::VIEW_CLUE,
            'view_search' => (int) ($arguments['view_search'] ?? 2),
            'scope_frame' => $this->normalizeScopeFrame($arguments['scope_frame'] ?? 'all'),
        ];

        $searchFields = $this->getSearchFields();
        foreach ($searchFields as $field) {
            $fieldName = $field['field'];
            if (isset($arguments[$fieldName]) && $arguments[$fieldName] !== '' && $arguments[$fieldName] !== null) {
                $where[$fieldName] = $this->normalizeDynamicSearchValue($field, $arguments[$fieldName]);
            }
        }

        if ($error = $this->applyTargetUserToViewWhere($arguments, $where, ModuleEnum::CUSTOMER)) {
            return $error;
        }

        $result = $service->getListByType($where, $this->getUserDbId(), []);

        return $result['list'] ?? [];
    }
}
