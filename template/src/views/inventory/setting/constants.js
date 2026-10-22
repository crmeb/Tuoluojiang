// 库存状态枚举值
export const STOCK_STATUS = {
  NORMAL: 0, // 正常
  LOW: 1, // 偏低
  HIGH: 2, // 偏高
  SOLD_OUT: 3 // 已售罄
};

// 库存状态完整配置：label / 标签主题（StatusTag 面性标签）
export const STOCK_STATUS_OPTIONS = [
  {
    value: STOCK_STATUS.NORMAL,
    label: "正常",
    theme: "success",
    variant: "filled"
  },
  {
    value: STOCK_STATUS.LOW,
    label: "偏低",
    theme: "warning",
    variant: "filled"
  },
  {
    value: STOCK_STATUS.HIGH,
    label: "偏高",
    theme: "primary",
    variant: "filled"
  },
  {
    value: STOCK_STATUS.SOLD_OUT,
    label: "已售罄",
    theme: "pink",
    variant: "filled"
  }
];

// 派生：value -> 配置，渲染标签 / 文案时 O(1) 查询
export const STOCK_STATUS_MAP = STOCK_STATUS_OPTIONS.reduce((map, item) => {
  map[item.value] = item;
  return map;
}, {});
