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

use App\Constants\AttachEnum;
use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Model\Attach\SystemAttach;
use App\Http\Model\Customer\Customer;
use App\Http\Model\Customer\FollowUp;
use App\Http\Model\Customer\Lead;
use App\Http\Model\Customer\Opportunity;
use App\Http\Service\Customer\FollowUpService;

/**
 * 修改跟进正文、计划时间和附件，不改关联对象、跟进类型和负责人.
 */
trait UpdatesFollowUpRecord
{
    use ResolvesFollowUpLink;

    /**
     * @param string $entityLabel 用户可见对象称呼，如客户、线索、商机
     *
     * @return array<string, mixed>
     */
    protected function followUpUpdateInputSchema(string $entityLabel): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'           => [
                    'type'        => 'integer',
                    'description' => '系统定位结果。不要向用户提问。',
                ],
                'entity_name'  => [
                    'type'         => 'string',
                    'description'  => $entityLabel . '名称。只给名称时，修改该' . $entityLabel . '最近一条跟进。',
                    'x-ai-aliases' => [$entityLabel . '名', $entityLabel, '名称'],
                ],
                'match_content' => [
                    'type'         => 'string',
                    'description'  => '现有跟进正文里的关键词，用来区分是哪一条，不是修改后的新内容。',
                    'x-ai-aliases' => ['原内容', '原文'],
                ],
                'latest'       => [
                    'type'        => 'boolean',
                    'description' => '名称命中多条且未给原文关键词时，默认改最近一条。传 false 则改为列出候选、不写入。',
                ],
                'content'    => ['type' => 'string', 'description' => '修改后的新跟进内容；传入后替换原文'],
                'time'       => ['type' => 'string', 'description' => '计划跟进时间（仅计划跟进有效）'],
                'attach_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '附件，由系统传入；传入后整体替换原附件'],
            ],
        ];
    }

    /**
     * 按数据权限修改指定关联类型的跟进记录.
     *
     * @param array<string, mixed> $arguments 工具入参
     * @param string $linkType 允许修改的关联类型
     *
     * @return array<string, mixed>
     */
    protected function updateFollowUp(array $arguments, string $linkType): array
    {
        if (! $this->hasFollowUpMutableField($arguments)) {
            return ['error' => true, 'message' => '请提供要修改的跟进内容', 'field' => 'content'];
        }

        $resolved = $this->resolveFollowUpId($arguments, $linkType);
        if (isset($resolved['error'])) {
            return $resolved;
        }
        $id = $resolved['id'];

        $info = FollowUp::query()->find($id);
        if (! $info) {
            return ['error' => true, 'message' => '跟进记录不存在'];
        }
        if ((string) $info->link_type !== $linkType) {
            return ['error' => true, 'message' => '跟进记录类型不匹配'];
        }

        $ownerId = $this->linkedOwnerId($linkType, (int) $info->eid);
        if ($ownerId === null) {
            return ['error' => true, 'message' => '关联业务不存在'];
        }
        if (! $this->hasPermissionToUser($ownerId, ModuleEnum::CUSTOMER)) {
            return ['error' => true, 'message' => '无权修改该跟进记录'];
        }

        if (array_key_exists('content', $arguments) && trim((string) $arguments['content']) === '') {
            return ['error' => true, 'message' => '请填写跟进内容', 'field' => 'content'];
        }

        $attachIds = array_key_exists('attach_ids', $arguments)
            ? array_values(array_map('intval', (array) $arguments['attach_ids']))
            : SystemAttach::query()
                ->where('relation_id', $id)
                ->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_FOLLOW])
                ->pluck('id')
                ->map(static fn ($attachId): int => (int) $attachId)
                ->all();

        $updated = app(FollowUpService::class)->resourceUpdate($id, [
            'content'    => array_key_exists('content', $arguments) ? trim((string) $arguments['content']) : (string) $info->content,
            'types'      => (int) $info->types,
            'time'       => array_key_exists('time', $arguments) ? (string) $arguments['time'] : (string) ($info->time ?? ''),
            'attach_ids' => $attachIds,
            'follow_id'  => 0,
            'link_type'  => $linkType,
        ]);
        if ($updated === false || $updated === null || $updated === []) {
            return ['error' => true, 'message' => '跟进记录修改失败'];
        }

        return ['success' => true, 'id' => $id];
    }

    /**
     * 确认修改前先按名称定位跟进。名称或正文未唯一时返回选择项.
     *
     * @param array<string, mixed> $arguments 工具入参
     *
     * @return array<string, mixed> 成功时含 arguments；失败时含 error
     */
    public function prepareWriteArguments(array $arguments): array
    {
        if (! $this->hasFollowUpMutableField($arguments)) {
            return ['error' => true, 'message' => '请提供要修改的跟进内容', 'field' => 'content'];
        }

        $resolved = $this->resolveFollowUpId($arguments, $this->followUpLinkType());
        if (isset($resolved['error'])) {
            return $resolved;
        }

        $arguments['id'] = $resolved['id'];

        return ['arguments' => $arguments];
    }

    /**
     * 用业务名称或正文关键词定位跟进。只给名称时取该对象最近一条；多条且指定了原文则请用户按内容选择.
     *
     * @param array<string, mixed> $arguments
     *
     * @return array{id: int}|array<string, mixed>
     */
    private function resolveFollowUpId(array $arguments, string $linkType): array
    {
        $id = (int) ($arguments['id'] ?? 0);
        if ($id > 0) {
            return ['id' => $id];
        }

        $matchContent = $this->firstFilledString($arguments, ['match_content']);
        $entity       = $this->resolveFollowUpEntity($arguments, $linkType);
        if (isset($entity['error'])) {
            return $entity;
        }

        $query = FollowUp::query()->where('link_type', $linkType)->where('eid', $entity['id']);
        if ($matchContent !== '') {
            $query->where('content', 'like', '%' . $this->escapeLike($matchContent) . '%');
        }
        $takeLatest = $matchContent === '' && ! $this->latestDisabled($arguments);
        $rows       = $query->orderByDesc('id')->limit($takeLatest ? 1 : 6)->get(['id', 'content', 'created_at']);
        if ($rows->isEmpty()) {
            return ['error' => true, 'message' => '没有找到可修改的跟进记录，请换一个名称或原文关键词'];
        }
        if (! $takeLatest && $rows->count() > 1) {
            return [
                'error'           => true,
                'needs_selection' => true,
                'field_key'       => 'id',
                'field_label'     => '跟进记录',
                'message'         => '找到多条跟进，请按内容和时间选择',
                'candidates'      => $rows->map(fn (FollowUp $row): array => [
                    'id'   => (int) $row->id,
                    'name' => trim(mb_substr(trim((string) $row->content), 0, 40) . ' ' . (string) ($row->created_at ?? '')),
                ])->all(),
            ];
        }

        return ['id' => (int) $rows->first()->id];
    }

    /**
     * latest 显式为 false 时，多条跟进改为返回候选.
     *
     * @param array<string, mixed> $arguments
     */
    private function latestDisabled(array $arguments): bool
    {
        return array_key_exists('latest', $arguments) && ! $this->wantsLatestRecord($arguments);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function hasFollowUpMutableField(array $arguments): bool
    {
        foreach (['content', 'time', 'attach_ids'] as $field) {
            if (array_key_exists($field, $arguments)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 关联业务负责人；业务不存在时返回 null.
     */
    private function linkedOwnerId(string $linkType, int $eid): ?int
    {
        $query = match ($linkType) {
            ViewSearchEnum::VIEW_CUSTOMER, ViewSearchEnum::VIEW_CUSTOMER_SEAS => Customer::query(),
            ViewSearchEnum::VIEW_CLUE, ViewSearchEnum::VIEW_CLUE_SEAS         => Lead::query(),
            ViewSearchEnum::VIEW_ODDS                                          => Opportunity::query(),
            default                                                            => null,
        };
        if ($query === null || $eid <= 0) {
            return null;
        }

        $ownerId = $query->whereKey($eid)->value('uid');
        if ($ownerId === null) {
            return null;
        }

        return (int) $ownerId;
    }
}
