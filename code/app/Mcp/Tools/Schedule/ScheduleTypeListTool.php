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

namespace App\Mcp\Tools\Schedule;

use App\Constants\ScheduleEnum;
use App\Http\Service\Schedule\ScheduleService;
use App\Mcp\Tools\Abstract\BaseTool;

/**
 * 日程类型列表 MCP 工具，供创建日程前选择 cid.
 *
 * 只暴露用户可手动选择的类型（is_public != 0，与管理端新建日程下拉一致）；
 * 客户跟进/续费/回款/汇报待办等系统业务类型由业务流程联动生成，缺少关联记录与提醒数据时前端详情会报错.
 */
class ScheduleTypeListTool extends BaseTool
{
    /**
     * 用户可手动创建日程的类型 ID.
     *
     * @param int $uid 用户 ID
     *
     * @return array<int, int>
     */
    public static function creatableTypeIds(int $uid): array
    {
        $ids = [];
        foreach ((array) app(ScheduleService::class)->typeList($uid, ['id', 'is_public']) as $item) {
            if (is_array($item) && (int) ($item['is_public'] ?? 0) !== 0 && (int) ($item['id'] ?? 0) > 0) {
                $ids[] = (int) $item['id'];
            }
        }

        return $ids;
    }

    /**
     * 解析创建日程的 cid：缺省或不可手动创建的类型一律退回个人提醒.
     *
     * @param int $uid 用户 ID
     * @param int $cid 模型传入的类型 ID
     *
     * @return int 可安全写入的类型 ID
     */
    public static function resolveCreatableCid(int $uid, int $cid): int
    {
        if ($cid <= 0 || ! in_array($cid, self::creatableTypeIds($uid), true)) {
            return ScheduleEnum::TYPE_PERSONAL;
        }

        return $cid;
    }
    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return '获取当前用户可用的日程类型列表（含个人提醒等），创建日程前可用此工具确认 cid';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => new \stdClass(),
        ];
    }

    /**
     * 返回当前用户可见的日程类型.
     *
     * @param array<string, mixed> $arguments 无必填参数
     *
     * @return array{list: array<int, array{id: int, name: string, color: string, info: string}>, total: int, hint: string}
     */
    public function execute(array $arguments): array
    {
        $list = app(ScheduleService::class)->typeList($this->getUserDbId(), ['id', 'name', 'color', 'info', 'is_public']);
        $items = [];
        foreach ((array) $list as $item) {
            if (! is_array($item) || (int) ($item['is_public'] ?? 0) === 0) {
                continue;
            }
            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $items[] = [
                'id'    => $id,
                'name'  => (string) ($item['name'] ?? ''),
                'color' => (string) ($item['color'] ?? ''),
                'info'  => (string) ($item['info'] ?? ''),
            ];
        }

        return [
            'list'  => $items,
            'total' => count($items),
            'hint'  => '创建日程时 cid 传 list[].id；未指定时默认个人提醒（id=' . ScheduleEnum::TYPE_PERSONAL . '）',
        ];
    }
}
