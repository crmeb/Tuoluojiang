import request from '@/api/request'

/**
 * 入库列表
 * @param {Object} params 透传给后端的查询参数
 */
export function inboundListApi(params) {
  return request.get(`warehouse/inbound`, params);
}

/**
 * 入库详情
 * @param {Number} id 入库单 ID
 */
export function inboundDetailApi(id) {
  return request.get(`warehouse/inbound/${id}`);
}

/**
 * 执行入库（待入库 -> 已入库）
 */
export function inboundDoApi(id) {
  return request.post(`warehouse/inbound/confirm/${id}`);
}

/**
 * 入库作废
 * @param {Number} id 入库单 ID
 */
export function inboundCancelApi(id) {
  return request.post(`warehouse/inbound/cancel/${id}`);
}

/**
 * 新建入库单
 * @param {Object} data 入库单表单数据（warehouse_id / inbound_type / source_no / remark / details）
 */
export function inboundCreateApi(data) {
  return request.post('warehouse/inbound', data)
}

/**
 * 仓库列表
 * @param {Object} params { page, limit, name, code, status }
 */
export function warehouseListApi(params) {
  return request.get('warehouse/base', params)
}

/**
 * 可查看仓库列表（当前登录人有权限的仓库及各业务动作权限）
 */
export function warehouseAccessibleApi() {
  return request.get('warehouse/base/accessible')
}

/**
 * 仓库详情
 * @param {Number} id
 */
export function warehouseDetailApi(id) {
  return request.get(`warehouse/base/${id}`)
}

/**
 * 新增仓库
 * @param {Object} data { name, contact, phone, address, status, sort, remark }
 */
export function warehouseCreateApi(data) {
  return request.post('warehouse/base', data)
}

/**
 * 编辑仓库
 * @param {Number} id
 * @param {Object} data { name, contact, phone, address, status, sort, remark }
 */
export function warehouseUpdateApi(id, data) {
  return request.put(`warehouse/base/${id}`, data)
}

/**
 * 启停仓库
 * @param {Number} id
 * @param {Number} status 0 停用 / 1 启用
 */
export function warehouseStatusUpdateApi(id, status) {
  return request.put(`warehouse/base/status/${id}`, { status })
}

/**
 * 删除仓库
 * @param {Number} id
 */
export function warehouseDeleteApi(id) {
  return request.delete(`warehouse/base/${id}`)
}


/**
 * 库管列表
 * @param {Object} params { page, limit, name, status }
 */
export function keeperListApi(params) {
  return request.get('warehouse/manager', params)
}

/**
 * 库管详情
 * @param {Number} id
 */
export function keeperDetailApi(id) {
  return request.get(`warehouse/manager/${id}`)
}

/**
 * 新增库管
 * @param {Object} data { name, manager_type, can_view_other, status, remark, user_ids, warehouse_ids, warehouse_permissions }
 */
export function keeperCreateApi(data) {
  return request.post('warehouse/manager', data)
}

/**
 * 编辑库管
 * @param {Number} id
 * @param {Object} data { name, manager_type, can_view_other, status, remark, user_ids, warehouse_ids, warehouse_permissions }
 */
export function keeperUpdateApi(id, data) {
  return request.put(`warehouse/manager/${id}`, data)
}

/**
 * 启停库管
 * @param {Number} id
 * @param {Number} status 0 停用 / 1 启用
 */
export function keeperStatusUpdateApi(id, status) {
  return request.put(`warehouse/manager/status/${id}`, { status })
}

/**
 * 删除库管
 * @param {Number} id
 */
export function keeperDeleteApi(id) {
  return request.delete(`warehouse/manager/${id}`)
}

/**
 * 出库列表
 * @param {Object} params 透传给后端的查询参数
 */
export function outboundListApi(params) {
  return request.get('warehouse/outbound', params)
}

/**
 * 出库详情
 * @param {Number} id 出库单 ID
 */
export function outboundDetailApi(id) {
  return request.get(`warehouse/outbound/${id}`)
}

/**
 * 新建出库单
 * @param {Object} data 出库单表单数据（warehouse_id / outbound_type / source_id / remark / details）
 */
export function outboundCreateApi(data) {
  return request.post('warehouse/outbound', data)
}

/**
 * 拣货出库（待出库 -> 已出库）
 * @param {Number} id 出库单 ID
 */
export function outboundPickApi(id) {
  return request.post(`warehouse/outbound/pick/${id}`)
}

/**
 * 出库作废
 * @param {Number} id 出库单 ID
 */
export function outboundCancelApi(id) {
  return request.post(`warehouse/outbound/cancel/${id}`)
}

/**
 * 调拨列表
 * @param {Object} params { page, limit, warehouse_ids, status, bill_no, created_at }
 */
export function transferListApi(params) {
  return request.get('warehouse/transfer', params)
}

/**
 * 调拨详情
 * @param {Number} id 调拨单 ID
 */
export function transferDetailApi(id) {
  return request.get(`warehouse/transfer/${id}`)
}

/**
 * 新建调拨单
 * @param {Object} data 调拨单表单数据（out_warehouse_id / in_warehouse_id / transfer_type / remark / details）
 */
export function transferCreateApi(data) {
  return request.post('warehouse/transfer', data)
}

/**
 * 调拨作废
 * @param {Number} id 调拨单 ID
 */
