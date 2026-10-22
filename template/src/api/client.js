import request from '@/api/request'
//todo 客户管理相关接口

/**
 * 客户管理--客户管理--企业设置--保存
 * @return {*}
 */
export function clientConfigSaveApi(data) {
  return request.post(`client/config/save`, data)
}
/**
 * 客户管理--线索表单
 * @return {*}
 */
export function getCluesCeartepApi() {
  return request.get(`client/clues/create`)
}
/**
 * 客户管理--线索-删除
 * @return {*}
 */
export function delcluesApi(id) {
  return request.delete(`client/clues/${id}`)
}
/**
 * 客户管理--线索-批量删除
 * @param {{ data: number[] }} data 线索ID列表
 * @return {*}
 */
export function batchDelCluesApi(data) {
  return request.post(`client/clues/batch_delete`, data)
}
/**
 * 客户管理--线索-保存表单
 * @return {*}
 */
export function savecluesApi(data) {
  return request.post(`client/clues`, data)
}
/**
 * 客户管理--线索-修改表单
 * @return {*}
 */
export function savecluesEditApi(id, data) {
  return request.put(`client/clues/${id}`, data)
}
/**
 * 客户管理--客户详情——少量字段
 * @return {*}
 */
export function clientCustomerBaseApi(id) {
  return request.get(`client/customer/base/${id}`)
}

/**
 * 客户管理--线索-修改表单内容获取
 * @return {*}
 */
export function getCluesEditApi(id, data = {}) {
  return request.get(`client/clues/${id}/edit`, data)
}


/**
 * 客户管理--V1.4保存客户表单
 * @return {*}
 */
export function clientCustomerSaveApi(data) {
  return request.post(`client/customer`, data)
}


/**
 * 客户管理--客户列表--删除客户信息
 * @return {*}
 */
export function clientDataDeleteApi(id) {
  return request.delete(`client/customer/${id}`)
}


/**
 * 客户管理--客户列表--客户导入
 * @return {*}
 */
export function clientImportApi(types, data) {
  return request.post(`system/data/import/${types}`, data)
}
/**
 * 客户管理--客户列表--获取导入模板
 * @return {*}
 */
export function clientImportTemplateApi(types) {
  return request.get(`system/data/template/${types}`)
}
/**
 * 客户管理--客户列表--数据导出
 * @return {*}
 */
export function clientExportApi(types, data) {
  return request.post(`system/data/export/${types}`, data)
}
/**
 * 客户管理--客户列表--导出导入记录
 * @return {*}
 */
export function clientExportRecordApi(data) {
  return request.get(`system/data/record`, data)
}
/**
 * 客户管理--客户列表--删除导出导入记录
 * @return {*}
 */
export function clientExportRecordDeleteApi(id) {
  return request.delete(`system/data/delete/${id}`)
}


/**
 * 客户管理--客户列表--批量设置标签
 * @return {*}
 */
export function clientDataLabelApi(data) {
  return request.post(`client/customer/label`, data)
}
/**
 * 客户管理--客户列表--编辑标签
 * @return {*}
 */
export function clientSaveLabelsApi(data) {
  return request.post(`client/labels/save_labels`, data)
}

/**
 * 客户管理--客户列表--客户详情
 * @return {*}
 */
export function clientDataInfoApi(id) {
  return request.get(`client/data/${id}`)
}

/**
 * 客户管理--客户列表--保存联系人
 * @return {*}
 */
export function clientLiaisonSaveApi(data) {
  return request.post(`client/liaisons`, data)
}

/**
 * 客户管理--客户列表--修改联系人
 * @return {*}
 */
export function clientLiaisonEditApi(id, data) {
  return request.put(`client/liaisons/${id}`, data)
}

/**
 * 客户管理--客户列表--联系人列表
 * @return {*}
 */
export function clientLiaisonListApi(data) {
  return request.get(`client/liaisons`, data)
}

/**
 * 客户管理--客户列表--删除联系人
 * @return {*}
 */
export function clientLiaisonDeleteApi(id) {
  return request.delete(`client/liaisons/${id}`)
}

/**
 * 客户管理--订单列表--添加订单
 * @return {*}
 */
export function clientContractSaveApi(data) {
  return request.post(`client/contracts`, data)
}

/**
 * 客户管理--订单列表--修改订单
 * @return {*}
 */
export function clientContractEditApi(id, data) {
  return request.put(`client/contracts/${id}`, data)
}
/**
 * 客户管理--订单列表--撤回付款与续费记录
 * @return {*}
 */
export function clientBillPutApi(id) {
  return request.put(`client/bill/withdraw/${id}`)
}

