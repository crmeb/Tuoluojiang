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

use App\Constants\ApproveEnum;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesApproveTemplate;

class ApproveTypeListTool extends BaseTool
{
    use ResolvesApproveTemplate;

    public function getDescription(): string
    {
        return '列出当前用户可发起的审批模板：人事（请假/加班/外出/出差/补卡）与自定义审批（如员工报销/餐费报销，types=0）。'
            . '员工报销请传 name=报销（或「出差报销」），不要改走客户订单支出。'
            . '客户回款/续费/开票/作废/订单支出/合同签约禁止用本工具，必须改调 customer_biz_form_get。'
            . '人事务必传 types（1请假/2补卡/3加班/4外出/5出差）；自定义报销用 name，types 可传 0。'
            . '同一筛选多模板时需选定 approve_id。';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'types' => [
                    'type'        => 'integer',
                    'description' => '按业务类型筛选。1=请假，2=补卡，3=加班，4=外出，5=出差，0=自定义（含报销）。人事场景强烈建议传 types；报销用 name=报销 即可',
                    'enum'        => [
                        0,
                        ApproveEnum::PERSONNEL_HOLIDAY,
                        ApproveEnum::PERSONNEL_SIGN,
                        ApproveEnum::PERSONNEL_OVERTIME,
                        ApproveEnum::PERSONNEL_OUT,
                        ApproveEnum::PERSONNEL_TRIP,
                    ],
                ],
                'name' => [
                    'type'        => 'string',
                    'description' => '按名称关键词筛选，如「请假」「报销」；客户业务名称（回款/开票/订单支出）请改用 customer_biz_form_get',
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $typeFilter = array_key_exists('types', $arguments) ? (int) $arguments['types'] : null;
        $nameFilter = trim((string) ($arguments['name'] ?? ''));

        // 客户业务关键词误走本工具时，直接纠偏，避免空列表后再拉全量逼手选.
        if ($this->looksLikeCustomerApproveIntent($nameFilter)) {
            return [
                'list'            => [],
                'total'           => 0,
                'groups'          => [],
                'duplicate_types' => [],
                'error'           => true,
                'message'         => '客户域业务审批请使用 customer_biz_form_get 等业务表单工具，不要用 approve_type_list',
                'hint'            => '流程：customer_biz_form_get(biz=…) → 对应写工具；员工报销仍用本工具 name=报销',
            ];
        }

        $items  = $this->listUserApproveTemplates($this->getUserDbId(), $typeFilter, $nameFilter);
        $groups = $this->groupApproveTemplatesByTypes($items);

        $hint = '人事传 types；报销用 name=报销；客户回款/开票/订单支出请改用 customer_biz_form_get';
        if ($typeFilter === null && $nameFilter === '') {
            $hint = '未传 types/name：结果含人事与自定义模板。人事请按 types 重筛；报销请 name=报销；'
                . '客户回款/开票/续费/订单支出/合同请改调 customer_biz_form_get。';
        } elseif ($groups['duplicate_types'] !== []) {
            $hint = '以下业务类型存在多个审批模板（duplicate_types），发起前必须让用户选择 approve_id：'
                . implode('、', array_map(fn (int $types): string => $this->getApproveTypesLabel($types), $groups['duplicate_types']));
        } elseif ($typeFilter === ApproveEnum::PERSONNEL_HOLIDAY) {
            $hint = '已选定请假模板后，再调 approve_holiday_type_list → approve_form_get → approve_create';
        } elseif ($typeFilter === 0 || ($nameFilter !== '' && str_contains($nameFilter, '报销'))) {
            $hint = '已筛选报销/自定义模板，选定 approve_id 后：approve_form_get → approve_process_get → approve_create';
        }

        return [
            'list'            => $items,
            'total'           => count($items),
            'groups'          => $groups['groups'],
            'duplicate_types' => $groups['duplicate_types'],
            'unfiltered'      => $typeFilter === null && $nameFilter === '',
            'hint'            => $hint,
        ];
    }

    private function looksLikeCustomerApproveIntent(string $nameFilter): bool
    {
        if ($nameFilter === '') {
            return false;
        }

        // 员工报销走本工具；仅拦截客户域订单业务关键词（「支出」需带订单/客户口径，避免误伤「报销」）.
        return (bool) preg_match('/回款|续费|开票|发票|冲红|合同签约|订单回款|订单续费|订单支出|客户支出/u', $nameFilter);
    }
}
