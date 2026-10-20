/**
 * AI 应用列表页常量
 *
 * 与应用设置页共用的枚举（应用类型 / 插件类型等）在 `setting/constants.ts`，
 * 这里只放列表页与创建弹窗自己的东西。
 */

/** 每页条数的兜底初值，实际由 useGridPageSize 按容器宽高算出并覆盖 */
export const PAGE_SIZE = 12

/** 应用发布状态，对应列表接口的 status */
export const APP_STATUS = {
  UNPUBLISHED: 0,
  PUBLISHED: 1
} as const

/** 应用简介输入框的占位文案 */
export const APP_INFO_PLACEHOLDER =
  '你是一个企业管理小助手，你能通过企业管理数据，进行挑战与现状分析、核心数据统计和优化建议；同时，保持积极的语气，展示出解决方案的可行性，这样更有助于激励团队采取行动。'