/**
 * 客户管理--订单列表--填写备注信息
 * @return {*}
 */
export function clientMarkApi(id, data) {
  return request.put(`client/bill/mark/${id}`, data)
}

/**
 * 客户管理--订单列表--付款提醒--填写备注信息
 * @return {*}
 */
export function clientRemindMarkApi(id, data) {
  return request.put(`client/remind/mark/${id}`, data)
}

/**
 * 财务审核--资金流水审核
 * @return {*}
 */
export function clientBillStatusApi(id, data) {
  return request.post(`client/bill/status/${id}`, data)
}

/**
 * 发票管理--发票申请列表
 * @return {*}
 */
export function clientInvoiceListApi(data) {
  return request.get(`client/invoice`, data)
}

/**
 * 发票管理--保存发票申请
 * @return {*}
 */
export function clientInvoiceSaveApi(data) {
  return request.post(`client/invoice`, data)
}

/**
 * 发票管理--修改发票申请
 * @return {*}
 */
export function clientInvoicePutApi(invoice, data) {
  return request.put(`client/invoice/${invoice}`, data)
}

/**
 * 发票管理--获取在线开票uri
 * @param invoice
 * @return {*}
 */
export function clientInvoiceUriApi(invoice) {
  return request.get(`client/invoice/uri/${invoice}`)
}

/**
 * 发票管理--开票撤回
 * @return {*}
 */
export function recallStatus(id, data) {
  return request.put(`client/invoice/withdraw/${id}`, data)
}
/**
 * 发票管理--申请冲红
 * @return {*}
 */
export function invalidApply(id, data) {
  return request.put(`client/invoice/invalid_apply/${id}`, data)
}

/**
 * 发票管理--发票删除
 * @return {*}
 */
export function clientInvoiceDeleteApi(id) {
  return request.delete(`client/invoice/${id}`)
}

/**
 * 发票管理--发票填写备注
 * @return {*}
 */
export function clientInvoiceMarkApi(id, data) {
  return request.put(`client/invoice/mark/${id}`, data)
}

/**
 * 发票管理--发票编辑
 * @return {*}
 */
export function clientInvoiceEditApi(id, data) {
  return request.put(`client/invoice/${id}`, data)
}

/**
 * 发票管理--发票审核与开票
 * @return {*} client/follow
 */
export function clientInvoiceStatusApi(id, data) {
  return request.post(`client/invoice/status/${id}`, data)
}
/**
 * 发票管理--发票审核与开票
 * @return {*} client/follow
 */
export function clientInvoiceStatusPutApi(id, data) {
  return request.put(`client/invoice/status/${id}`, data)
}

export function clientInvoiceStatus(id, data) {
  return request.put(`client/invoice/invalid_review/${id}`, data)
}

/**
 * 客户管理--跟进记录--保存客户提醒与跟进详情
 * @return {*}
 */
export function clientFollowSaveApi(data) {
  return request.post(`client/follow`, data)
}

/**
 * 客户管理--跟进记录--修改客户提醒与跟进详情
 * @return {*}
 */
export function clientFollowEditApi(id, data) {
  return request.put(`client/follow/${id}`, data)
}



/**
 * 客户管理--跟进记录--删除客户跟进记录附件
 * @return {*}
 */
export function clientFileDeleteApi(id) {
  return request.delete(`client/file/delete/${id}`)
}

/**
 * 客户管理--附件相关-附件列表
 * @return {*}
 */
export function clientFileListApi(data) {
  return request.get(`client/file/index`, data)
}
/**
 * 订单管理--附件相关-资料列表
 * @return {*}
 */
export function contracFileListApi(data) {
  return request.get(`client/resources`, data)
}
/**
 * 订单管理--附件相关-保存资料
 * @return {*}
 */
export function contracFileSaveApi(data) {
  return request.post(`client/resources`, data)
}
/**
 * 订单管理--附件相关-删除资料
 * @return {*}
 */
export function contracFileDeleteApi(id) {
  return request.delete(`client/resources/${id}`)
}
/**
 * 订单管理--附件相关-编辑订单资料
 * @return {*}
 */
export function contracFileEditApi(id, data) {
  return request.put(`client/resources/${id}`, data)
}

/**
 * 订单管理--付款提醒保存
 * @return {*}
 */
export function clientRemindSaveApi(data) {
  return request.post(`client/remind`, data)
}

/**
 * 订单管理--付款提醒编辑
 * @return {*}
 */
export function clientRemindEditApi(id, data) {
  return request.put(`client/remind/${id}`, data)
}

