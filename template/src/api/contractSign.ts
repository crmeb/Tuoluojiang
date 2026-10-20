import request from './request'
import type { Id, ReqData } from '@/types/api'
/**
 *获取合同签约列表
 */
export function getContractDocListApi(data?: ReqData) {
  return request.get(`client/contract_doc`,data)
}
/**
 *新增合同签约接口
 */
export function contractDocSaveApi(data?: ReqData) {
  return request.post(`client/contract_doc`,data)
}
/**
 *修改合同签约接口
 */
export function contractDocPutApi(id: Id, data?: ReqData) {
  return request.put(`client/contract_doc/${id}`,data)
}
/**
 *删除合同签约接口
 */
export function contractDocDelApi(id: Id) {
  return request.delete(`client/contract_doc/${id}`)
}
/**
 *签约流程节点接口
 */
export function contractProcessApi(data?: ReqData) {
  return request.post(`client/contract_doc/process`,data)
}
/**
 *获取合同签约详情
 */
export function contractDocDetailApi(id: Id, data?: ReqData) { 
  return request.get(`client/contract_doc/${id}/edit`,data)
}
/**
 *获取合同文件转换结果
 */
export function contractDocTaskApi(taskId: Id) { 
  return request.get(`client/contract_doc/task/${taskId}`)
}
/**
 *获取合同撤销
 */
export function contractDocCancelApi(id: Id) { 
  return request.get(`client/contract_doc/cancel/${id}`)
}
/**
 *获取关联签约订单
 */
export function contractDocOrdersApi(id: Id) { 
  return request.get(`client/contract_doc/orders/${id}`)
}
/**
 *关联合同订单
 */
export function contractLinkOrderApi(id: Id, data?: ReqData) { 
  return request.post(`client/contract_doc/link_order/${id}`,data)
}
/**
 *线下签约文件上传
 */
export function contractDocSignApi(id: Id, data?: ReqData) { 
  return request.post(`client/contract_doc/sign/${id}`,data)
}
/**
 *获取合同签约人
 */
export function contractSignatoryApi(id: Id) { 
  return request.get(`client/contract_doc/signatory/${id}`)
}