import { ref, computed } from "vue";
import type { StockRow } from "../../types";
import { useTable } from "@/composables/useTable";
import { stockLimitListApi } from "@/api/inventory";
import { STOCK_STATUS_OPTIONS } from "../constants";
import { WAREHOUSE_PERMISSION } from "../../constants";

/**
 * 库存设置列表逻辑：组合通用列表(useTable) + 搜索字段 + 列定义 + 多选 + 设置弹窗控制。
 *
 * 列表按「仓库 + SKU」维度返回记录，同一 SKU 在不同仓库会拆分为多行，
 * 因此保留仓库列与仓库筛选用于区分。
 */
export const useSetting = () => {
  const dialogRef = ref();
  const tableRef = ref();

  // 仓库为必选条件（搜索项配置 default_first 自动选中首仓），首次查询由仓库默认选中触发，
  // 故关闭 immediate，避免初始化时先发一次不带 warehouse_id 的请求
  const table = useTable(stockLimitListApi, { immediate: false });

  // 当前多选选中的行
  const selectedRows = ref<StockRow[]>([]);
  const hasSelection = computed(() => selectedRows.value.length > 0);

  const handleSelectionChange = (rows: StockRow[]) => {
    selectedRows.value = rows;
  };

  const searchFields = [
    {
      field_name: "商品编码/条码/名称",
      field_name_en: "keyword",
      form_value: "input",
      width: "215px"
    },
    {
      field_name: "仓库名称",
      field_name_en: "warehouse_id",
      form_value: "warehouse_select",
      default_first: true,
      // 库存设置列表只筛选当前用户有「库存设置」权限的仓库
      permission: [WAREHOUSE_PERMISSION.STOCK_SETTING]
    },
    {
      field_name: "库存状态",
      field_name_en: "stock_status",
      form_value: "select",
      data_dict: STOCK_STATUS_OPTIONS.map(item => ({
        value: item.value,
        name: item.label
      }))
    }
  ];

  // 列顺序 / 名称依据原型「库存设置」表头，另增仓库列区分多仓记录
  const tableColumns = [
    { field: "warehouse.name", name: "仓库", minWidth: 120 },
    { field: "goods", name: "商品信息", minWidth: 240, slot: "goods" },
    { field: "spec", name: "规格", minWidth: 200, slot: "spec" },
    { field: "remain_stock", name: "剩余库存", minWidth: 100, slot: "remain_stock" },
    { field: "high_limit", name: "库存上限", minWidth: 130, slot: "high_limit" },
    { field: "low_limit", name: "库存下限", minWidth: 130, slot: "low_limit" }
  ];

  // 批量设置：打开弹窗并带入当前选中行
  const handleBatchSet = () => {
    if (!hasSelection.value) return;
    dialogRef.value?.open(selectedRows.value);
  };

  // 设置成功后：刷新列表并清空选中
  const handleSetSuccess = () => {
    tableRef.value?.clearSelection();
    selectedRows.value = [];
    table.fetchData();
  };

  return {
    ...table,

    dialogRef,
    tableRef,
    selectedRows,
    hasSelection,

    searchFields,
    tableColumns,

    handleSelectionChange,
    handleBatchSet,
    handleSetSuccess
  };
};
