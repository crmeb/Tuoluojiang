// AI 模型配置模块的共享常量。
// 后端 v2.6 起字段统一为 provider / config_type / model_id / model_type / api_key / base_url，
// 旧的 driver / chat_model / embedding_model / enabled_embedding 已废弃，勿再引用。

// 配置类型：预设供应商 / 自定义（OpenAI 兼容）/ 一号通
export const CONFIG_TYPE = Object.freeze({
  PRESET: 1,
  CUSTOM: 2,
  YIHAOTONG: 3
})

// 自定义配置对应的驱动，供应商选项接口异常时的兜底值
export const CUSTOM_DRIVER = 'openai_compatible'

// 一号通驱动：前端不暴露 base_url / api_key，由后端按 config_type 补全
export const YIHAOTONG_DRIVER = 'yihaotong'

// 各供应商的 API Key 申请入口由 options 接口下发（data[].api_key_url），前端不再维护映射表

// 获取模型接口按「模型能力」分组返回，取值用于区分聊天模型与向量模型
export const MODEL_GROUP = Object.freeze({
  CHAT: 'LLM',
  EMBEDDING: 'Embedding'
})

// 模型类别：仅前端使用，决定「模型」表单项展示聊天还是向量选项。
// 提交时映射为后端 model_type：CHAT(0)→1，EMBEDDING(1)→2
export const MODEL_CATEGORY = Object.freeze({
  CHAT: 0,
  EMBEDDING: 1
})

/** 后端 model_type：1=对话模型，2=向量模型 */
export const MODEL_TYPE = Object.freeze({
  CHAT: 1,
  EMBEDDING: 2
})

/** 模型类别下拉选项：列表筛选与卡片回显共用（name 供 formList 的 el-option 取用） */
export const MODEL_TYPE_OPTIONS = Object.freeze([
  { value: MODEL_TYPE.CHAT, name: '对话模型' },
  { value: MODEL_TYPE.EMBEDDING, name: '向量模型' }
])

/** 按 model_type 取中文名称，未知值返回 '--' */
export const getModelTypeLabel = (modelType) => {
  const item = MODEL_TYPE_OPTIONS.find((option) => option.value === Number(modelType))
  return item ? item.name : '--'
}

/** 思考模式：关闭 / 开启 / 跟随模型默认 */
export const THINKING_MODE = Object.freeze({
  OFF: 0,
  ON: 1,
  DEFAULT: 2
})

export const THINKING_MODE_OPTIONS = Object.freeze([
  { label: '跟随模型默认配置', value: THINKING_MODE.DEFAULT },
  { label: '开启', value: THINKING_MODE.ON },
  { label: '关闭', value: THINKING_MODE.OFF }
])

/** 高级配置默认值（与后端 AiModelEnum 对齐） */
export const ADVANCED_DEFAULTS = Object.freeze({
  input_tokens: 1048576,
  output_tokens: null,
  max_tool_rounds: 500,
  temperature: null,
  top_p: null,
  top_k: null
})

/** 输入上下文窗口快捷选项 */
export const INPUT_TOKEN_SHORTCUTS = Object.freeze([
  { label: '128K', value: 131072 },
  { label: '256K', value: 262144 },
  { label: '512K', value: 524288 },
  { label: '1M', value: 1048576 }
])

/** 输出 Token 快捷选项 */
export const OUTPUT_TOKEN_SHORTCUTS = Object.freeze([
  { label: '4K', value: 4096 },
  { label: '16K', value: 16384 },
  { label: '32K', value: 32768 },
  { label: '128K', value: 131072 }
])
