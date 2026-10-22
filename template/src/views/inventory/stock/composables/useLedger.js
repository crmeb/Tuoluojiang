import { ref } from "vue";
import { useTable } from "@/composables/useTable";
import { useWarehouseMap } from "../../composables/useWarehouseMap";
import { stockLedgerApi } from "@/api/inventory";
import { FLOW_TYPE, FLOW_TYPE_MAP, LEDGER_QUALITY_TABS } from "../constants";

/**
 * 库存台账抽屉逻辑：按「仓库 + SKU + 库存属性」维度分页查询库存变动流水
 */
export const useLedger = () => {
  const visible = ref(false);
  const row = ref({});

  // 良品 / 残次品页签，作为搜索表单以外的查询条件，
  // 经 extraParams 在每次请求时合并（需先于 useTable 声明）
  const activeQuality = ref(LEDGER_QUALITY_TABS[0].name);

  // 打开抽屉时才按当前行查询，挂载时不请求
  const table = useTable(stockLedgerApi, {
    immediate: false,
    extraParams: () => ({ stock_quality: Number(activeQuality.value) })
  });

  // 仓库 id -> 名称映射：台账流水的调拨方向只返回 target_warehouse_id，需前端补名称
  const { loadWarehouseMap, getWarehouseName } = useWarehouseMap();

  // 列顺序 / 名称对齐原型「库存台账」抽屉表头
  const tableColumns = [
    { field: "change", name: "库存变化", minWidth: 150, slot: "change" },
    { field: "reason", name: "变动原因", minWidth: 200, slot: "reason" },
    { field: "detail", name: "变动情况", minWidth: 260, slot: "detail" }
  ];

  // 变动原因：调拨流水补充对方仓库名，其余直接取类型文案
  const getReason = (item) => {
    if (item.flow_type === FLOW_TYPE.TRANSFER_OUT) return `调出至 ${getWarehouseName(item.target_warehouse_id)}`;
    if (item.flow_type === FLOW_TYPE.TRANSFER_IN) return `由 ${getWarehouseName(item.target_warehouse_id)} 调入`;
    return FLOW_TYPE_MAP[item.flow_type]?.label || "--";
  };

  // 来源单号字段名：按变动类型区分（入库单号 / 出库单号 / 调拨单号...）
  const getBillLabel = (item) => {
    return FLOW_TYPE_MAP[item.flow_type]?.billLabel || "关联单号";
  };

  // 页签切换只影响流水属性，商品 / 仓库条件不变，回到第 1 页重查即可
  const handleQualityChange = () => {
    table.handleSearch();
  };

  const openBox = (stockRow) => {
    row.value = stockRow;
    visible.value = true;
    // 每次打开都是新的 SKU，页签回到默认的「良品台账」
    activeQuality.value = LEDGER_QUALITY_TABS[0].name;
    loadWarehouseMap();
    table.handleSearch({
      warehouse_id: stockRow.warehouse_id,
      sku_id: stockRow.sku_id
    });
  };

  // 关闭即清空，避免下次打开残留上一个 SKU 的流水
  const reset = () => {
    table.tableData.value = [];
    table.total.value = 0;
  };

  return {
    ...table,

    visible,
    row,
    tableColumns,
    activeQuality,

    getReason,
    getBillLabel,
    handleQualityChange,
    openBox,
    reset
  };
};
