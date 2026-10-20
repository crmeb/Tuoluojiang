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

namespace App\Mcp\Tools\Record;

use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Service\Customer\RecordService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesFollowUpLink;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

/**
 * 客户/线索/商机跟进与动态记录列表.
 *
 * 约束：link_type 使用库内字符串（customer/clue/odds），禁止传整数枚举；
 * 关键词落在 reason 字段（表无 content 列）；可用 entity_name 定位业务对象.
 */
class RecordListTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesFollowUpLink;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取客户、线索或商机的跟进/动态记录列表。查某条线索历史跟进时传 link_type=clue，并用 entity_name 或 eid 定位；keyword 仅用于 reason 模糊搜索，不要把业务名称塞进 keyword。';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'customer_id' => ['type' => 'integer', 'description' => '客户ID（等价 eid，link_type=customer）'],
                'lead_id' => ['type' => 'integer', 'description' => '线索ID（等价 eid，link_type=clue）'],
                'opportunity_id' => ['type' => 'integer', 'description' => '商机ID（等价 eid，link_type=odds）'],
                'eid' => ['type' => 'integer', 'description' => '关联业务ID（客户/线索/商机）'],
                'entity_name' => ['type' => 'string', 'description' => '客户/线索/商机名称，用于定位 eid'],
                'types' => ['type' => 'integer', 'description' => '跟进类型'],
                'link_type' => [
                    'type' => 'string',
                    'description' => '关联类型：customer|clue|odds（可用 lead 作 clue 别名），默认 customer',
                ],
                'keyword' => ['type' => 'string', 'description' => '按 reason 模糊搜索，勿传业务名称'],
                'start_date' => ['type' => 'string', 'description' => '跟进开始日期'],
                'end_date' => ['type' => 'string', 'description' => '跟进结束日期'],
                'page' => ['type' => 'integer', 'description' => '页码', 'default' => 1],
                'limit' => ['type' => 'integer', 'description' => '每页数量', 'default' => 20],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $this->applyPage($arguments);

        if (! isset($arguments['link_type']) || trim((string) $arguments['link_type']) === '') {
            if (! empty($arguments['lead_id'])) {
                $arguments['link_type'] = ViewSearchEnum::VIEW_CLUE;
            } elseif (! empty($arguments['opportunity_id'])) {
                $arguments['link_type'] = ViewSearchEnum::VIEW_ODDS;
            } elseif (! empty($arguments['customer_id'])) {
                $arguments['link_type'] = ViewSearchEnum::VIEW_CUSTOMER;
            }
        }
        $linkType = $this->normalizeRecordLinkType((string) ($arguments['link_type'] ?? ViewSearchEnum::VIEW_CUSTOMER));
        $resolved = $this->resolveRecordEid($arguments, $linkType);
        if (isset($resolved['error'])) {
            return $resolved;
        }
        $eid = (int) ($resolved['eid'] ?? 0);

        $where = $this->onlyFilled($arguments, [
            'types' => 'type',
            'keyword' => 'reason',
        ]);
        $where['link_type'] = $linkType;
        if ($eid > 0) {
            $where['eid'] = $eid;
        }

        if ($date = $this->dateRange($arguments)) {
            $where['time'] = $date;
        }

        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::CUSTOMER);
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        if (! empty($authorized['has_filter'])) {
            $where['creator_uid'] = $authorized['user_ids'];
        }

        return app(RecordService::class)->getList($where);
    }

    /**
     * 归一化关联类型别名到库内字符串.
     */
    private function normalizeRecordLinkType(string $linkType): string
    {
        $normalized = strtolower(trim($linkType));

        return match ($normalized) {
            '', '1', (string) ViewSearchEnum::VIEW_CUSTOMER, 'customer_seas' => ViewSearchEnum::VIEW_CUSTOMER,
            'lead', 'clue', '4', 'clue_seas' => ViewSearchEnum::VIEW_CLUE,
            'opportunity', 'odds', '5' => ViewSearchEnum::VIEW_ODDS,
            default => $normalized,
        };
    }

    /**
     * 从 id / 名称参数解析 eid；名称多命中时返回 needs_selection.
     * 无 eid 且 keyword 像业务名时，消耗 keyword 作为 entity_name（不再当 reason）.
     *
     * @param array<string, mixed> $arguments
     *
     * @return array{eid: int}|array<string, mixed>
     */
    private function resolveRecordEid(array &$arguments, string $linkType): array
    {
        $eid = (int) ($arguments['eid'] ?? 0);
        if ($eid <= 0) {
            $eid = (int) ($arguments['customer_id'] ?? $arguments['lead_id'] ?? $arguments['opportunity_id'] ?? 0);
        }

        $entityName = trim((string) ($arguments['entity_name'] ?? ''));
        // 模型常把业务名误塞进 keyword；无 eid 时当作名称定位并移出 reason 条件.
        if ($entityName === '' && $eid <= 0) {
            $keyword = trim((string) ($arguments['keyword'] ?? ''));
            if ($keyword !== '') {
                $entityName             = $keyword;
                $arguments['entity_name'] = $entityName;
                unset($arguments['keyword']);
            }
        }

        if ($eid <= 0 && $entityName === '') {
            return ['eid' => 0];
        }

        $resolved = $this->resolveFollowUpEntity(
            array_merge($arguments, [
                'eid'         => $eid > 0 ? $eid : null,
                'entity_name' => $entityName,
            ]),
            $linkType
        );
        if (! empty($resolved['error'])) {
            return $resolved;
        }

        return ['eid' => (int) ($resolved['id'] ?? 0)];
    }
}
