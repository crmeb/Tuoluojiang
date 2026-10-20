/**
 * AI 应用设置页类型
 *
 * 字段与 Apifox `AiApplicationSaveRequest` 一一对应，详情接口多返回的展示字段
 * （knowledge / model / user 等）放在 ApplicationDetail 里，不进提交体。
 */

/** MCP 插件选中的服务，字段对应 Apifox `AiMcpServiceItem` */
export interface McpItem {
  id: number
  name?: string
  info?: string
  /** 是否系统内置：1=内置（只读，不可编辑/删除），0=自定义 */
  is_default?: number
  status?: number
  sort?: number
  config_json?: Record<string, any>
}

/** 知识库插件选中的知识库 */
export interface KnowledgeItem {
  id: number
  name?: string
  description?: string
  icon?: string
  color?: string
}

/** 模型参数设置的一行 */
export interface ParamItem {
  name: string
  filed: string
  value: string
  message: string
}

/** 应用表单，即保存草稿 / 发布时的提交体。status 由发布接口写入，不随表单提交 */
export interface ApplicationForm {
  name: string
  info: string
  pic: string
  /** 人设与回复逻辑（系统提示词） */
  tooltip_text: string
  /** 应用类型：0=内部应用，1=外部应用 */
  app_type: number
  /** 可使用成员ID，app_type=1 时不提交 */
  auth_ids: number[]
  /** 编辑权限成员ID */
  edit_ids: number[]
  use_limit: number
  sort: number
  models_id: number | ''
  /** 是否开启模型思考：1=开启，0=关闭 */
  enable_thinking: 0 | 1
  prologue_text: string
  prologue_list: string[]
  count_number: number
  /** 插件多选：1=数据库，2=MCP，4=知识库 */
  source_types: number[]
  tables: string[]
  content: string
  keyword: string[]
  data_arrange_text: string
  /** MCP 插件绑定的服务ID数组，后端据此合并生成 mcp_json，前端不再提交 mcp_json */
  mcp_service_ids: number[]
  knowledge_ids: number[]
  /** 模型高级参数设置 */
  json: ParamItem[]
}

/** 详情接口返回体，只取页面用得到的字段 */
export interface ApplicationDetail extends Partial<ApplicationForm> {
  id?: number
  status?: number
  /** 访客端分享标识，仅外部应用返回；调试预览按访客形态挂载时要用 */
  share_key?: string
  /** 绑定知识库详情，用于插件区渲染卡片 */
  knowledge?: KnowledgeItem[]
  /** 绑定 MCP 服务详情，与 mcp_service_ids 对应，用于插件区渲染卡片 */
  mcp_services?: McpItem[]
  /** 是否有尚未发布的草稿 */
  has_draft?: boolean
}

/** 传给预览 SDK 的状态 */
export interface PreviewState {
  prologueText: string
  prologueList: string[]
}
