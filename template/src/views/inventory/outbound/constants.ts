// 出库类型枚举值
export const OUTBOUND_TYPE = {
  SALE: 1, // 销售出库
  EXPIRE_RETURN: 2, // 过期退货
  TRIAL: 3, // 试用出库
  OTHER: 4 // 调拨出库
};

// 出库类型完整配置：label / desc / 标签主题（StatusTag 面性标签）
export const OUTBOUND_TYPE_OPTIONS = [
  {
    value: OUTBOUND_TYPE.SALE,
    label: "销售出库",
    desc: "销售出库，指的是销售订单拣货出库",
    theme: "primary",
    variant: "filled"
  },
  {
    value: OUTBOUND_TYPE.EXPIRE_RETURN,
    label: "过期退货",
    desc: "过期退货，是指商品过期后退回供应商或作报废处理的出库",
    theme: "pink",
    variant: "filled"
  },
  {
    value: OUTBOUND_TYPE.TRIAL,
    label: "试用出库",
    desc: "试用出库，是指用于客户试用、样品发放等场景的出库",
    theme: "warning",
    variant: "filled"
  },
  {
    value: OUTBOUND_TYPE.OTHER,
    label: "调拨出库",
    desc: "调拨出库，是指除销售、退货、试用之外的其他用途商品出库",
    theme: "gray",
    variant: "filled"
  }
];

// 派生：value -> 配置，渲染标签/文案时 O(1) 查询
export const OUTBOUND_TYPE_MAP = OUTBOUND_TYPE_OPTIONS.reduce<Record<string | number, (typeof OUTBOUND_TYPE_OPTIONS)[number]>>((map, item) => {
  map[item.value] = item;
  return map;
}, {});


// 出库状态枚举值（出库单创建即为待出库，无草稿态）
export const OUTBOUND_STATUS = {
  PENDING: 1, // 待出库
  COMPLETED: 2, // 已完成
  VOIDED: 9, // 已作废
};

// 出库状态完整配置：label / 标签主题（StatusTag 纯文字标签）
export const OUTBOUND_STATUS_OPTIONS = [
  {
    value: OUTBOUND_STATUS.PENDING,
    label: "待出库",
    theme: "success",
    variant: "text"
  },
  {
    value: OUTBOUND_STATUS.COMPLETED,
    label: "已完成",
    theme: "warning",
    variant: "text"
  },
  {
    value: OUTBOUND_STATUS.VOIDED,
    label: "已作废",
    theme: "gray",
    variant: "text"
  }
];

// 派生：value -> 配置，渲染标签/文案时 O(1) 查询
export const OUTBOUND_STATUS_MAP = OUTBOUND_STATUS_OPTIONS.reduce<Record<string | number, (typeof OUTBOUND_STATUS_OPTIONS)[number]>>((map, item) => {
  map[item.value] = item;
  return map;
}, {});
