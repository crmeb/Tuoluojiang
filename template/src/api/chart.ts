import request from './request'
import type { Id, ReqData } from '@/types/api'

/**
 *  图表列表
 * @param {*} data
 * @returns
 */
export function dashboardList(data?: ReqData) {
  return request.get('crud/dashboard', data)
}
/**
 * 新增
 * @param {*} data
 * @returns
 */
export function saveDashboard(data?: ReqData) {
  return request.post('crud/dashboard', data)
}
/**
 *  修改
 * @param {*} data
 * @param {*} id
 * @returns
 */
export function changeDashboard(id: Id, data?: ReqData) {
  return request.put(`crud/dashboard/${id}`, data)
}
/**
 *  保存看板数据
 * @param {*} data
 * @param {*} id
 * @returns
 */
export function changeDashboardDesign(id: Id, data?: ReqData) {
  return request.put(`crud/dashboard/design/${id}`, data)
}
/**
 *  获取看板数据
 * @param {*} data
 * @param {*} id
 * @returns
 */
export function getDashboardDesign(id: Id, data?: ReqData) {
  return request.get(`crud/dashboard/design/${id}`, data)
}

/**
 *
 * 通用查询接口
 */
export function getDataList(data?: ReqData) {
  return request.post('crud/dashboard/list', data)
}

/**
 * 图表数据查询
 * 参数由看板模块组装，见 views/system/dashboard-design/charts/chartQuery.ts
 */
export function queryChartDataApi(data?: ReqData) {
  return request.post('crud/dashboard/chart', data)
}
