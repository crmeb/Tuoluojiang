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

namespace App\Http\Service\Warehouse;

use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Dao\Warehouse\WarehouseManagerDao;
use App\Http\Dao\Warehouse\WarehouseManagerUserDao;
use App\Http\Dao\Warehouse\WarehouseManagerWarehouseDao;
use App\Http\Service\BaseService;

/**
 * 仓库权限服务，负责库管仓库范围计算与操作校验.
 */
class WarehousePermissionService extends BaseService
{
    /**
     * 注入数据访问对象.
     */
    public function __construct(WarehouseManagerDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取员工对应的库管配置.
     */
    public function getManagerByUser(int $uid): ?array
    {
        if (! $uid) {
            return null;
        }
        $managerIds = app(WarehouseManagerUserDao::class)->column(['user_id' => $uid], 'manager_id');
        if (! $managerIds) {
            return null;
        }
        $managers = $this->dao->select(['id' => $managerIds, 'status' => WarehouseEnum::STATUS_ENABLE])?->toArray();
        if (! $managers) {
            return null;
        }
        $managerIds    = array_column($managers, 'id');
        $warehouseRows = app(WarehouseManagerWarehouseDao::class)->select(['manager_id' => $managerIds])?->toArray() ?: [];

        return [
            'id'             => $managerIds[0] ?? 0,
            'manager_type'   => collect($managers)->contains(fn ($manager) => (int) $manager['manager_type'] === WarehouseEnum::MANAGER_SUPER) ? WarehouseEnum::MANAGER_SUPER : WarehouseEnum::MANAGER_NORMAL,
            'can_view_other' => collect($managers)->contains(fn ($manager) => (int) $manager['can_view_other'] === 1) ? 1 : 0,
            'warehouse_ids'  => array_values(array_unique(array_map('intval', array_column($warehouseRows, 'warehouse_id')))),
            'permissions'    => $this->mergeWarehousePermissions($warehouseRows),
        ];
    }

    /**
     * 获取员工可访问的仓库 ID 范围.
     */
    public function accessibleWarehouseIds(int $uid, bool $forStockView = false, string $action = ''): array
    {
        $manager = $this->getManagerByUser($uid);
        if (! $manager) {
            return [-1];
        }
        if ((int) $manager['manager_type'] === WarehouseEnum::MANAGER_SUPER) {
            return [];
        }
        if ($forStockView && (int) $manager['can_view_other'] === 1) {
            return [];
        }
        if ($action) {
            $warehouseIds = [];
            foreach ((array) ($manager['permissions'] ?? []) as $warehouseId => $permissions) {
                if (! empty($permissions[$action])) {
                    $warehouseIds[] = (int) $warehouseId;
                }
            }
            return $warehouseIds ?: [-1];
        }
        $warehouseIds = array_map('intval', $manager['warehouse_ids'] ?? []);
        return $warehouseIds ?: [-1];
    }

    /**
     * 断言员工拥有指定仓库操作权限.
     */
    public function assertCanUseWarehouse(int $warehouseId, int $uid, string $action = '', bool $allowViewOtherStock = true): void
    {
        $manager = $this->getManagerByUser($uid);
        if (! $manager) {
            throw $this->exception('无权操作该仓库');
        }
        if ((int) $manager['manager_type'] === WarehouseEnum::MANAGER_SUPER) {
            return;
        }
        if ($allowViewOtherStock && $action === WarehouseEnum::ACTION_STOCK && (int) $manager['can_view_other'] === 1) {
            return;
        }
        $warehouseId = (int) $warehouseId;
        $permissions = (array) ($manager['permissions'][$warehouseId] ?? []);
        if (! $permissions || ($action && empty($permissions[$action]))) {
            $message = match ($action) {
                WarehouseEnum::ACTION_STOCK         => '无权查看该仓库库存',
                WarehouseEnum::ACTION_STOCK_SETTING => '无权设置该仓库库存',
                default                             => '无权操作该仓库',
            };
            throw $this->exception($message);
        }
    }

    /**
     * 将仓库权限范围追加到查询条件.
     */
    public function applyWarehouseScope(array $where, int $uid, bool $forStockView = false, string $action = ''): array
    {
        $ids = $this->accessibleWarehouseIds($uid, $forStockView, $action);
        if (! $ids) {
            return $where;
        }
        if (isset($where['warehouse_id']) && $where['warehouse_id'] !== '') {
            $where['warehouse_id'] = array_values(array_intersect((array) $where['warehouse_id'], $ids));
            return $where;
        }
        if (isset($where['warehouse_ids']) && $where['warehouse_ids'] !== '') {
            $where['warehouse_ids'] = array_values(array_intersect((array) $where['warehouse_ids'], $ids));
            return $where;
        }
        $where['warehouse_ids'] = $ids;
        return $where;
    }

    /**
     * 合并员工多个库管角色下的仓库动作权限.
     */
    private function mergeWarehousePermissions(array $rows): array
    {
        $permissions = [];
        foreach ($rows as $row) {
            $warehouseId = (int) ($row['warehouse_id'] ?? 0);
            if (! $warehouseId) {
                continue;
            }
            foreach (WarehouseEnum::PERMISSION_ACTIONS as $action) {
                $permissions[$warehouseId][$action] = (int) (($permissions[$warehouseId][$action] ?? 0) || (int) ($row['can_' . $action] ?? 0));
            }
        }
        return $permissions;
    }
}