export function transferCancelApi(id) {
  return request.post(`warehouse/transfer/cancel/${id}`)
}

/**
 * 调拨出库（物流调拨：调出仓库出库）
 * @param {Number} id 调拨单 ID
 */
export function transferOutApi(id) {
  return request.post(`warehouse/transfer/out/${id}`)
}

/**
 * 调拨入库（物流调拨：调入仓库收货入库）
 * @param {Number} id 调拨单 ID
 */
export function transferInApi(id) {
  return request.post(`warehouse/transfer/in/${id}`)
}

/**
 * 调拨重新提交（已作废调拨单修改后重新提交，请求体与新建调拨一致）
 * @param {Number} id 调拨单 ID
 * @param {Object} data 调拨单表单数据（out_warehouse_id / in_warehouse_id / transfer_type / remark / details）
 */
export function transferResubmitApi(id, data) {
  return request.post(`warehouse/transfer/resubmit/${id}`, data)
}

/**
 * 盘点列表
 * @param {Object} params { page, limit, warehouse_id, status, bill_no, product_keyword, time }
 */
export function checkListApi(params) {
  return request.get('warehouse/check', params)
}

/**
 * 盘点详情
 * @param {Number} id 盘点单 ID
 */
export function checkDetailApi(id) {
  return request.get(`warehouse/check/${id}`)
}

/**
 * 新建盘点单
 * is_draft 为 true 或不传时保存草稿（盘点中，不处理库存）；为 false 时直接完成盘点并处理库存差异
 * @param {Object} data 盘点单表单数据（warehouse_id / remark / images / attach_ids / is_draft / details）
 */
export function checkCreateApi(data) {
  return request.post('warehouse/check', data)
}

/**
 * 继续盘点（编辑盘点单并继续保存盘点结果，请求体与新增盘点一致）
 * @param {Number} id 盘点单 ID
 * @param {Object} data 盘点单表单数据（warehouse_id / remark / images / attach_ids / is_draft / details）
 */
export function checkContinueApi(id, data) {
  return request.put(`warehouse/check/continue/${id}`, data)
}

/**
 * 盘点作废（仅盘点中的盘点单允许作废）
 * @param {Number} id 盘点单 ID
 */
export function checkCancelApi(id) {
  return request.put(`warehouse/check/cancel/${id}`)
}

/**
 * 盘点备注
 * @param {Number} id 盘点单 ID
 * @param {Object} data { remark }
 */
export function checkRemarkUpdateApi(id, data) {
  return request.put(`warehouse/check/remark/${id}`, data)
}

/**
 * SKU 仓库库存列表（盘点选品时用于按仓库回填良品 / 残次品账面库存）
 * @param {Object} params { page, limit, warehouse_id, sku_id, keyword, ... }
 */
export function warehouseStockListApi(params) {
  return request.get('warehouse/stock/list', params)
}

/**
 * 库存数量统计（各状态页签数量：全部 / 偏低 / 偏高 / 已售罄）
 * @param {Object} params { warehouse_id, keyword, category_id, has_occupy, has_transit, ... } 与列表共享筛选条件，不含 stock_status
 * @returns {Promise<{ data: { all: number, low: number, high: number, sold_out: number } }>}
 */
export function warehouseStockStatisticsApi(params) {
  return request.get('warehouse/stock/statistics', params)
}


/**
 * 库存台账（SKU 在指定仓库的库存变动流水）
 * @param {Object} params { page, limit, warehouse_id, product_id, sku_id, flow_type, source_no, created_at }
 */
export function stockLedgerApi(params) {
  return request.get('warehouse/stock/ledger', params)
}

/**
 * 库存上下限列表
 * @param {Object} params { page, limit, warehouse_id, product_id, sku_id, category_id, brand, keyword, stock_status }
 */
export function stockLimitListApi(params) {
  return request.get('warehouse/stock/limit/list', params)
}

/**
 * 设置库存上下限（按库存记录 ID，仓库与 SKU 由记录推导）
 * @param {Object} data { id, low_limit, high_limit }
 */
export function stockLimitSetApi(data) {
  return request.put('warehouse/stock/limit', data)
}

/**
 * 批量设置库存上下限（按库存记录 ID 数组）
 * @param {Object} data { ids, low_limit, high_limit }
 */
export function stockLimitBatchSetApi(data) {
  return request.put('warehouse/stock/limit/batch', data)
}

/**
 * 正次转换列表
 * @param {Object} params { page, limit, warehouse_id, convert_type, status, bill_no, created_at }
 */
export function convertListApi(params) {
  return request.get('warehouse/convert', params)
}

/**
 * 正次转换详情
 * @param {Number} id 转换单 ID
 */
export function convertDetailApi(id) {
  return request.get(`warehouse/convert/${id}`)
}

/**
 * 新建正次转换单
 * @param {Object} data 转换单表单数据（warehouse_id / convert_type / images / attach_ids / remark / details）
 */
export function convertCreateApi(data) {
  return request.post('warehouse/convert', data)
}

/**
 * 仓储--销售出库与退货入库记录（订单详情「出入库记录」Tab，按创建时间组合分页）
 * @param {Object} params { page, limit, source_id, warehouse_id, source_no, product_keyword, status, record_type, created_at }
 */
export function orderStockBillListApi(params) {
  return request.get('warehouse/stock/records', params)
}