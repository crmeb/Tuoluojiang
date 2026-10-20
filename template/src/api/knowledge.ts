import request from '@/api/request'
import type { Id, ReqData } from '@/types/api'
//todo 知识库相关接口（V2.6 ent/knowledge）

/**
 * 知识库--列表
 * @return {*}
 */
export function knowledgeBaseListApi(data?: ReqData) {
  return request.get(`knowledge/base`, data)
}

/**
 * 知识库--新增
 * @return {*}
 */
export function knowledgeBaseCreateApi(data?: ReqData) {
  return request.post(`knowledge/base`, data)
}

/**
 * 知识库--详情
 * @return {*}
 */
export function knowledgeBaseDetailApi(id: Id) {
  return request.get(`knowledge/base/${id}`)
}

/**
 * 知识库--修改
 * @return {*}
 */
export function knowledgeBaseUpdateApi(id: Id, data?: ReqData) {
  return request.put(`knowledge/base/${id}`, data)
}

/**
 * 知识库--删除
 * @return {*}
 */
export function knowledgeBaseDeleteApi(id: Id) {
  return request.delete(`knowledge/base/${id}`)
}

/**
 * 知识库--修改状态
 * @return {*}
 */
export function knowledgeBaseStatusApi(id: Id, data?: ReqData) {
  return request.get(`knowledge/base/${id}/status`, data)
}

/**
 * 知识库--文档列表
 * @return {*}
 */
export function knowledgeDocumentListApi(data?: ReqData) {
  return request.get(`knowledge/document`, data)
}

/**
 * 知识库--上传文档
 * @return {*}
 */
export function knowledgeDocumentUploadApi(data?: ReqData) {
  return request.post(`knowledge/document/upload`, data, { allowRepeat: true })
}

/**
 * 知识库--文档详情
 * @return {*}
 */
export function knowledgeDocumentDetailApi(id: Id) {
  return request.get(`knowledge/document/${id}`)
}

/**
 * 知识库--批量启停文档
 * @return {*}
 */
export function knowledgeDocumentBatchEnabledApi(data?: ReqData) {
  return request.put(`knowledge/document/batch/enabled`, data)
}

/**
 * 知识库--批量删除文档
 * @return {*}
 */
export function knowledgeDocumentBatchDeleteApi(data?: ReqData) {
  return request.delete(`knowledge/document/batch`, data)
}

/**
 * 知识库--重新处理文档
 * @return {*}
 */
export function knowledgeDocumentReprocessApi(id: Id) {
  return request.post(`knowledge/document/${id}/reprocess`)
}

/**
 * 知识库--文档分块列表
 * @return {*}
 */
export function knowledgeDocumentChunksApi(id: Id, data?: ReqData) {
  return request.get(`knowledge/document/${id}/chunks`, data)
}

/**
 * 知识库--修改文档分块
 * @return {*}
 */
export function knowledgeChunkUpdateApi(id: Id, data?: ReqData) {
  return request.put(`knowledge/chunk/${id}`, data)
}

/**
 * 知识库--新增文档手动分块
 * @return {*}
 */
export function knowledgeChunkCreateApi(data?: ReqData) {
  return request.post(`knowledge/chunk`, data)
}

/**
 * 知识库--删除文档分块
 * @return {*}
 */
export function knowledgeChunkDeleteApi(id: Id) {
  return request.delete(`knowledge/chunk/${id}`)
}

/**
 * 知识库--启停文档分块
 * @return {*}
 */
export function knowledgeChunkEnabledApi(id: Id, data?: ReqData) {
  return request.put(`knowledge/chunk/${id}/enabled`, data)
}

/**
 * 知识库--手动向量化文档分块
 * @return {*}
 */
export function knowledgeChunkEmbedApi(id: Id) {
  return request.post(`knowledge/chunk/${id}/embed`)
}

/**
 * 知识库--问答
 * @return {*}
 */
export function knowledgeQuestionApi(data?: ReqData) {
  return request.post(`knowledge/question`, data, { allowRepeat: true })
}
