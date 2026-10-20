<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 */

namespace App\Mcp\Tools\Contact;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\ModuleEnum;
use App\Http\Model\Customer\Customer;
use App\Http\Service\Customer\LiaisonService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesCustomFormFields;

/** 新增客户联系人 MCP 写工具，字段跟随租户联系人自定义表单。 */
class ContactCreateTool extends BaseTool
{
    use ResolvesCustomFormFields;

    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '为已有客户新增联系人，联系人字段根据租户自定义表单动态生成，创建人固定为当前 MCP 用户；必须提供客户 ID。';
    }

    public function getInputSchema(): array
    {
        $schema = $this->buildCreateInputSchema(
            CustomEnum::LIAISON,
            ['eid' => ['type' => 'integer', 'description' => '所属客户 ID']],
            ['eid'],
            ['eid']
        );

        // 联系人姓名与电话是语义必需字段：动态表单声明了才加入 required，
        // 缺参由 missingWriteFields 统一拦截并推送补录卡片，避免创建出空联系人.
        $required = (array) ($schema['required'] ?? []);
        foreach (['liaison_name', 'liaison_tel'] as $key) {
            if (isset($schema['properties'][$key]) && ! in_array($key, $required, true)) {
                $required[] = $key;
            }
        }
        if ($required !== []) {
            $schema['required'] = array_values($required);
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array
    {
        $eid = (int) ($arguments['eid'] ?? 0);
        if ($eid <= 0) {
            return ['error' => true, 'field' => 'eid', 'message' => '缺少所属客户 ID'];
        }

        $customer = Customer::query()->find($eid);
        if (! $customer) {
            return ['error' => true, 'field' => 'eid', 'message' => '客户不存在'];
        }
        if (! $this->hasPermissionToUser((int) $customer->uid, ModuleEnum::CUSTOMER)) {
            return ['error' => true, 'message' => '无权为该客户新增联系人'];
        }

        $data = $this->buildCreatePayload($arguments, CustomEnum::LIAISON, ['eid']);
        $contact = app(LiaisonService::class)->saveLiaison($data, $eid, $this->getUserDbId());

        return [
            'success' => true,
            'id'      => (int) $contact->id,
            'eid'     => $eid,
            'name'    => (string) ($data['liaison_name'] ?? ''),
        ];
    }
}
