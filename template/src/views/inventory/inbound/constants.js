// 入库类型枚举值
// 入库类型：1 采购 / 2 退货 / 3 其他
export const INBOUND_TYPE = {
  PURCHASE: 1,
  RETURN: 2,
  OTHER: 3
};

// 入库类型完整配置：label / desc / 标签主题（StatusTag 面性标签）
export const INBOUND_TYPE_OPTIONS = [
  {
    value: INBOUND_TYPE.PURCHASE,
    label: "采购入库",
    desc: "采购入库，是指企业采购的商品运抵仓库后，正式登记并纳入库存管理",
    theme: "primary",
    variant: "filled"
  },
  {
    value: INBOUND_TYPE.RETURN,
    label: "退货入库",
    desc: "退货入库，是指客户退回的商品重新登记入库",
    theme: "pink",
    variant: "filled"
  },
  {
    value: INBOUND_TYPE.OTHER,
    label: "调拨入库",
    desc: "调拨入库，是指除采购、退货之外的其他来源商品入库",
    theme: "gray",
    variant: "filled"
  }
];

// 派生：value -> 配置，渲染标签/文案时 O(1) 查询
export const INBOUND_TYPE_MAP = INBOUND_TYPE_OPTIONS.reduce((map, item) => {
  map[item.value] = item;
  return map;
}, {});


// 入库状态枚举值（入库单创建即为待入库，无草稿态）
export const INBOUND_STATUS = {
  PENDING: 1, // 待入库
  COMPLETED: 2, // 已完成
  VOIDED: 9, // 已作废
}


// 入库状态完整配置：label / 标签主题（StatusTag 纯文字标签）
export const INBOUND_STATUS_OPTIONS = [
  {
    value: INBOUND_STATUS.PENDING,
    label: "待入库",
    theme: "success",
    variant: "text"
  },
  {
    value: INBOUND_STATUS.COMPLETED,
    label: "已完成",
    theme: "warning",
    variant: "text"
  },
  {
    value: INBOUND_STATUS.VOIDED,
    label: "已作废",
    theme: "gray",
    variant: "text"
  }
];

// 派生：value -> 配置，渲染标签/文案时 O(1) 查询
export const INBOUND_STATUS_MAP = INBOUND_STATUS_OPTIONS.reduce((map, item) => {
  map[item.value] = item;
  return map;
}, {});