/**
 * 订单管理--付款提醒删除
 * @return {*}
 */
export function clientRemindDeleteApi(id) {
  return request.delete(`client/remind/${id}`)
}

/**
 * 订单管理--付款提醒列表
 * @return {*}
 */
export function clientRemindListApi(data) {
  return request.get(`client/remind`, data)
}

/**
 * 订单管理--批量设置转移
 * @return {*}
 */
export function clientContractShiftApi(data) {
  return request.post(`client/contracts/shift`, data)
}
/**
 * 客户导入
 * @return {*}
 */
export function importExcel(data) {
  return request.post(`client/import`, data)
}

/**
 * 申请冲红
 * @return {*}
 */
export function putInvoice(id, data) {
  return request.put(`client/invoice/invalid_apply/${id}`, data)
}
/**
 * 申请冲红
 * @return {*}
 */
export function putInvalid(id, data) {
  return request.put(`client/invoice/invalid_review/${id}`, data)
}
/**
 * 文件重命名
 * @return {*}
 */
export function putRealName(id, data) {
  return request.put(`client/file/real_name/${id}`, data)
}
/**
 获取付款记录详情接口
 * @param {Number} id
 * @returns {*}
 */
export function clientBillDetailApi(id) {
  return request.get(`client/bill/${id}`)
}
/**
 获取发票详情接口
 * @param {Number} id
 * @returns {*}
 */
export function clientInvoiceDetailApi(id) {
  return request.get(`client/invoice/info/${id}`)
}

/**
 获取客户订单详情接口
 * @param {Number} id
 * @returns {*}
 */
export function clientContractDetailApi(id) {
  return request.get(`client/contracts/info/${id}`)
}

/**
 * 获取客户名称接口
 * @param {Number} id
 * @returns {*}
 */
export function clientNameApi() {
  return request.get(`client/data/select`)
}

/**
 * 获取业务员字段数据接口
 * @param {string} custom_type
 * @returns {*}
 */
export function salesmanCustomApi(custom_type) {
  return request.get(`config/form/data/fields/${custom_type}`)
}

/**
 * 保存业务员字段数据接口
 * @return {*}
 */
export function saveSalesmanCustomApi(custom_type, data) {
  return request.put(`config/form/data/fields/${custom_type}`, data)
}

/**
 * 获取视图列表
 * @return {*}
 */
export function getViewSeachApi(data) {
  return request.get(`config/view_search`, data)
}

/**
 * 视图保存
 * @return {*}
 */
export function saveViewSeachApi(data) {
  return request.post(`config/view_search`, data)
}


/**
 * 编辑视图
 * @return {*}
 */
export function putViewSeachInfoApi(id, data) {
  return request.put(`config/view_search/${id}`, data)
}

/**
 * 删除视图
 * @return {*}
 */
export function delViewSeachApi(id) {
  return request.delete(`config/view_search/${id}`)
}

/**
 * 视图排序
 * @return {*}
 */
export function viewSeachSortApi(data) {
  return request.post(`config/view_search/sort`, data)
}

/**
 * 产品分类列表
 * @return {*}
 */
export function productCateListApi(data) {
  return request.get(`client/product/cate`, data)
}

/**
 * 添加产品分类
 * @return {*}
 */
export function productCateCreateApi(data) {
  return request.get(`client/product/cate/create`, data)
}

/**
 * 修改产品分类
 * @return {*}
 */
export function productCateEditApi(id) {
  return request.get(`client/product/cate/${id}/edit`)
}

/**
 * 删除 产品分类
 * @return {*}
 */
export function productCateDelApi(id) {
  return request.delete(`client/product/cate/${id}`)
}

/**
 * 删除 产品分类
 * @return {*}
 */
export function productCateApi(id, data) {
  return request.get(`client/product/cate/${id}`, data)
}

/**
 * 获取添加商品表单
 * @return {*}
 */
export function productCreateApi() {
  return request.get(`client/products/create`)
}

/**
 * 保存商品表单
 * @return {*}
 */
export function productSaveApi(data) {
  return request.post(`client/products`, data)
}

/**
 * 获取产品详情
 * @return {*}
 */
export function productInfoApi(id) {
  return request.get(`client/products/${id}/edit`)
}

/**
 * 获取产品详情
 * @return {*}
 */
export function productListApi(data) {
  return request.post(`client/products/list`, data)
}

/**
 * 编辑产品
 * @return {*}
 */
export function putProductApi(id, data) {
  return request.put(`client/products/${id}`, data)
}

/**
 * 删除产品
 * @return {*}
 */
export function productDelApi(id) {
  return request.delete(`client/products/${id}`)
}

