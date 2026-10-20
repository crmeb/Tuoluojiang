import { computed } from "vue";
import { useWarehouseStore } from "@/stores/warehouse";
import { WAREHOUSE_SCOPE } from "../constants";

/**
 * 仓储仓库 composable：读取全局 warehouse store 维护的仓库列表。
 *
 * @param {Object}   [options]
 * @param {boolean}  [options.immediate=true] 是否初始化时立即加载
 * @param {Array}    [options.permission=[]]  按业务动作权限过滤（仅 accessible 范围有效）
 * @param {string}   [options.scope]          数据源范围，取值见 constants 的 WAREHOUSE_SCOPE：
 *                                            accessible（默认）=可查看仓库；all=全量仓库
*/
export const useWarehouse = ({
  immediate = true,
  permission = [] as string | string[],
  scope = WAREHOUSE_SCOPE.ACCESSIBLE as string
}: { immediate?: boolean; permission?: string | string[]; scope?: string } = {}) => {
  const isAll = scope === WAREHOUSE_SCOPE.ALL;
  const warehouseStore = useWarehouseStore();

  const warehouseLoading = computed(() =>
    isAll ? warehouseStore.allLoading : warehouseStore.loading
  );

  // el-select 选项：{ label, value }。
  // 全量范围不涉及权限，permission 自然失效；accessible 范围下 permission
  // 传空时 warehousesByPermission 返回全量可查看仓库，无需额外分支。
  const options = computed(() => {
    const list = isAll
      ? warehouseStore.allList
      : warehouseStore.warehousesByPermission(permission);
    return list.map((item) => ({
      label: item.name,
      value: item.id
    }))
  });

  // 加载仓库列表（带缓存）；force=true 强制刷新
  const loadWarehouses = (force = false) =>
    isAll ? warehouseStore.loadAll({ force }) : warehouseStore.loadAccessible({ force });

  // 强制刷新当前范围（新增/编辑仓库、变更库管权限后调用）
  const refreshWarehouses = () =>
    isAll ? warehouseStore.refreshAll() : warehouseStore.refreshAccessible();

  if (immediate) loadWarehouses();

  return {
    warehouseLoading,
    options,
    loadWarehouses,
    refreshWarehouses
  };
};
