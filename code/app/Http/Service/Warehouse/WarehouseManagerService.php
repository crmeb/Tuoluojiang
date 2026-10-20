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
use App\Http\Service\Admin\AdminService;
use App\Http\Service\BaseService;

/**
 * 库管服务，负责库管人员与可操作仓库维护.
 */
class WarehouseManagerService extends BaseService
{
    /**
     * 注入数据访问对象.
     */
    public function __construct(WarehouseManagerDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取库管列表.
     */
    public function getManagerList(array $where): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, ['*'], $page, $limit, ['sort' => 'desc', 'id' => 'desc'], [
            'users',
            'warehouseInfos',
        ]);
        $list->each(fn ($manager) => $this->appendWarehouseNames($manager));
        return $this->listData($list, $this->dao->count($where));
    }

    /**
     * 保存库管基础信息和关联关系.
     */
    public function saveManager(array $data, int $uid, int $id = 0): int
    {
        if (! $data['name']) {
            throw $this->exception('请填写库管名称');
        }
        $userIds = $this->normalizeUserIds((array) ($data['user_ids'] ?? []));
        $this->assertUsersNotAssigned($userIds, $id);
        $managerType = (int) ($data['manager_type'] ?? WarehouseEnum::MANAGER_NORMAL);
        $warehousePermissions = $this->normalizeWarehousePermissions($data);
        return $this->transaction(function () use ($data, $uid, $id, $userIds, $managerType, $warehousePermissions) {
            $save = [
                'name'           => $data['name'],
                'manager_type'   => $managerType,
                'can_view_other' => $managerType === WarehouseEnum::MANAGER_SUPER ? 1 : (int) ($data['can_view_other'] ?? 0),
                'status'         => (int) ($data['status'] ?? WarehouseEnum::STATUS_ENABLE),
                'sort'           => (int) ($data['sort'] ?? 0),
                'remark'         => $data['remark'] ?? '',
            ];
            if ($id) {
                $this->dao->update($id, $save);
            } else {
                $save['creator'] = $uid;
                $id              = (int) $this->dao->create($save)->id;
            }
            $this->syncUsers($id, $userIds);
            $this->syncWarehouses($id, $warehousePermissions);
            return $id;
        });
    }

    /**
     * 获取单据或业务详情.
     */
    public function detail(int $id): array
    {
        $manager = $this->dao->get($id, with: ['users', 'warehouses', 'warehouseInfos']);
        if ($manager) {
            $this->appendWarehouseNames($manager);
        }
        $info = $manager?->toArray();
        if (! $info) {
            throw $this->exception('库管不存在');
        }
        $info['user_ids']      = array_column($info['users'] ?? [], 'id');
        $info['warehouse_ids'] = array_column($info['warehouses'] ?? [], 'warehouse_id');
        $info['warehouse_permissions'] = array_map(function ($warehouse) {
            return $this->formatWarehousePermission($warehouse);
        }, $info['warehouses'] ?? []);
        return $info;
    }

    /**
     * 同步库管关联员工.
     */
    private function syncUsers(int $managerId, array $userIds): void
    {
        $dao = app(WarehouseManagerUserDao::class);
        $dao->delete(['manager_id' => $managerId]);
        $rows = [];
        foreach ($this->normalizeUserIds($userIds) as $userId) {
            $rows[] = ['manager_id' => $managerId, 'user_id' => (int) $userId, 'created_at' => now(), 'updated_at' => now()];
        }
        if ($rows) {
            $dao->getModel(false)->insert($rows);
        }
    }

    /**
     * 追加仓库名称数组.
     */
    private function appendWarehouseNames($manager): void
    {
        $manager->setAttribute('warehouse_names', $manager->warehouseInfos?->pluck('name')->filter()->values()->all() ?? []);
        $manager->unsetRelation('warehouseInfos');
    }

    /**
     * 校验员工未被其他库管角色占用.
     */
    private function assertUsersNotAssigned(array $userIds, int $managerId = 0): void
    {
        if (! $userIds) {
            return;
        }
        $query = app(WarehouseManagerUserDao::class)->getModel(false)
            ->whereIn('user_id', $userIds)
            ->whereIn('manager_id', $this->dao->getModel(false)->select('id'));
        if ($managerId) {
            $query->where('manager_id', '<>', $managerId);
        }
        $assigned = $query->first();
        if ($assigned) {
            $name = app(AdminService::class)->value((int) $assigned->user_id, 'name');
            throw $this->exception(($name ?: '该') . '员工已设置过库管角色');
        }
    }

    /**
     * 标准化库管员工 ID.
     */
    private function normalizeUserIds(array $userIds): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $userIds))));
    }

    /**
     * 同步库管关联仓库.
     */
    private function syncWarehouses(int $managerId, array $permissions): void
    {
        $dao = app(WarehouseManagerWarehouseDao::class);
        $dao->delete(['manager_id' => $managerId]);
        $rows = [];
        foreach ($permissions as $permission) {
            $rows[] = array_merge($permission, [
                'manager_id' => $managerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if ($rows) {
            $dao->getModel(false)->insert($rows);
        }
    }

    /**
     * 标准化前端提交的仓库动作权限.
     */
    private function normalizeWarehousePermissions(array $data): array
    {
        $items = (array) ($data['warehouse_permissions'] ?? []);
        if (! $items && ! empty($data['warehouse_ids'])) {
            $items = array_map(fn ($warehouseId) => ['warehouse_id' => $warehouseId], (array) $data['warehouse_ids']);
        }

        $permissions = [];
        foreach ($items as $item) {
            $warehouseId = is_array($item) ? (int) ($item['warehouse_id'] ?? $item['id'] ?? 0) : (int) $item;
            if (! $warehouseId) {
                continue;
            }
            if (! isset($permissions[$warehouseId])) {
                $permissions[$warehouseId] = $this->defaultWarehousePermission($warehouseId);
            }
            if (is_array($item)) {
                foreach (WarehouseEnum::PERMISSION_ACTIONS as $action) {
                    $column = 'can_' . $action;
                    if (array_key_exists($column, $item)) {
                        $permissions[$warehouseId][$column] = (int) (bool) $item[$column];
                    } elseif (isset($item['permissions']) && is_array($item['permissions'])) {
                        $permissions[$warehouseId][$column] = in_array($action, $item['permissions'], true) ? 1 : 0;
                    }
                }
            }
        }

        return array_values($permissions);
    }

    /**
     * 生成仓库默认动作权限.
     */
    private function defaultWarehousePermission(int $warehouseId): array
    {
        $permission = ['warehouse_id' => $warehouseId];
        foreach (WarehouseEnum::PERMISSION_ACTIONS as $action) {
            $permission['can_' . $action] = $action === WarehouseEnum::ACTION_STOCK_SETTING ? 0 : 1;
        }
        return $permission;
    }

    /**
     * 格式化详情返回的仓库动作权限.
     */
    private function formatWarehousePermission(array $warehouse): array
    {
        $permission = [
            'warehouse_id' => (int) ($warehouse['warehouse_id'] ?? 0),
            'permissions'  => [],
        ];
        foreach (WarehouseEnum::PERMISSION_ACTIONS as $action) {
            $column = 'can_' . $action;
            $permission[$column] = (int) ($warehouse[$column] ?? 0);
            if ($permission[$column] === 1) {
                $permission['permissions'][] = $action;
            }
        }
        return $permission;
    }
}
