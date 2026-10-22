// 客户标签组类型：0 手动标签 / 1 系统标签
// 供客户标签设置页、添加标签组弹窗、客户标签选择弹窗共用，避免各处重复维护枚举与配色

export const LABEL_GROUP_TYPE = Object.freeze({
  MANUAL: 0,
  SYSTEM: 1
})

// 完整配置：value / label / 标签主题（StatusTag 面性标签）
export const LABEL_GROUP_TYPE_OPTIONS = Object.freeze([
  {
    value: LABEL_GROUP_TYPE.MANUAL,
    label: '手动标签',
    theme: 'primary',
    variant: 'filled'
  },
  {
    value: LABEL_GROUP_TYPE.SYSTEM,
    label: '系统标签',
    theme: 'warning',
    variant: 'filled'
  }
])

// 派生：value -> 配置，渲染标签时 O(1) 查询
export const LABEL_GROUP_TYPE_MAP = LABEL_GROUP_TYPE_OPTIONS.reduce((map, item) => {
  map[item.value] = item
  return map
}, {})
