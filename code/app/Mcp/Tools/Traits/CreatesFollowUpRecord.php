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

use App\Http\Service\Customer\FollowUpService;

/**
 * 按名称新增跟进记录。使用方需实现 followUpLinkType().
 */
trait CreatesFollowUpRecord
{
    use ResolvesFollowUpLink;

    /**
     * 确认写入前把名称解析成内部关联 ID。名称未唯一时返回选择项，不向用户索要 ID.
     *
     * @param array<string, mixed> $arguments 工具入参
     *
     * @return array<string, mixed> 成功时含 arguments；失败时含 error
     */
    public function prepareWriteArguments(array $arguments): array
    {
        $content = trim((string) ($arguments['content'] ?? ''));
        if ($content === '') {
            return ['error' => true, 'message' => '请填写跟进内容', 'field' => 'content'];
        }

        $resolved = $this->resolveFollowUpEntity($arguments, $this->followUpLinkType());
        if (isset($resolved['error'])) {
            return $resolved;
        }

        $arguments['eid'] = $resolved['id'];
        if (trim((string) ($arguments['entity_name'] ?? '')) === '') {
            $arguments['entity_name'] = $resolved['name'];
        }

        return ['arguments' => $arguments];
    }

    /**
     * 为当前关联类型新增跟进.
     *
     * @param array<string, mixed> $arguments 工具入参
     * @param string $linkType 关联类型
     *
     * @return array<string, mixed>
     */
    protected function createFollowUp(array $arguments, string $linkType): array
    {
        $content = trim((string) ($arguments['content'] ?? ''));
        if ($content === '') {
            return ['error' => true, 'message' => '请填写跟进内容', 'field' => 'content'];
        }

        $resolved = $this->resolveFollowUpEntity($arguments, $linkType);
        if (isset($resolved['error'])) {
            return $resolved;
        }

        $data = [
            'eid'        => $resolved['id'],
            'content'    => $content,
            'types'      => (int) ($arguments['types'] ?? 0),
            'time'       => (string) ($arguments['time'] ?? ''),
            'attach_ids' => (array) ($arguments['attach_ids'] ?? []),
            'follow_id'  => (int) ($arguments['follow_id'] ?? 0),
            'link_type'  => $linkType,
        ];

        $res = app(FollowUpService::class)->resourceSave($data);
        $id  = is_object($res) ? (int) ($res->id ?? 0) : (int) $res;
        if ($id <= 0) {
            return ['error' => true, 'message' => '跟进记录创建失败'];
        }

        return [
            'id'          => $id,
            'success'     => true,
            'entity_name' => $resolved['name'],
        ];
    }

    /**
     * 跟进写入参数。必填的是名称，不是业务 ID.
     *
     * @param string $entityLabel 用户可见对象称呼，如客户、线索、商机
     *
     * @return array<string, mixed>
     */
    protected function followUpInputSchema(string $entityLabel): array
    {
        return [
            'type'       => 'object',
            'required'   => ['entity_name', 'content'],
            'properties' => [
                'entity_name' => [
                    'type'         => 'string',
                    'description'  => $entityLabel . '名称。直接填写名称。',
                    'x-ai-aliases' => [$entityLabel . '名', $entityLabel, '名称'],
                ],
                'eid' => [
                    'type'        => 'integer',
                    'description' => '系统定位结果。名称已唯一确定时由系统写入，不要向用户提问。',
                ],
                'content'    => ['type' => 'string', 'description' => '跟进内容'],
                'types'      => ['type' => 'integer', 'description' => '跟进类型：0=普通跟进，1=计划跟进（需传 time）', 'default' => 0],
                'time'       => ['type' => 'string', 'description' => '计划跟进时间，格式 Y-m-d H:i:s。计划跟进时填写'],
                'attach_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '附件，由系统传入，不要向用户索要附件编号'],
                'follow_id'  => ['type' => 'integer', 'description' => '待办跟进，由系统传入，不要向用户提问'],
            ],
        ];
    }
}
