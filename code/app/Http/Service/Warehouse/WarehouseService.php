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
use App\Http\Dao\Warehouse\WarehouseDao;
use App\Http\Dao\Warehouse\WarehouseSkuStockDao;
use App\Http\Service\BaseService;

/**
 * 仓库基础资料服务，负责仓库维护与启用状态校验.
 */
class WarehouseService extends BaseService
{
    /**
     * 注入数据访问对象.
     */
    public function __construct(WarehouseDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取仓库列表.
     */
    public function getWarehouseList(array $where): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, ['*'], $page, $limit, ['sort', 'id']);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 获取登录人员可查看的仓库列表（含权限）.
     */
    public function getAccessibleWarehouseList(int $uid): array
    {
        $permissionService = app(WarehousePermissionService::class);
        $manager           = $permissionService->getManagerByUser($uid);

        // 用户无库管配置，返回空列表
        if (! $manager) {
            return [];
        }

        // 超级库管返回所有启用仓库，附带全部权限
        if ((int) $manager['manager_type'] === WarehouseEnum::MANAGER_SUPER) {
            $list = $this->dao->getList(['status' => WarehouseEnum::STATUS_ENABLE], ['id', 'name', 'code', 'address', 'status'],sort: ['sort','id']);
            return $list->map(function ($warehouse) {
                $data                = $warehouse->toArray();
                $data['permissions'] = $this->formatAccessiblePermissions([], 1);
                return $data;
            })->toArray();
        }

        // 普通库管开启查看其他仓库库存时，已配置仓库保留原权限，其它仓库只给库存查看权限
        if ((int) ($manager['can_view_other'] ?? 0) === 1) {
            $list                   = $this->dao->getList(['status' => WarehouseEnum::STATUS_ENABLE], ['id', 'name', 'code', 'address', 'status'],sort: ['sort','id']);
            $permissions            = $manager['permissions'] ?? [];
            $configuredWarehouseIds = array_flip(array_map('intval', $manager['warehouse_ids'] ?? []));
            return $list->map(function ($warehouse) use ($configuredWarehouseIds, $permissions) {
                $warehouseId         = (int) $warehouse->id;
                $data                = $warehouse->toArray();
                $data['permissions'] = isset($configuredWarehouseIds[$warehouseId])
                    ? $this->formatAccessiblePermissions((array) ($permissions[$warehouseId] ?? []))
                    : $this->formatAccessiblePermissions([WarehouseEnum::ACTION_STOCK => 1]);
                return $data;
            })->toArray();
        }

        // 普通库管：取可访问仓库
        $warehouseIds = $manager['warehouse_ids'] ?? [];
        if (empty($warehouseIds)) {
            return [];
        }

        $list        = $this->dao->getList(['id' => $warehouseIds, 'status' => WarehouseEnum::STATUS_ENABLE], ['id', 'name', 'code', 'address', 'status'],sort: ['sort','id']);
        $permissions = $manager['permissions'] ?? [];

        return $list->map(function ($warehouse) use ($permissions) {
            $data                = $warehouse->toArray();
            $data['permissions'] = $this->formatAccessiblePermissions((array) ($permissions[$warehouse->id] ?? []));
            return $data;
        })->toArray();
    }

    /**
     * 保存仓库基础资料.
     */
    public function saveWarehouse(array $data, int $uid, int $id = 0): int
    {
        if (! $data['name']) {
            throw $this->exception('请填写仓库名称');
        }
        $save = [
            'name'    => $data['name'],
            'contact' => $data['contact'] ?? '',
            'phone'   => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'status'  => (int) ($data['status'] ?? WarehouseEnum::STATUS_ENABLE),
            'sort'    => (int) ($data['sort'] ?? 0),
            'remark'  => $data['remark'] ?? '',
        ];
        if ($id) {
            $this->dao->update($id, $save);
            return $id;
        }
        $save['code']    = $this->generateWarehouseCode();
        $save['creator'] = $uid;
        return (int) $this->dao->create($save)->id;
    }

    /**
     * 删除未发生库存和单据的仓库.
     */
    public function deleteWarehouse(int $id): bool
    {
        if (app(WarehouseSkuStockDao::class)->exists(['warehouse_id' => $id])) {
            throw $this->exception('仓库已有关联库存，不允许删除');
        }
        foreach ([WarehouseInboundService::class, WarehouseOutboundService::class, WarehouseCheckService::class, WarehouseConvertService::class] as $service) {
            if (app($service)->exists(['warehouse_id' => $id])) {
                throw $this->exception('仓库已有历史单据，不允许删除');
            }
        }
        if (app(WarehouseTransferService::class)->hasWarehouseBill($id)) {
            throw $this->exception('仓库已有调拨单据，不允许删除');
        }
        return (bool) $this->dao->delete($id);
    }

    /**
     * 断言仓库存在且处于启用状态.
     */
    public function assertEnabled(int $warehouseId): void
    {
        $warehouse = $this->dao->get($warehouseId);
        if (! $warehouse) {
            throw $this->exception('仓库不存在');
        }
        if ((int) $warehouse->status !== WarehouseEnum::STATUS_ENABLE) {
            throw $this->exception('仓库已停用');
        }
    }

    /**
     * 生成唯一仓库编码.
     */
    protected function generateWarehouseCode(): string
    {
        do {
            $code = 'WH' . date('YmdHis') . mt_rand(1000, 9999);
        } while ($this->dao->getModel(false)->where('code', $code)->exists());
        return $code;
    }

    /**
     * 格式化可访问仓库接口返回的权限字段.
     */
    private function formatAccessiblePermissions(array $permissions, int $default = 0): array
    {
        $data = [];
        foreach (WarehouseEnum::PERMISSION_ACTIONS as $action) {
            $key        = 'can_' . $action;
            $data[$key] = (int) ($permissions[$key] ?? $permissions[$action] ?? $default);
        }
        return $data;
    }
}
