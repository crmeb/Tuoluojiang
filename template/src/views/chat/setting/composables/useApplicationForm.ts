import { APP_TYPE, PLUGIN_TYPE } from '../constants'
import type { ApplicationDetail, ApplicationForm, KnowledgeItem } from '../types'

/** 表单初始值，同时也是「提交体包含哪些字段」的唯一定义 */
export const createApplicationForm = (): ApplicationForm => ({
  name: '',
  info: '',
  pic: '',
  tooltip_text: '',
  app_type: APP_TYPE.INTERNAL,
  auth_ids: [],
  edit_ids: [],
  use_limit: 0,
  sort: 0,
  models_id: '',
  enable_thinking: 1,
  prologue_text: '',
  prologue_list: [''],
  count_number: 0,
  source_types: [],
  tables: [],
  content: '',
  keyword: [],
  data_arrange_text: '',
  mcp_service_ids: [],
  knowledge_ids: [],
  json: []
})

const isUndef = (value: unknown) => value === undefined || value === null

/** 详情接口的空数组字段可能返回空字符串，统一收敛为真正的数组 */
const normalizeArray = (value: unknown): unknown[] => {
  if (Array.isArray(value)) return JSON.parse(JSON.stringify(value))
  if (typeof value !== 'string' || !value.trim()) return []

  try {
    const parsed = JSON.parse(value)
    return Array.isArray(parsed) ? parsed : []
  } catch (error) {
    return []
  }
}

const normalizeStringList = (value: unknown): string[] =>
  normalizeArray(value).filter((item): item is string => typeof item === 'string' && !!item)

/**
 * 插件类型收敛成数组
 *
 * 2.6 之前 source_type 是单选整数，升级前的存量数据仍可能是 0 / 1 这种标量，
 * 这里统一成数组，避免模板里再分支判断。
 */
const normalizeSourceTypes = (detail: ApplicationDetail): number[] => {
  let raw = (detail.source_types ?? (detail as Record<string, any>).source_type) as unknown
  // 存量数据里 source_type 可能是 "[1]" 这种未解码的 JSON 字符串
  if (typeof raw === 'string') {
    try {
      raw = JSON.parse(raw)
    } catch (error) {
      raw = []
    }
  }
  const list = Array.isArray(raw) ? raw : isUndef(raw) ? [] : [raw]
  return list.map(Number).filter((item) => Object.values(PLUGIN_TYPE).includes(item as any))
}

/**
 * 用详情数据填充表单
 *
 * 只覆盖表单已声明的字段，数组做深拷贝——直接复用详情里的数组会让表单编辑反向污染
 * 详情对象，详情二次赋值时表单状态就丢了。
 */
export const fillApplicationForm = (form: ApplicationForm, detail: ApplicationDetail) => {
  ;(Object.keys(form) as (keyof ApplicationForm)[]).forEach((key) => {
    const value = detail[key]
    if (isUndef(value)) return
    ;(form[key] as unknown) = Array.isArray(form[key]) ? normalizeArray(value) : value
  })

  form.source_types = normalizeSourceTypes(detail)
  // 详情里的 MCP 服务ID可能是字符串，提交时接口只接受整数
  form.mcp_service_ids = form.mcp_service_ids.map(Number).filter((id) => !!id)
  // 开场白问题为空时留一个空行，否则用户看不到可编辑的输入框
  if (!form.prologue_list.length) form.prologue_list = ['']
}

/**
 * 组装提交体
 *
 * - 开场白问题 / 关键字过滤掉空行，接口不接受空字符串
 * - 外部应用不保存可使用成员，避免脏数据回写
 * - 知识库以选中的卡片为准，同步 knowledge_ids
 * - MCP 只提交 mcp_service_ids，mcp_json 由后端按 ID 合并生成
 * - 插件未启用时不携带对应配置，避免提交当前不可见的插件数据
 */
export const buildApplicationPayload = (
  form: ApplicationForm,
  knowledge: KnowledgeItem[]
) => {
  const payload: Partial<ApplicationForm> = {
    ...form,
    prologue_list: normalizeStringList(form.prologue_list),
    keyword: normalizeStringList(form.keyword),
    knowledge_ids: knowledge.map((item) => item.id),
    // el-input type=number 拿到的是字符串，接口要的是整数
    use_limit: Number(form.use_limit) || 0,
    sort: Number(form.sort) || 0
  }

  if (payload.app_type === APP_TYPE.EXTERNAL) payload.auth_ids = []

  if (!form.source_types.includes(PLUGIN_TYPE.DATABASE)) {
    delete payload.tables
    delete payload.content
    delete payload.keyword
    delete payload.data_arrange_text
  }

  if (!form.source_types.includes(PLUGIN_TYPE.MCP)) delete payload.mcp_service_ids

  if (!form.source_types.includes(PLUGIN_TYPE.KNOWLEDGE)) delete payload.knowledge_ids

  return payload
}
