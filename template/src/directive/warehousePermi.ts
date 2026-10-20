import type { Directive, DirectiveBinding } from 'vue';
import pinia from '@/stores';
import { useWarehouseStore } from '@/stores/warehouse';

/**
 * 仓库业务动作权限指令：按「仓库 ID + 动作权限」控制元素显隐。
 *
 * 用法（permission 取 @/views/inventory/constants 的 WAREHOUSE_PERMISSION，支持单个标识或数组，数组时需同时具备）：
 * v-warehouse-permi="{ id: row.outbound_warehouse_id, permission: WAREHOUSE_PERMISSION.INBOUND }"
 *
 * 判定规则：
 * - 仓库 ID 为空 / 0 / 非法值视为无权限；
 * - 仓库不在当前登录人可查看仓库列表（warehouse store）中，或缺少指定动作权限，均视为无权限；
 * - 可查看仓库列表异步加载（带缓存），就绪前元素先隐藏，避免无权限按钮闪现；加载失败保持隐藏（宁缺勿滥）。
 *
 * 采用 display:none 隐藏而非移除节点：表格行复用时绑定值会变化，需支持 updated 钩子重新校验后恢复展示。
 */

// 仓库 ID 归一化：接口行数据可能为字符串（如 "2"），统一转正整数，无效值归 0
/** 指令绑定值：仓库 ID + 动作权限 */
export interface WarehousePermiValue {
  id?: number | string | null;
  permission?: string | string[];
}

type WarehousePermiElement = HTMLElement & { _wpOriginalDisplay?: string };

const toWarehouseId = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) && n > 0 ? n : 0;
};

// 同时导出给 v-if 使用：el-dropdown-item 等非元素根节点的组件上指令不生效
export const hasWarehousePermission = (value: WarehousePermiValue | null | undefined) => {
  const { id, permission } = value || {};
  const warehouseId = toWarehouseId(id);
  if (!warehouseId) return false;
  return useWarehouseStore(pinia).warehousesByPermission(permission).some((item) => item.id === warehouseId);
};

const check = (el: WarehousePermiElement, binding: DirectiveBinding<WarehousePermiValue>) => {
  // 带缓存加载，已就绪时同步 microtask 内完成；失败兜底保持隐藏
  useWarehouseStore(pinia)
    .loadAccessible()
    .then(() => {
      el.style.display = hasWarehousePermission(binding.value) ? el._wpOriginalDisplay! : 'none';
    })
    .catch(() => {});
};

const warehousePermi: Directive<WarehousePermiElement, WarehousePermiValue> = {
  mounted(el, binding) {
    // 记录原始内联 display 供恢复；就绪前先隐藏
    el._wpOriginalDisplay = el.style.display;
    el.style.display = 'none';
    check(el, binding);
  },
  updated(el, binding) {
    check(el, binding);
  },
};

export default warehousePermi;
