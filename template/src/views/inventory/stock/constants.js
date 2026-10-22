import { STOCK_STATUS } from "../setting/constants";
import { INBOUND_STATUS_MAP } from "../inbound/constants";
import { OUTBOUND_STATUS_MAP } from "../outbound/constants";
import { TRANSFER_STATUS_MAP } from "../transfer/constants";

// 「全部商品」页签值（不传 stock_status，查询全部）
export const STOCK_TAB_ALL = "all";

// 列表顶部状态页签（对齐原型：全部商品 / 库存过低 / 库存过高 / 已售罄）
// countKey 对应「库存数量统计」接口 data 下的字段，用于在页签后展示对应数量
export const STOCK_TABS = [
  { name: STOCK_TAB_ALL, label: "全部商品", countKey: "all" },
  { name: String(STOCK_STATUS.LOW), label: "库存过低", countKey: "low" },
  { name: String(STOCK_STATUS.HIGH), label: "库存过高", countKey: "high" },
  { name: String(STOCK_STATUS.SOLD_OUT), label: "已售罄", countKey: "sold_out" }
];

// 库存属性枚举值（对齐「库存台账」接口 stock_quality）
export const STOCK_QUALITY = {
  GOOD: 1, // 良品
  DEFECT: 2 // 次品
};

// 库存台账抽屉顶部页签：良品 / 残次品流水分开展示
// el-tab-pane 的 name 只接受字符串，请求时再转回数字传给 stock_quality
export const LEDGER_QUALITY_TABS = [
  { name: String(STOCK_QUALITY.GOOD), label: "良品台账" },
  { name: String(STOCK_QUALITY.DEFECT), label: "残次品台账" }
];

// 库存流水变动类型枚举值（对齐 apifox「库存台账」接口 flow_type）
export const FLOW_TYPE = {
  INBOUND: 1, // 入库
  OUTBOUND: 2, // 出库
  OCCUPY: 3, // 占用
  RELEASE_OCCUPY: 4, // 释放占用
  TRANSIT: 5, // 在途
  RELEASE_TRANSIT: 6, // 释放在途
  CHECK_GAIN: 7, // 盘盈
  CHECK_LOSS: 8, // 盘亏
  TRANSFER_OUT: 9, // 调拨出库
  TRANSFER_IN: 10, // 调拨入库
  CONVERT: 11 // 正残品转换
};

// 变动类型完整配置：label（变动原因文案）/ billLabel（变动情况里来源单号的字段名）
export const FLOW_TYPE_OPTIONS = [
  { value: FLOW_TYPE.INBOUND, label: "入库", billLabel: "入库单号" },
  { value: FLOW_TYPE.OUTBOUND, label: "出库", billLabel: "出库单号" },
  { value: FLOW_TYPE.OCCUPY, label: "占用", billLabel: "关联单号" },
  { value: FLOW_TYPE.RELEASE_OCCUPY, label: "释放占用", billLabel: "关联单号" },
  { value: FLOW_TYPE.TRANSIT, label: "在途", billLabel: "关联单号" },
  { value: FLOW_TYPE.RELEASE_TRANSIT, label: "释放在途", billLabel: "关联单号" },
  { value: FLOW_TYPE.CHECK_GAIN, label: "盘盈", billLabel: "盘点单号" },
  { value: FLOW_TYPE.CHECK_LOSS, label: "盘亏", billLabel: "盘点单号" },
  { value: FLOW_TYPE.TRANSFER_OUT, label: "调拨出库", billLabel: "调拨单号" },
  { value: FLOW_TYPE.TRANSFER_IN, label: "调拨入库", billLabel: "调拨单号" },
  { value: FLOW_TYPE.CONVERT, label: "正次转换", billLabel: "正次转换单号" }
];

// 派生：value -> 配置，渲染文案时 O(1) 查询
export const FLOW_TYPE_MAP = FLOW_TYPE_OPTIONS.reduce((map, item) => {
  map[item.value] = item;
  return map;
}, {});

// 台账流水来源单据类型 source_type（接口文档未登记枚举，按台账实际数据核实：
// 1 入库单 RK / 2 出库单 CK / 3 盘点单 PD / 4 调拨单 DB）
export const SOURCE_TYPE = {
  INBOUND: 1, // 入库单
  OUTBOUND: 2, // 出库单
  CHECK: 3, // 盘点单
  TRANSFER: 4 // 调拨单
};

// 来源单据类型 -> 该单据的状态配置表，用于流水「状态」列按 source_status 取文案与样式，
// 直接复用入库 / 出库 / 调拨列表的状态枚举，保证同一单据在各处展示一致。
// 目前流水只会关联这三类单据，后续新增来源（如盘点单、正次转换单）在此补一行映射即可
export const SOURCE_STATUS_MAP_BY_TYPE = {
  [SOURCE_TYPE.INBOUND]: INBOUND_STATUS_MAP,
  [SOURCE_TYPE.OUTBOUND]: OUTBOUND_STATUS_MAP,
  [SOURCE_TYPE.TRANSFER]: TRANSFER_STATUS_MAP
};

// 占用 / 在途明细弹窗（FlowDialog）按流水类型的差异配置：表格列
// 数据统一来自库存台账接口（flow_type=占用/在途），字段取台账流水记录
export const STOCK_FLOW_DIALOG_CONFIG = {
  [FLOW_TYPE.OCCUPY]: {
    columns: [
      { field: "source_no", name: "关联单号", minWidth: 170 },
      { field: "remark", name: "占用原因", minWidth: 180 },
      { field: "status", name: "状态", minWidth: 90, slot: "status" },
      { field: "change_num", name: "占用数量", minWidth: 90, slot: "quantity" },
      { field: "created_at", name: "占用时间", minWidth: 150 },
      { field: "action", name: "操作", minWidth: 90, slot: "action" }
    ]
  },
  [FLOW_TYPE.TRANSIT]: {
    columns: [
      { field: "source_no", name: "调拨单号", minWidth: 170 },
      { field: "status", name: "状态", minWidth: 90, slot: "status" },
      { field: "change_num", name: "在途数量", minWidth: 90, slot: "quantity" },
      { field: "warehouse_id", name: "调出仓库", minWidth: 160, slot: "out_warehouse" },
      { field: "created_at", name: "出库时间", minWidth: 150 },
      { field: "action", name: "操作", minWidth: 90, slot: "action" }
    ]
  }
};
