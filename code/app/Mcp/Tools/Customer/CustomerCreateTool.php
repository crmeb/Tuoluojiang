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

namespace App\Mcp\Tools\Customer;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\LiaisonService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesCustomFormFields;

class CustomerCreateTool extends BaseTool
{
    use ResolvesCustomFormFields;

    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '新增客户及默认联系人，客户与联系人字段均根据租户自定义表单动态生成，创建人固定为当前 MCP 用户';
    }

    public function getInputSchema(): array
    {
        $customerSchema = $this->buildCreateInputSchema(CustomEnum::CUSTOMER, [
            'link_id' => ['type' => 'integer', 'description' => '关联 ID'],
        ]);

        $liaisonProperties = [];
        foreach ($this->getCreateFieldsByCustomType(CustomEnum::LIAISON, ['eid']) as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $liaisonProperties[$key] = $this->getDynamicCreateFieldSchema($field);
        }

        $customerSchema['properties'] = array_merge($customerSchema['properties'] ?? [], $liaisonProperties);

        return $customerSchema;
    }

    public function execute(array $arguments): array
    {
        $customerData = $this->buildCreatePayload($arguments, CustomEnum::CUSTOMER);
        $liaisonData  = $this->buildCreatePayload($arguments, CustomEnum::LIAISON, ['eid']);
        $linkId       = (int) ($arguments['link_id'] ?? 0);

        $res = app(CustomerService::class)->saveCustomer(
            $customerData,
            $this->getUserDbId(),
            ViewSearchEnum::VIEW_CUSTOMER,
            $linkId
        );
        app(LiaisonService::class)->saveLiaison($liaisonData, (int) $res->id, $this->getUserDbId());

        return ['id' => (int) $res->id, 'success' => true];
    }
}
