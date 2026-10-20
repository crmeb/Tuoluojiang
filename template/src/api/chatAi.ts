import request, { type RequestOptions } from '@/api/request'
import type { Id, ReqData } from '@/types/api'
//todo AI应用 / 模型 / MCP / 会话相关接口（V2.6 ent/ai）

/**
 * AI应用--新增
 * @return {*}
 */
export function chatSaveApplicationsApi(data?: ReqData) {
  return request.post(`ai/applications`, data)
}

/**
 * AI应用--快速新增
 *
 * 传 name 和模块固定的 app_type（0=内部、1=外部，省略默认内部），info / models_id 选填；
 * status、可使用与编辑成员由后端补默认值，
 * 列表页「创建应用」用它建壳子，其余配置进设置页再补。
 * @return {*} data.id 新建应用ID
 */
export function chatQuickSaveApplicationsApi(data?: ReqData) {
  return request.post(`ai/applications/quick`, data)
}

/**
 * AI应用--修改
 * @return {*}
 */
export function chatPutApplicationsApi(id: Id, data?: ReqData) {
  return request.put(`ai/applications/${id}`, data)
}

/**
 * AI应用--发布
 *
 * 把当前表单写成已发布配置并打开状态，不重置 share_key。
 * @return {*}
 */
export function chatPublishApplicationsApi(id: Id, data?: ReqData) {
  return request.put(`ai/applications/${id}/publish`, data)
}

/**
 * AI应用--修改状态（发布/启停）
 * @return {*}
 */
export function chatReleasesApplicationsApi(id: Id, data?: ReqData) {
  return request.get(`ai/applications/${id}/show`, data)
}

/**
 * AI应用--列表
 * @return {*}
 */
export function getApplicationsListApi(data?: ReqData) {
  return request.get(`ai/applications`, data)
}

/**
 * AI应用--删除
 * @return {*}
 */
export function delApplicationsApi(id: Id) {
  return request.delete(`ai/applications/${id}`)
}

/**
 * AI应用--详情
 * @return {*}
 */
export function getApplicationsInfoApi(id: Id) {
  return request.get(`ai/applications/${id}/edit`)
}

/**
 * AI应用--数据库提示文本
 * @return {*}
 */
export function getApplicationstoolTipApi(data?: ReqData) {
  return request.post(`ai/applications/database/tooltip`, data)
}

/**
 * AI应用--数据库列表
 * @return {*}
 */
export function getDatabesListApi(data?: ReqData) {
  return request.get(`ai/applications/databes/list`, data)
}

/**
 * AI应用--获取绑定知识库
 * @return {*}
 */
export function getApplicationKnowledgeApi(id: Id) {
  return request.get(`ai/applications/${id}/knowledge`)
}

/**
 * AI应用--绑定知识库
 * @return {*}
 */
export function bindApplicationKnowledgeApi(id: Id, data?: ReqData) {
  return request.post(`ai/applications/${id}/knowledge`, data)
}

/**
 * AI应用--解绑知识库
 * @return {*}
 */
export function unbindApplicationKnowledgeApi(id: Id, kbId: Id) {
  return request.delete(`ai/applications/${id}/knowledge/${kbId}`)
}

/**
 * AI MCP--服务列表
 * @return {*}
 */
export function getMcpListApi(data?: ReqData) {
  return request.get(`ai/mcp`, data)
}

/**
 * AI MCP--获取服务详情
 * @return {*}
 */
export function getMcpServiceApi(id: Id) {
  return request.get(`ai/mcp/${id}/edit`)
}

/**
 * AI MCP--修改服务
 * @return {*}
 */
export function putMcpServiceApi(id: Id, data?: ReqData) {
  return request.put(`ai/mcp/${id}`, data)
}

/**
 * AI MCP--删除服务
 * @return {*}
 */
export function delMcpServiceApi(id: Id) {
  return request.delete(`ai/mcp/${id}`)
}

/**
 * AI MCP--保存服务
 * @return {*}
 */
export function saveMcpServiceApi(data?: ReqData) {
  return request.post(`ai/mcp`, data)
}

/**
 * AI MCP--修改服务状态
 * @return {*}
 */
export function showMcpServiceApi(id: Id, data?: ReqData) {
  return request.get(`ai/mcp/${id}/show`, data)
}

/**
 * AI MCP--测试连接
 * @return {*}
 */
export function testMcpConnectionApi(data?: ReqData) {
  return request.post(`ai/mcp/test-connection`, data)
}

