import request from './request'
/**
 *获取合同签约列表
 */
export function getContractDocListApi(data) {
  return request.get(`client/contract_doc`,data)
}
/**
 *新增合同签约接口
 */
export function contractDocSaveApi(data) {
  return request.post(`client/contract_doc`,data)
}
/**
 *修改合同签约接口
 */
export function contractDocPutApi(id,data) {
  return request.put(`client/contract_doc/${id}`,data)
}
/**
 *删除合同签约接口
 */
export function contractDocDelApi(id) {
  return request.delete(`client/contract_doc/${id}`)
}
/**
 *签约流程节点接口
 */
export function contractProcessApi(data) {
  return request.post(`client/contract_doc/process`,data)
}
/**
 *获取合同签约详情
 */
export function contractDocDetailApi(id,data) { 
  return request.get(`client/contract_doc/${id}/edit`,data)
}
/**
 *获取合同文件转换结果
 */
export function contractDocTaskApi(taskId) { 
  return request.get(`client/contract_doc/task/${taskId}`)
}
/**
 *获取合同撤销
 */
export function contractDocCancelApi(id) { 
  return request.get(`client/contract_doc/cancel/${id}`)
}
/**
 *获取关联签约订单
 */
export function contractDocOrdersApi(id,) { 
  return request.get(`client/contract_doc/orders/${id}`)
}
/**
 *关联合同订单
 */
export function contractLinkOrderApi(id,data) { 
  return request.post(`client/contract_doc/link_order/${id}`,data)
}
/**
 *线下签约文件上传
 */
export function contractDocSignApi(id,data) { 
  return request.post(`client/contract_doc/sign/${id}`,data)
}
/**
 *获取合同签约人
 */
export function contractSignatoryApi(id) { 
  return request.get(`client/contract_doc/signatory/${id}`)
}