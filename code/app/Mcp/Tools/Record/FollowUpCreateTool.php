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

use App\Constants\System\ViewSearchEnum;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\CreatesFollowUpRecord;

/**
 * 统一新增跟进：entity_type=customer|lead|opportunity.
 */
class FollowUpCreateTool extends BaseTool
{
    use CreatesFollowUpRecord {
        prepareWriteArguments as private prepareFollowUpWriteArguments;
    }

    /** @var array<string, array{link: string, label: string}> */
    private const ENTITY_MAP = [
        'customer'    => ['link' => ViewSearchEnum::VIEW_CUSTOMER, 'label' => '客户'],
        'lead'        => ['link' => ViewSearchEnum::VIEW_CLUE, 'label' => '线索'],
        'opportunity' => ['link' => ViewSearchEnum::VIEW_ODDS, 'label' => '商机'],
    ];

    /** @var string */
    private $activeLinkType = ViewSearchEnum::VIEW_CUSTOMER;

    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '新增跟进记录。须传 entity_type（customer/lead/opportunity）；用户用名称即可，禁止索要 ID；'
            . '把名称写入 entity_name。操作人固定为当前用户，写操作需要确认。';
    }

    public function getInputSchema(): array
    {
        $schema             = $this->followUpInputSchema('客户/线索/商机');
        $schema['required'] = ['entity_type', 'entity_name', 'content'];
        $schema['properties'] = array_merge([
            'entity_type' => [
                'type'        => 'string',
                'enum'        => ['customer', 'lead', 'opportunity'],
                'description' => '跟进对象类型：customer=客户，lead=线索，opportunity=商机',
            ],
        ], $schema['properties'] ?? []);

        return $schema;
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function prepareWriteArguments(array $arguments): array
    {
        $resolved = $this->resolveEntityType($arguments);
        if (isset($resolved['error'])) {
            return $resolved;
        }
        $this->activeLinkType = $resolved['link'];

        return $this->prepareFollowUpWriteArguments($arguments);
    }

    public function execute(array $arguments): array
    {
        $resolved = $this->resolveEntityType($arguments);
        if (isset($resolved['error'])) {
            return $resolved;
        }

        return $this->createFollowUp($arguments, $resolved['link']);
    }

    protected function followUpLinkType(): string
    {
        return $this->activeLinkType;
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array{link: string, label: string}|array{error: true, message: string, field?: string}
     */
    private function resolveEntityType(array $arguments): array
    {
        $type = strtolower(trim((string) ($arguments['entity_type'] ?? '')));
        if ($type === '' || ! isset(self::ENTITY_MAP[$type])) {
            return [
                'error'   => true,
                'message' => '请指定 entity_type：customer / lead / opportunity',
                'field'   => 'entity_type',
            ];
        }

        return self::ENTITY_MAP[$type];
    }
}
