import { computed } from "vue";
import store from "@/store";

/**
 * 全量仓库元数据：id -> 仓库信息映射
 *
 * 仅用于按仓库 ID 补全名称等展示信息（台账调拨方向、在途调出仓库等），
 * 与权限无关，勿与「可查看仓库」（WAREHOUSE_SCOPE.ACCESSIBLE）混用。
 *
 * 数据取自全局 warehouse 模块的全量仓库缓存，与 WareHouseSelect 的 scope=all
 * 共用同一份数据和请求，避免两处各自缓存、重复拉取。
 *
 * 懒加载：调用 loadWarehouseMap 时才请求，全局缓存，失败后允许下次重试。
 *
 * @returns {{ warehouseMap, loadWarehouseMap, getWarehouseName }}
 */
export const useWarehouseMap = () => {
  const warehouseMap = computed(() => store.getters["warehouse/warehouseMap"]);

  const loadWarehouseMap = () => store.dispatch("warehouse/loadAll");

  const getWarehouseName = (id) => {
    return warehouseMap.value[id]?.name || "--";
  };

  return { warehouseMap, loadWarehouseMap, getWarehouseName };
};