/**
 * AI MCP--获取工具列表
 * @return {*}
 */
export function getMcpToolsApi(id: Id) {
  return request.get(`ai/mcp/${id}/tools`)
}

/**
 * AI模型--启用下拉
 * @return {*}
 */
export function getModelsSelectListApi() {
  return request.get(`ai/models/list`)
}

/**
 * AI模型--配置列表
 * @return {*}
 */
export function getModelsListApi(data?: ReqData) {
  return request.get(`ai/models`, data)
}

/**
 * AI模型--新增
 * @return {*}
 */
export function saveModelsApi(data?: ReqData) {
  return request.post(`ai/models`, data)
}

/**
 * AI模型--详情
 * @return {*}
 */
export function getModelsInfoApi(id: Id) {
  return request.get(`ai/models/${id}/edit`)
}

/**
 * AI模型--删除
 * @return {*}
 */
export function delModelsApi(id: Id) {
  return request.delete(`ai/models/${id}`)
}

/**
 * AI模型--修改
 * @return {*}
 */
export function editModelsApi(id: Id, data?: ReqData) {
  return request.put(`ai/models/${id}`, data)
}

/**
 * AI模型--修改状态
 * @return {*}
 */
export function showModelsApi(id: Id, data?: ReqData) {
  return request.get(`ai/models/${id}/show`, data)
}

/**
 * AI模型--供应商选项
 * @return {*}
 */
export function getModelsOptionsApi() {
  return request.get(`ai/models/options`)
}

/**
 * AI模型--从预设供应商获取模型列表
 *
 * 调用供应商 OpenAI 兼容 /models 接口拉取远程模型，失败或为空时后端回退到本地预设模型。
 * @param {Object} data
 * @param {String} data.provider 模型供应商（原 driver）
 * @param {String} data.api_key  API Key（必填）
 * @param {String} [data.base_url] 基础地址，自定义配置（openai_compatible）必填
 * @return {*}
 */
export function fetchModelsApi(data?: ReqData) {
  return request.post(`ai/models/fetch`, data)
}

/**
 * AI模型--测试连接
 *
 * @param {Object} data    模型配置，字段与新增/编辑一致（含 provider / model_id / model_type）
 * @param {Object} options 请求选项，传 { silent: true } 时错误提示交由调用方自行展示
 * @return {*}
 */
export function testModelsApi(data?: ReqData, options: RequestOptions = {}) {
  return request.post(`ai/models/test`, data, options)
}

/**
 * AI会话--列表
 * @return {*}
 */
export function getConversationsListApi(data?: ReqData) {
  return request.get(`ai/conversations`, data)
}

/**
 * AI会话--创建
 * @return {*}
 */
export function createConversationApi(data?: ReqData) {
  return request.post(`ai/conversations`, data)
}

/**
 * AI会话--更新
 * @return {*}
 */
export function updateConversationApi(id: Id, data?: ReqData) {
  return request.put(`ai/conversations/${id}`, data)
}

/**
 * AI会话--删除
 * @return {*}
 */
export function deleteConversationApi(id: Id) {
  return request.delete(`ai/conversations/${id}`)
}

/**
 * AI会话--消息记录
 * @return {*}
 */
export function getConversationRecordsApi(id: Id, data?: ReqData) {
  return request.get(`ai/conversations/${id}/records`, data)
}

/**
 * AI会话--清空消息记录
 * @return {*}
 */
export function clearConversationRecordsApi(id: Id) {
  return request.delete(`ai/conversations/${id}/records`)
}

/**
 * AI会话--SQL记录结果
 * @return {*}
 */
export function getConversationRecordByUuidApi(uuid: Id) {
  return request.get(`ai/conversations/records/${uuid}`)
}

/**
 * AI会话--流式对话
 * @return {*}
 */
export function streamConversationApi(id: Id, data?: ReqData) {
  return request.post(`ai/conversations/${id}/stream`, data, { allowRepeat: true })
}

/**
 * AI会话--重新生成回答
 * @return {*}
 */
export function regenerateConversationApi(id: Id, data?: ReqData) {
  return request.post(`ai/conversations/${id}/regenerate`, data, { allowRepeat: true })
}

/**
 * AI会话--中断流式对话
 * @return {*}
 */
export function interruptConversationApi(id: Id, data?: ReqData) {
  return request.post(`ai/conversations/${id}/interrupt`, data, { allowRepeat: true })
}
