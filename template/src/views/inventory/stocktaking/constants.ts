// 盘点状态枚举值
export const CHECK_STATUS = {
  CHECKING: 1, // 盘点中
  COMPLETED: 2, // 已完成
  VOIDED: 9 // 已作废
};

// 盘点状态完整配置：label / 标签主题（StatusTag 纯文字标签）
export const CHECK_STATUS_OPTIONS = [
  {
    value: CHECK_STATUS.CHECKING,
    label: "盘点中",
    theme: "warning",
    variant: "text"
  },
  {
    value: CHECK_STATUS.COMPLETED,
    label: "已完成",
    theme: "success",
    variant: "text"
  },
  {
    value: CHECK_STATUS.VOIDED,
    label: "已作废",
    theme: "danger",
    variant: "text"
  }
];

// 派生：value -> 配置，渲染标签 / 文案时 O(1) 查询
export const CHECK_STATUS_MAP = CHECK_STATUS_OPTIONS.reduce<Record<string | number, (typeof CHECK_STATUS_OPTIONS)[number]>>((map, item) => {
  map[item.value] = item;
  return map;
}, {});
