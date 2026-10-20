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

namespace App\Http\Requests\Warehouse;

use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Requests\ApiValidate;

/**
 * 库管资料校验.
 */
class WarehouseManagerRequest extends ApiValidate
{
    /**
     * 库管保存规则.
     */
    protected $scene = [
        'save'   => ['name', 'manager_type', 'can_view_other', 'status', 'sort', 'user_ids', 'warehouse_ids', 'warehouse_permissions'],
        'update' => ['name', 'manager_type', 'can_view_other', 'status', 'sort', 'user_ids', 'warehouse_ids', 'warehouse_permissions'],
    ];

    /**
     * 获取库管验证规则.
     */
    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:100',
            'manager_type'   => ['required', 'integer', 'in:0,1', function ($attribute, $value, $fail) {
                if ((int) $value === WarehouseEnum::MANAGER_NORMAL
                    && ! $this->hasWarehousePermission($this->request()->input('warehouse_permissions', []))
                    && ! $this->hasWarehouseId($this->request()->input('warehouse_ids', []))) {
                    $fail('请选择管理仓库');
                }
            }],
            'can_view_other' => 'nullable|integer|in:0,1',
            'status'         => 'nullable|integer|in:0,1',
            'sort'           => 'nullable|integer|min:0',
            'user_ids'       => 'nullable|array',
            'warehouse_ids'  => 'nullable|array',
            'warehouse_permissions'                => 'nullable|array',
            'warehouse_permissions.*.warehouse_id'      => 'required_with:warehouse_permissions|integer|min:1',
            'warehouse_permissions.*.can_stock'         => 'nullable|integer|in:0,1',
            'warehouse_permissions.*.can_stock_setting' => 'nullable|integer|in:0,1',
            'warehouse_permissions.*.can_inbound'       => 'nullable|integer|in:0,1',
            'warehouse_permissions.*.can_outbound'      => 'nullable|integer|in:0,1',
            'warehouse_permissions.*.can_check'         => 'nullable|integer|in:0,1',
            'warehouse_permissions.*.can_transfer'      => 'nullable|integer|in:0,1',
            'warehouse_permissions.*.can_convert'       => 'nullable|integer|in:0,1',
        ];
    }

    /**
     * 获取库管验证提示.
     */
    public function message(): array
    {
        return [
            'name.required'      => '请填写库管名称',
            'manager_type.required' => '请选择库管权限类型',
            'manager_type.in'    => '库管类型错误',
            'can_view_other.in'  => '查看其它库存权限错误',
            'status.in'          => '库管状态错误',
            'sort.integer'       => '排序格式错误',
            'user_ids.array'     => '库管人员格式错误',
            'warehouse_ids.array' => '关联仓库格式错误',
            'warehouse_permissions.array' => '仓库权限格式错误',
            'warehouse_permissions.*.warehouse_id.required_with' => '请选择管理仓库',
            'warehouse_permissions.*.warehouse_id.integer' => '管理仓库格式错误',
            'warehouse_permissions.*.warehouse_id.min' => '管理仓库格式错误',
            'warehouse_permissions.*.can_stock.in' => '查看库存权限错误',
            'warehouse_permissions.*.can_stock_setting.in' => '库存设置权限错误',
            'warehouse_permissions.*.can_inbound.in' => '入库权限错误',
            'warehouse_permissions.*.can_outbound.in' => '出库权限错误',
            'warehouse_permissions.*.can_check.in' => '盘点权限错误',
            'warehouse_permissions.*.can_transfer.in' => '调拨权限错误',
            'warehouse_permissions.*.can_convert.in' => '正次转换权限错误',
        ];
    }

    /**
     * 判断是否提交了有效仓库权限.
     */
    private function hasWarehousePermission($permissions): bool
    {
        foreach ((array) $permissions as $permission) {
            $warehouseId = is_array($permission) ? (int) ($permission['warehouse_id'] ?? $permission['id'] ?? 0) : (int) $permission;
            if ($warehouseId > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断是否提交了有效仓库 ID.
     */
    private function hasWarehouseId($warehouseIds): bool
    {
        foreach ((array) $warehouseIds as $warehouseId) {
            if ((int) $warehouseId > 0) {
                return true;
            }
        }
        return false;
    }
}
