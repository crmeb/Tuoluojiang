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

namespace App\Mcp\Tools\Warehouse;

use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Service\Warehouse\WarehouseService;
use App\Mcp\Tools\Abstract\BaseTool;

/**
 * 获取当前用户有权限使用的仓库列表.
 *
 * 供订单/商机等写工具填写 outbound_warehouse_id 前解析仓库；
 * 权限由 WarehouseService::getAccessibleWarehouseList 按库管配置裁剪。
 */
class WarehouseListTool extends BaseTool
{
    public function getDescription(): string
    {
        return '获取当前用户有权限使用的仓库列表（按库管配置裁剪）。创建订单需要填写出库仓库 outbound_warehouse_id 时，先调用本工具取得候选仓库，再把选中仓库的 id 作为 outbound_warehouse_id 提交；返回为空表示当前用户未配置库管权限。';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'keyword' => [
                    'type'        => 'string',
                    'description' => '按仓库名称或编码模糊筛选，为空返回全部可用仓库',
                ],
                'action' => [
                    'type'        => 'string',
                    'description' => '只保留具备该操作权限的仓库：outbound=出库，inbound=入库，stock=库存查看，stock_setting=库存上下限设置，check=盘点，transfer=调拨，convert=转换；为空不按操作过滤。创建订单选出库仓库时传 outbound',
                    'enum'        => WarehouseEnum::PERMISSION_ACTIONS,
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $list = app(WarehouseService::class)->getAccessibleWarehouseList($this->getUserDbId());

        $keyword = trim((string) ($arguments['keyword'] ?? ''));
        $action  = trim((string) ($arguments['action'] ?? ''));

        $items = [];
        foreach ($list as $warehouse) {
            if (! is_array($warehouse)) {
                continue;
            }
            $name = (string) ($warehouse['name'] ?? '');
            $code = (string) ($warehouse['code'] ?? '');
            if ($keyword !== ''
                && mb_stripos($name, $keyword) === false
                && mb_stripos($code, $keyword) === false) {
                continue;
            }

            // 权限键为 can_<action>（见 WarehouseService::formatAccessiblePermissions）.
            $permissions = (array) ($warehouse['permissions'] ?? []);
            if ($action !== '' && empty($permissions['can_' . $action])) {
                continue;
            }

            $allowed = [];
            foreach (WarehouseEnum::PERMISSION_ACTIONS as $permissionAction) {
                if (! empty($permissions['can_' . $permissionAction])) {
                    $allowed[] = $permissionAction;
                }
            }

            $items[] = [
                'id'          => (int) ($warehouse['id'] ?? 0),
                'name'        => $name,
                'code'        => $code,
                'address'     => (string) ($warehouse['address'] ?? ''),
                'permissions' => $allowed,
            ];
        }

        return [
            'total' => count($items),
            'list'  => $items,
            'hint'  => $items === []
                ? '当前用户没有可用仓库，请联系管理员配置库管权限；不要臆造仓库 ID。'
                : '将选中仓库的 id 作为 outbound_warehouse_id 提交给 order_create。',
        ];
    }
}
