/**
 * 仓储模块公共常量
 */

// 仓库业务动作权限标识（后端可查看仓库列表 / 库管权限配置统一使用这套 key）
export const WAREHOUSE_PERMISSION = {
  STOCK: "can_stock", // 查看库存
  INBOUND: "can_inbound", // 入库
  OUTBOUND: "can_outbound", // 出库
  CHECK: "can_check", // 盘点
  TRANSFER: "can_transfer", // 调拨
  CONVERT: "can_convert", // 正次转换
  STOCK_SETTING: "can_stock_setting" // 库存设置
};

// 权限标识 + 中文名（用于库管权限配置勾选项等展示场景）
export const WAREHOUSE_PERMISSION_LIST = [
  { key: WAREHOUSE_PERMISSION.STOCK, name: "查看库存" },
  { key: WAREHOUSE_PERMISSION.INBOUND, name: "入库" },
  { key: WAREHOUSE_PERMISSION.OUTBOUND, name: "出库" },
  { key: WAREHOUSE_PERMISSION.CHECK, name: "盘点" },
  { key: WAREHOUSE_PERMISSION.TRANSFER, name: "调拨" },
  { key: WAREHOUSE_PERMISSION.CONVERT, name: "正次转换" },
  { key: WAREHOUSE_PERMISSION.STOCK_SETTING, name: "库存设置" },
];

// 全部权限标识数组
export const WAREHOUSE_PERMISSION_KEYS = WAREHOUSE_PERMISSION_LIST.map((item) => item.key);

// 仓库下拉的数据源范围（WareHouseSelect 的 scope / useWarehouse 的 scope）
export const WAREHOUSE_SCOPE = {
  // 当前登录人可查看的仓库，带业务动作权限，可配合 permission 过滤（单据类场景用这个）
  ACCESSIBLE: "accessible",
  // 全量仓库（含已关闭、当前登录人无权限的仓库），仅用于「给仓库配权限」的场景
  ALL: "all"
};

// 仓库类型枚举值（调拨列表按「调出 / 调入」维度筛选仓库，对齐接口 warehouse_type 取值）
export const WAREHOUSE_TYPE = {
  OUT: "out", // 调出仓库
  IN: "in" // 调入仓库
};

// 仓库类型选项（WareHouseTypeSelect 左侧类型下拉）
export const WAREHOUSE_TYPE_OPTIONS = [
  { value: WAREHOUSE_TYPE.OUT, label: "调出仓库" },
  { value: WAREHOUSE_TYPE.IN, label: "调入仓库" }
];

// 关联订单付款状态枚举值（入库 / 出库单据共用）
export const PAYMENT_STATUS = {
  UNPAID: 0,
  PAID: 1,
};

// 付款状态完整配置：label / 标签主题（StatusTag 面性标签）
export const PAYMENT_STATUS_OPTIONS = [
  {
    value: PAYMENT_STATUS.UNPAID,
    label: "未结清",
    theme: "danger",
    variant: "filled"
  },
  {
    value: PAYMENT_STATUS.PAID,
    label: "已结清",
    theme: "success",
    variant: "filled"
  },
];

// 派生：value -> 配置，渲染标签/文案时 O(1) 查询
export const PAYMENT_STATUS_MAP = PAYMENT_STATUS_OPTIONS.reduce((map, item) => {
  map[item.value] = item;
  return map;
}, {});
