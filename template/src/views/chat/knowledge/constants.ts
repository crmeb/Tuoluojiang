/** 知识库图标底色，取自设计稿色板 */
export const ICON_COLORS = [
  '#1890FF',
  '#5762FF',
  '#10BEFF',
  '#00D6B9',
  '#39D826',
  '#222222',
  '#FF40AC',
  '#FF625E',
  '#FF8502',
  '#FFC300'
]

/** 未选图标时的兜底展示 */
export const DEFAULT_ICON = 'CRMEB-wendang-xianxing'
export const DEFAULT_ICON_COLOR = ICON_COLORS[0]

/** 列表页每页条数的兜底初值（实际由 useGridPageSize 按容器宽高算出），也是选择弹窗的默认值 */
export const PAGE_SIZE = 12

/** 卡片右下角「更多」下拉项 */
export const CARD_ACTIONS = [
  { label: '设置', value: 'setting' },
  { label: '删除', value: 'delete' }
]

/** 详情页页签，「问题」相关功能本期不做 */
export const EDIT_TABS = [
  { label: '文档', value: 'document' },
  { label: '关联应用', value: 'application' },
  { label: '设置', value: 'setting' }
]

/** 文档处理状态，取自接口 status 枚举 */
export const DOC_STATUS = {
  PENDING: 0,
  PROCESSING: 1,
  DONE: 2,
  FAILED: 3
} as const

export const DOC_STATUS_TEXT: Record<number, string> = {
  [DOC_STATUS.PENDING]: '待处理',
  [DOC_STATUS.PROCESSING]: '分片处理中',
  [DOC_STATUS.DONE]: '',
  [DOC_STATUS.FAILED]: '向量化失败'
}

/** 文档列表勾选后的批量操作项 */
export const BATCH_ACTIONS = [
  { label: '开启', value: 'enable' },
  { label: '关闭', value: 'disable' },
  { label: '删除', value: 'delete' }
]

/** 分段来源，取自接口 source_type 枚举 */
export const CHUNK_SOURCE_TEXT: Record<number, string> = {
  1: '系统生成',
  2: '手动创建'
}

/** 分段向量化状态，取自接口 status 枚举 */
export const CHUNK_STATUS = {
  PENDING: 0,
  DONE: 1,
  FAILED: 2
} as const

/** 向量化范围，接口暂不支持按范围区分，仅保留交互 */
export const EMBED_SCOPES = [
  { label: '向量化未成功的分段', value: 'failed' },
  { label: '全部分段', value: 'all' }
]

/** 文档列表每页条数（左侧列表窄，走滚动分页视觉） */
export const DOC_PAGE_SIZE = 20

/** 分段卡片单行通栏排布，每页取 12 条 */
export const CHUNK_PAGE_SIZE = 12

/** 分段纯文本长度上限，与接口 content 的 maxLength 对齐 */
export const CHUNK_CONTENT_MAX = 20000

/**
 * 分段编辑器（mavon-editor）工具栏
 *
 * 弹窗内的全屏 / 沉浸式阅读会被 el-dialog 的层级盖住，保持关闭；
 * imagelink 同时提供图片上传入口，编辑区粘贴图片也会复用同一上传事件
 */
export const MARKDOWN_TOOLBARS = {
  bold: true,
  italic: true,
  header: true,
  underline: true,
  strikethrough: true,
  mark: true,
  quote: true,
  ol: true,
  ul: true,
  link: true,
  imagelink: true,
  code: true,
  table: true,
  undo: true,
  redo: true,
  trash: true,
  navigation: true,
  preview: true,
  subfield: true,
  help: true
}

/** 导入文档限制：与接口 attach_ids 的约束一致 */
export const UPLOAD_ACCEPT = ['docx', 'xlsx', 'pptx', 'pdf', 'md', 'txt']
export const UPLOAD_MAX_COUNT = 20
export const UPLOAD_MAX_SIZE_MB = 20