/**
 * 产品详情
 * @return {*}
 */
export function getProductInfoApi(id) {
  return request.get(`client/products/info/${id}`)
}

/**
 * 商机列表
 * @return {*}
 */
export function oddsListApi(data) {
  return request.post(`client/odds/list`, data)
}

/**
 * 获取添加商机表单
 * @return {*}
 */
export function oddsCreateApi(data) {
  return request.get(`client/odds/create`, data)
}
/**
 * 获取修改商机表单
 * @return {*}
 */
export function oddsCreateEditApi(id) {
  return request.get(`client/odds/${id}/edit`)
}

/**
 * 保存商机表单
 * @return {*}
 */
export function oddsSaveApi(data) {
  return request.post(`client/odds`, data)
}

/**
 * 修改商机表单
 * @return {*}
 */
export function oddsEditApi(id, data) {
  return request.put(`client/odds/${id}`, data)
}

/**
 * 修改商机失效
 * @return {*}
 */
export function oddsStatusApi(id, status) {
  return request.post(`client/odds/status/${id}/${status}`)
}
/**
 * 商机移交同事
 * @return {*}
 */
export function oddsShiftApi(data) {
  return request.post(`client/odds/shift`, data)
}

/**
 * 删除商机
 * @return {*}
 */
export function oddsDelApi(id) {
  return request.delete(`client/odds/${id}`)
}

/**
 * 获取产品规格列表
 * @return {*}
 */
export function getProductsAttrsApi(data) {
  return request.get(`client/products/attrs`, data)
}

/**
 * 获取跟进记录列表
 * @return {*}
 */
export function getClientFollowApi(data) {
  return request.get(`client/follow`, data)
}

/**
 * 保存跟进记录
 * @return {*}
 */
export function saveClientFollowApi(data) {
  return request.post(`client/follow`, data)
}

/**
 * 修改跟进记录
 * @return {*}
 */
export function putClientFollowApi(follow, data) {
  return request.put(`client/follow/${follow}`, data)
}

/**
 * 删除跟进记录
 * @return {*}
 */
export function delClientFollowApi(follow) {
  return request.delete(`client/follow/${follow}`)
}

/**
 * 商机关注
 * @return {*}
 */
export function oddsSubscribeApi(id, status, data) {
  return request.post(`client/odds/subscribe/${id}/${status}`, data)
}

/**
 * 线索关注
 * @return {*}
 */
export function cluesSubscribeApi(id, status, data) {
  return request.post(`client/clues/subscribe/${id}/${status}`, data)
}

/**
 * 线索转客户
 * @return {*}
 */
export function configConvertApi(types, data) {
  return request.put(`config/form/data/convert/${types}`, data)
}

/**
 * 业绩目标列表
 * @return {*}
 */
export function clientTargetsApi(data) {
  return request.get(`client/targets`, data)
}

/**
 * 保存业绩目标列表
 * @return {*}
 */
export function clientTargetPutApi(data) {
  return request.put(`client/targets`, data)
}

/**
 * 删除业绩目标列表
 * @return {*}
 */
export function clientTargetDelApi(data) {
  return request.delete(`client/targets`, data)
}

/**
 * 获取业绩目标完成度列表
 * @return {*}
 */
export function clientTargetRateApi(data) {
  return request.get(`client/targets/rate`, data)
}

/**
 * 获取业绩目标统计数据
 * @return {*}
 */
export function clientTargetCensusApi(data) {
  return request.get(`client/targets/census`, data)
}

/**
 * 企微标签同步
 * @return {*}
 */
export function clientWorkLabelApi() {
  return request.get(`client/labels/auth_work_client_label`)
}
/**
 * 客户标签排序
 * @return {*}
 */
export function clientSortLabelsApi(data) {
  return request.post(`client/labels/sort_labels`, data)
}

/**
 * 同步企业客户
 * @return {*}
 */
export function clientWorkSyncApi() {
  return request.get(`work/client/sync`)
}

/**
 * 获取线索
 * @return {*}
 */
export function clientCluesSearchApi(data) {
  return request.post(`client/clues/search`, data)
}
/**
 * 合并客户
 * @return {*}
 */
export function clientCluesMergeApi(data) {
  return request.post(`client/customer/merge`, data)
}
/**
 * 转客户
 * @return {*}
 */
export function clientToCustomerApi(id) {
  return request.put(`client/clues/to_customer/${id}`)
}
/**
 * 保存客户协作者
 * @return {*}
 */
export function clientMemberApi(id, data) {
  return request.post(`client/customer/member/${id}`, data)
}
