import request from './request'
import type { Id, ReqData } from '@/types/api'

/**
 * @description 快捷回复分组列表
 */
export function getWorkReplyGroupApi() {
  return request.get(`work/reply_temp_group`)
}

/**
 * @description 添加快捷回复分组接口
 */
export function getWorkReplyGroupCreateApi() {
  return request.get(`work/reply_temp_group/create`)
}
/**
 * @description 编辑快捷回复分组接口
 */
export function getWorkReplyGroupEditApi(id: Id) {
  return request.get(`work/reply_temp_group/${id}/edit`)
}
/**
 * @description 删除快捷回复分组接口
 */
export function getWorkReplyGroupDelApi(id: Id) {
  return request.delete(`work/reply_temp_group/${id}`)
}
/**
 * @description 获取快捷回复列表
 */
export function getWorkReplyListApi(data?: ReqData) {
  return request.get(`work/reply_temp`, data)
}
/**
 * @description 快捷回复保存接口
 */
export function workReplySaveApi(data?: ReqData) {
  return request.post(`work/reply_temp`, data)
}
/**
 * @description 获取快捷回复详情接口
 */
export function workReplyDetailsApi(id: Id) {
  return request.get(`work/reply_temp/${id}/edit`)
}
/**
 * @description 修改快捷回复保存
 */
export function workReplyPutApi(id: Id, data?: ReqData) {
  return request.put(`work/reply_temp/${id}`, data)
}
/**
 * @description 删除快捷回复接口
 */
export function workReplyDelApi(id: Id) {
  return request.delete(`work/reply_temp/${id}`)
}

/**
 * @description 快捷回复下载模板
 */
export function getWorkReplyImportApi() {
  return request.get(`work/reply_temp/import/temp`)
}
/**
 * @description 快捷回复导入
 */
export function workReplyImportApi(data?: ReqData) {
  return request.post(`work/reply_temp/import`, data)
}

/**
 * @description 群发素材分组列表
 */
export function workMassTempGroupApi() {
  return request.get(`work/mass_messaging_temp_group`)
}
/**
 * @description 添加群发素材分组接口
 */
export function workMassTempGroupCreateApi() {
  return request.get(`work/mass_messaging_temp_group/create`)
}
/**
 * @description 添加群发素材分组接口
 */
export function workMassTempGroupEditApi(id: Id) {
  return request.get(`work/mass_messaging_temp_group/${id}/edit`)
}
/**
 * @description 删除群发素材分组接口
 */
export function workMassTempGroupDelApi(id: Id) {
  return request.delete(`work/mass_messaging_temp_group/${id}`)
}

/**
 * @description 群发素材列表接口
 */
export function workMassTempListApi(data?: ReqData) {
  return request.get(`work/mass_messaging_temp`, data)
}

/**
 * @description 群发素材新增保存接口
 */
export function workMassTempSaveApi(data?: ReqData) {
  return request.post(`work/mass_messaging_temp`, data)
}

/**
 * @description 群发素材编辑保存接口
 */
export function workMassTempEditApi(id: Id, data?: ReqData) {
  return request.put(`work/mass_messaging_temp/${id}`, data)
}
/**
 * @description 群发素材获取接口
 */
export function workMassTempApi(id: Id) {
  return request.get(`work/mass_messaging_temp/${id}/edit`)
}

/**
 * @description 删除群发素材接口
 */
export function workMassTempDelApi(id: Id) {
  return request.delete(`work/mass_messaging_temp/${id}`)
}

/**
 * @description 群发消息列表
 */
export function getMassList(data?: ReqData) {
  return request.get(`work/mass_messaging`, data)
}

/**
 * @description 添加群发消息
 */
export function workMassSave(data?: ReqData) {
  return request.post(`work/mass_messaging`, data)
}

/**
 * @description 获取群发消息详情
 */
export function getWorkMassEdit(id: Id) {
  return request.get(`work/mass_messaging/${id}/edit`)
}

/**
 * @description 修改群发消息
 */
export function putWorkMassEdit(id: Id, data?: ReqData) {
  return request.put(`work/mass_messaging/${id}`, data)
}

/**
 * @description 删除群发消息
 */
export function delWorkMass(id: Id) {
  return request.delete(`work/mass_messaging/${id}`)
}

/**
 * @description 企微客户群列表
 */
export function getWorkMassGroupChat(data?: ReqData) {
  return request.post(`work/mass_messaging/group_chat`, data)
}

/**
 * @description 待发送客户数
 */
export function getWorkMassCustomerCount(data?: ReqData) {
  return request.post(`work/mass_messaging/customer_count`, data)
}

/**
 * @description 修改群发状态
 */
