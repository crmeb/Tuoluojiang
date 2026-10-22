// 调拨方式枚举值（对齐「新增调拨」接口 transfer_type）
export const TRANSFER_TYPE = {
  DIRECT: 1, // 直接调拨
  LOGISTICS: 2 // 物流调拨
};

// 调拨方式完整配置：label / desc / 标签主题（StatusTag 面性标签）
export const TRANSFER_TYPE_OPTIONS = [
  {
    value: TRANSFER_TYPE.DIRECT,
    label: "直接调拨",
    desc: "直接调拨会直接将调出仓库的库存加至调入仓库，适用于短途调拨",
    theme: "warning",
    variant: "filled"
  },
  {
    value: TRANSFER_TYPE.LOGISTICS,
    label: "物流调拨",
    desc: "物流调拨需要调出仓库先出库，调入仓库收到货后进行入库，适用于在途时间较长的调拨方式",
    theme: "primary",
    variant: "filled"
  }
];

// 派生：value -> 配置，渲染标签/文案时 O(1) 查询
export const TRANSFER_TYPE_MAP = TRANSFER_TYPE_OPTIONS.reduce((map, item) => {
  map[item.value] = item;
  return map;
}, {});


// 调拨状态枚举值（对齐 apifox「调拨列表」接口 status：1 待出库，2 待入库，3 调拨完成，9 已作废）
export const TRANSFER_STATUS = {
  PENDING_OUT: 1, // 待出库（物流调拨：等待调出仓库出库）
  PENDING_IN: 2, // 待入库（物流调拨：调出已出库，等待调入仓库入库）
  COMPLETED: 3, // 已调拨（调拨完成）
  VOIDED: 9 // 已作废
};

// 调拨状态完整配置：label / 标签主题（StatusTag 纯文字标签，对齐入库/出库列表）
export const TRANSFER_STATUS_OPTIONS = [
  {
    value: TRANSFER_STATUS.PENDING_OUT,
    label: "待出库",
    theme: "warning",
    variant: "text"
  },
  {
    value: TRANSFER_STATUS.PENDING_IN,
    label: "待入库",
    theme: "success",
    variant: "text"
  },
  {
    value: TRANSFER_STATUS.COMPLETED,
    label: "已调拨",
    theme: "primary",
    variant: "text"
  },
  {
    value: TRANSFER_STATUS.VOIDED,
    label: "已作废",
    theme: "danger",
    variant: "text"
  }
];

// 派生：value -> 配置，渲染标签/文案时 O(1) 查询
export const TRANSFER_STATUS_MAP = TRANSFER_STATUS_OPTIONS.reduce((map, item) => {
  map[item.value] = item;
  return map;
}, {});