export function getWorkMassStatus(id: Id, data?: ReqData) {
  return request.get(`work/mass_messaging/status/${id}`, data)
}

/**
 * @description 修改群发状态
 */
export function getWorkMassRemind(id: Id) {
  return request.get(`work/mass_messaging/remind/${id}`)
}
/**
 * @description 获取群发结果
 */
export function getWorkMassResult(id: Id, data?: ReqData) {
  return request.get(`work/mass_messaging/result/${id}`, data)
}

/**
 * @description 获取网页元信息
 */
export function getUrlMetadataApi(data?: ReqData) {
  return request.post(`work/reply_temp/url_metadata`, data)
}

/**
 * @description 获上传图片路径获取临时id
 */
export function getMediaUploadByUrlApi(data?: ReqData) {
  return request.post(`work/media/upload-by-url`, data)
}

// V2.5.0

/**
 * @description 获取企微渠道活码列表
 */
export function getChannelCodeApi(data?: ReqData) {
  return request.get(`work/channel_code`, data)
}
/**
 * @description 删除企微渠道活码
 */
export function delChannelCodeApi(id: Id) {
  return request.delete(`work/channel_code/${id}`)
}

/**
 * @description 保存企微渠道活码
 */
export function saveChannelCodeApi(data?: ReqData) {
  return request.post(`work/channel_code`, data)
}

/**
 * @description 编辑保存企微渠道活码
 */
export function putChannelCodeApi(id: Id, data?: ReqData) {
  return request.put(`work/channel_code/${id}`, data)
}

/**
 * @description 获取企微渠道活码详情
 */
export function getGroupChatyDetails(id: Id) {
  return request.get(`work/channel_code/${id}/edit`)
}

/**
 * @description 获取企微客户群聊列表
 */
export function getGroupChat(data?: ReqData) {
  return request.post(`work/channel_code/group_chat`, data)
}

/**
 * @description 快捷启用或关闭企微渠道活码
 */
export function putGroupChatStatus(id: Id, status: Id) {
  return request.put(`work/channel_code/status/${id}/${status}`)
}

/**
 * @description 获取企微渠道活码数据统计
 * @param {number|string} id 渠道活码 ID
 * @param {object} [data] 查询参数，time：统计日期范围（YYYY-MM-DD - YYYY-MM-DD），不传默认最近 30 天
 */
export function getChannelCodeStatisticsApi(id: Id, data?: ReqData) {
  return request.get(`work/channel_code/statistics/${id}`, data)
}

/**
 * @description 获取渠道活码分组列表
 */
export function getChannelCodeGroupApi(data?: ReqData) {
  return request.get(`work/channel_code_group`, data)
}

/**
 * @description 获取新增渠道活码分组表单
 */
export function createChannelCodeGroupApi() {
  return request.get(`work/channel_code_group/create`)
}

/**
 * @description 获取渠道活码分组编辑表单
 */
export function editChannelCodeGroupApi(id: Id) {
  return request.get(`work/channel_code_group/${id}/edit`)
}

/**
 * @description 删除渠道活码分组
 */
export function delChannelCodeGroupApi(id: Id) {
  return request.delete(`work/channel_code_group/${id}`)
}

/**
 * @description 批量编辑活码分组（group_id 为 0 表示移出分组）
 */
export function batchChannelCodeGroupApi(data?: ReqData) {
  return request.put(`work/channel_code/batch/group`, data)
}

/* ------------------------- 自动建群配置（群活码） ------------------------- */

/**
 * @description 自动建群配置列表
 * @param {Object} data { page, limit, name, status }
 */
export function groupInvoteConfigListApi(data?: ReqData) {
  return request.get(`work/channel_code_invite_group`, data)
}

/**
 * @description 获取自动建群配置详情
 * @param {Number} id 配置 ID
 */
export function groupInvoteConfigDetailApi(id: Id) {
  return request.get(`work/channel_code_invite_group/${id}/edit`)
}

/**
 * @description 保存自动建群配置
 * @param {Object} data { chat_ids, auto_create_room, room_base_name, room_base_id }
 */
export function groupInvoteConfigCreateApi(data?: ReqData) {
  return request.post(`work/channel_code_invite_group`, data)
}

/**
 * @description 编辑自动建群配置
 * @param {Number} id 配置 ID
 * @param {Object} data 同新增
 */
export function groupInvoteConfigUpdateApi(id: Id, data?: ReqData) {
  return request.put(`work/channel_code_invite_group/${id}`, data)
}

/**
 * @description 删除自动建群配置
 * @param {Number} id 配置 ID
 */
export function groupInvoteConfigDeleteApi(id: Id) {
  return request.delete(`work/channel_code_invite_group/${id}`)
}
