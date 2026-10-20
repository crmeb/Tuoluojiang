import request, { type RequestOptions } from './request'
import type { Id, ReqData } from '@/types/api'

/**
 * @description 短信验证码Key
 */
export function getCmsKeyApi() {
  return request.get(`common/verify/key`)
}

/**
 * @description 短信验证码
 */
export function getCmsApi(data?: ReqData) {
  return request.post(`common/verify`, data)
}

/**
 * @description 验证码
 */
export function captchaApi() {
  return request.get(`common/captcha`)
}

/**
 * @description 账号密码登录
 */
export function login(data?: ReqData) {
  return request.post(`/user/login`, data)
}

/**
 * 用户--登录凭证--刷新
 * @return {*}
 */
export function refreshTokenApi(data?: ReqData) {
  return request.post(`/user/token/refresh`, data, { allowRepeat: true, skipAuthRefresh: true })
}

/**
 * @description 获取企业微信配置(无需授权)
 */
export function getCorpConfig() {
  return request.get(`user/corp/config`)
}
/**
 * @description 绩效跳过自评
 */
export function skipSelfAssessment(id: Id) {
  return request.post(`assess/to_eval/${id}`)
}
/**
 * @description 获取天气
 */
export function getWeather() {
  return request.get(`common/weather`)
}

/**
 * @description 企业微信扫码登录(无需授权)
 */
export function workAuthLogin(data?: ReqData) {
  return request.post(`user/work_auth_login`, data)
}

/**
 * @description 绑定管理员
 */
export function workBinding(data?: ReqData) {
  return request.post(`work/binding/admin`, data)
}

/**
 * @description 解绑
 */
export function workUnbinding() {
  return request.post(`work/unbind/admin`)
}

/**
 * @description 获取用户基本信息
 */
export function loginInfo(data?: ReqData) {
  return request.get(`/user/info`, data)
}

/**
 * @description 账号密码登录
 */
export function phoneLogin(data?: ReqData) {
  return request.post(`/user/phone_login`, data)
}

/**
 * @description 用户注册
 */
export function registerUserApi(data?: ReqData) {
  return request.post(`/registerUser`, data)
}

/**
 * @description 重置密码
 */
export function savePasswordApi(data?: ReqData) {
  return request.put(`/user/savePassword`, data)
}
/**
 * @description 获取个人简历信息
 */
export function getResume(data?: ReqData) {
  return request.get(`/user/resume`, data)
}
/**
 * @description 保存个人简历信息
 */
export function saveResume(data?: ReqData) {
  return request.put(`user/resume_save`, data)
}
/**
 * @description 退出登录
 */
export function logout() {
  return request.get(`common/logout`)
}

/**
 * @description 验证密码
 */
export function checkPasswordApi(data?: ReqData) {
  return request.post(`user/checkpwd`, data)
}
/**
 * @description 获取工作经历列表
 */
export function userWorkList(data?: ReqData) {
  return request.get(`user/work`, data)
}
/**
 * @description 获取教育列表
 */
export function userEducationList(data?: ReqData) {
  return request.get(`user/education`, data)
}
/**
 * @description 完善资料邀请记录
 */
export function getPerfectIndex(data?: ReqData) {
  return request.get(`user/perfect/index`, data)
}
/**
 * @description 同意发送资料
 */
export function putPerfectAgree(id: Id) {
  return request.put(`user/perfect/agree/${id}`)
}
/**
 * @description 拒绝发送资料
 */
export function putPerfectRefuse(id: Id) {
  return request.put(`user/perfect/refuse/${id}`)
}

/**
 * @description 获取创建工作经历表单
 */
export function userCreateWork(data?: ReqData) {
  return request.get(`user/work/create`, data)
}
/**
 * @description 删除个人简历工作经历表单
 */
export function userDeleteWork(work: Id) {
  return request.delete(`user/work/${work}`)
}

/**
 * @description 编辑创建个人简历工作经历表单
 */
export function userPutWork(work: Id) {
  return request.get(`user/work/${work}/edit`)
}

/**
 * @description 工作分析列表
 */
export function jobAnalysis(data?: ReqData) {
  return request.get(`company/job_analysis`, data)
}

/**
 * @description 获取工作分析
 */
export function jobDetails(id: Id) {
  return request.get(`company/job_analysis/info/${id}`)
}

/**
 * @description 修改工作分析
 */
export function putAnalysis(id: Id, data?: ReqData) {
  return request.put(`company/job_analysis/${id}`, data)
}

/**
 * @description 获取我的工作分析
 */
export function mineAnalysis() {
  return request.get(`company/job_analysis/mine`)
}

/**
 * @description 获取创建教育经历表单
 */
export function userCreateEducation(data?: ReqData) {
  return request.get(`user/education/create`, data)
}
/**
 * @description 删除个人简历教育经历表单
 */
export function userDeleteEducation(education: Id) {
  return request.delete(`user/education/${education}`)
}
/**
 * @description 编辑创建个人简历教育经历表单
 */
export function userPutEducation(education: Id) {
  return request.get(`user/education/${education}/edit`)
}
/**
 * @description 当前企业邀请的用户 -- 列表
 */
export function getApplyApi(data?: ReqData) {
  return request.get(`user/ent/getApply`, data)
}

/**
 * @description 当前企业邀请的用户 -- 删除
 */
export function applyDeleteApi(id: Id, data?: ReqData) {
  return request.delete(`user/ent/apply/${id}`, data)
}

/**
 * @description 当前企业邀请的用户 -- 同意加入企业 user/refuse/{id}
 */
export function applyAgreeApi(id: Id) {
  return request.put(`user/agree/${id}`)
}

/**
 * @description 当前企业邀请的用户 -- 拒绝加入企业
 */
export function applyRefuseApi(id: Id) {
  return request.put(`user/refuse/${id}`)
}

/**
 * @description 企业成员 -- 列表

 */
export function userListApi(data?: ReqData) {
  return request.get(`user/list`, data)
}

/**
 * @description 获取组织架构成员修改信息

 */
export function userEntCardApi(id: Id) {
  return request.get(`system/roles/role/${id}`)
}

/**
 * @description 获取人事管理--组织架构成员修改信息

 */
export function userCardApi(id: Id) {
  return request.get(`user/card/${id}`)
}
/**
 * @description 获取出勤统计接口

 */
export function attendanceStatistics(data?: ReqData) {
  return request.get(`attendance/attendance_statistics`, data)
}
/**
 * @description 获取出勤表格数据接口

 */
export function individualStatistics(data?: ReqData) {
  return request.get(`attendance/individual_statistics`, data)
}
/**
 * 修改用户角色
 */
export function systemUserRoleApi(data?: ReqData) {
  return request.post(`system/roles/user`, data)
}

/**
 * @description 修改人事管理--组织架构成员
 */
export function userHrUpdateApi(id: Id, data?: ReqData) {
  return request.put(`user/card/${id}`, data)
}
/**
 * @description 办公中心-企业通讯录-组织架构
 */
export function userAddBookTree() {
  return request.get('user/add_book/tree')
}

/**
 * 办公中心-企业通讯录-列表
 * @param data
 * @return {*}
 */
export function userAddBookeList(data?: ReqData) {
  return request.get('user/add_book/list', data)
}

/**
 * 工作台-获取企业文档列表
 * @param data
 * @return {*}
 */
export function fileList(data?: ReqData) {
  return request.get('enterprise/template/lst', data)
}

/**
 * 工作台-获取模板支付二维码
 * @return {*}
 * @param id
 */
export function templatePayCode(id: Id) {
  return request.get(`enterprise/template/pay/code/${id}`)
}

/**
 * 云文件-获取模板预览地址
 * @param id
 * @return {*}
 */
export function templateViewApi(id: Id) {
  return request.get(`enterprise/folder/template_view/${id}`)
}

/**
 * 个人中心-获取当前用户信息
 * @return {*}
 */
export function userInfo() {
  return request.get('user/userInfo')
}

/**
 * 个人中心-修改当前用户信息
 * @param data
 * @return {*}
 */
export function editUserInfo(data?: ReqData) {
  return request.put('user/userInfo', data)
}

/**
 * 个人中心-更新当前用户McpKey
 * @return {*}
 */
export function updateMcpKeyApi() {
  return request.put('user/mcp_key')
}


/**
 * 批量邀请用户加入企业
 * @param data
 * @return {*}
 */
export function userBatchCreate(data?: ReqData) {
  return request.post('user/batch/create', data)
}

/**
 * @description 站点配置
 */
export function site() {
  return request.get(`common/site`)
}

/**
 * 个人办公-我的考核-我的自评与下级考核
 * 类型:0=我的自评;1=下级评估
 * @param data
 * @returns {*} user/assess/deletes
 */
export function userAssessList(data?: ReqData) {
  return request.get(`assess/list`, data)
}

/**
 * 绩效考核--员工绩效--删除记录列表
 * @param data
 * @returns {*}
 */
export function userAssessDeletesList(data?: ReqData) {
  return request.get(`assess/del_record`, data)
}
/**
 * 绩效考核--V1.4未创建考核记录列表
 * @param data
 * @returns {*}
 */
export function userAssessAbnormalList(data?: ReqData) {
  return request.get(`assess/abnormal`, data)
}
/**
 * 绩效考核--V1.4未创建考核记录提示显示
 * @param data
 * @returns {*}
 */
export function userAssessAbnormal(data?: ReqData) {
  return request.get(`assess/is_abnormal`, data)
}

/**
 * 人事管理-员工绩效-提醒考核人
 * @param id
 * @returns {*}
 */
export function userAssessRemindApi(id: Id) {
  return request.get(`user/assess/remind/${id}`)
}

/**
 * 个人办公-V1.4我的考核-下级考核-列表
 * @param data
 * @returns {*}
 */
export function userAssessSubord(data?: ReqData) {
  return request.get(`assess/index`, data)
}

/**
 * 个人办公-我的考核-下级考核-状态设置
 *
 */
export function userAssessSetShow(id: Id, data?: ReqData) {
  return request.get(`assess/show/${id}`, data)
}
/**
 * 获取绩效考核详情
 * @param id
 * @returns {*}

 */
export function userAssessInfo(id: Id) {
  return request.get(`assess/info/${id}`)
}

/**
 * 个人办公-V1.4我的考核-目标定制-修改
 * @param id
 * @param data
 * @returns {*}
 */
export function userAssessEditApi(id: Id, data?: ReqData) {
  return request.post(`assess/update/${id}`, data)
}

/**
 * v1.4 创建绩效考核
 * @returns {*}
 * @param data
 */
export function userAssessCreateApi(data?: ReqData) {
  return request.post(`assess/create`, data)
}

/**
 * v1.4 绩效考核自评
 * @param id
 * @param data
 * @returns {*}
 */
export function userAssessSelfSaveApi(id: Id, data?: ReqData) {
  return request.put(`assess/self_eval/${id}`, data)
}

/**
 * v1.4 绩效考核上级评价
 * @param id
 * @param data
 * @returns {*}
 */
export function userSuperiorEvalSaveApi(id: Id, data?: ReqData) {
  return request.put(`assess/superior_eval/${id}`, data)
}

/**
 * v1.4 绩效考核上上级评价
 * @param id
 * @param data
 * @returns {*}
 */
export function userExamineEvalSaveApi(id: Id, data?: ReqData) {
  return request.put(`assess/examine_eval/${id}`, data)
}
/**
 * 个人办公-企业邀请-加入企业
 * @param data
 * @returns {*}
 */
export function enterpriseUserJoinApi(data?: ReqData) {
  return request.put(`user/user/join`, data)
}

/**
 * 个人办公-我的考核-当前考核考核-目标定制-保存
 * @param data
 * @returns {*}
 */
export function userAssessTarget(data?: ReqData) {
  return request.post(`assess/target`, data)
}
/**
 * 个人办公-我的考核-考核评分记录
 * @param id
 * @param data
 * @returns {*}
 */
export function userAssessRecord(id: Id, data?: ReqData) {
  return request.get(`assess/score/${id}`, data)
}

/**
 * 个人办公-我的考核-删除考核记录
 * @param id
 * @returns {*}
 */
export function userAssessDelete(id: Id) {
  return request.get(`assess/del_form/${id}`)
}

/**
 * 个人办公-我的考核-执行期-保存上级备注
 * @param id
 * @param data
 * @returns {*}
 */
export function userAssessMark(id: Id, data?: ReqData) {
  return request.put(`user/assess/mark/${id}`, data)
}

/**
 * 个人办公-我的考核-当前考核考核-考核相关-评论-标题
 * @param id
 * @returns {*}
 */
export function userAssessExplain(id: Id) {
  return request.get(`assess/explain/${id}`)
}
/**
 * 记事本-最近使用
 * @param id
 * @returns {*}
 */
export function memorialGroupApi(data?: ReqData) {
  return request.get(`user/memorial/group`, data)
}

/**
 * @description 刷新个人简历工作经历表单
 */
export function getUserWorkList(data?: ReqData) {
  return request.get(`user/work`, data)
}

/**
 * @description 刷新个人简历工作经历表单
 */
export function getUserEducationList(data?: ReqData) {
  return request.get(`user/education`, data)
}

/**
 * 日程类型列表
 * @returns {*}
 */
export function scheduleTypesApi() {
  return request.get(`/schedule/types`)
}

/**
 * 日程评论
 * @returns {*}
 */
export function scheduleReplySaveApi(data?: ReqData) {
  return request.post(`/schedule/reply/save`, data)
}

/**
 * 日程评论列表
 * @returns {*}
 */
export function scheduleReplyListApi(data?: ReqData) {
  return request.get(`/schedule/replys`, data)
}
/**
 * 日程评论删除
 * @returns {*}
 */
export function scheduleReplyDelApi(id: Id) {
  return request.delete(`/schedule/reply/del/${id}`)
}

/**
 * 业绩统计
 * @returns {*}
 */
export function workStatisticsApi(types: Id) {
  return request.get(`user/work/statistics/${types}`)
}
/**
 * 管理业绩统计数据
 * @returns {*}
 */
export function workStatisticsApiAll() {
  return request.get(`user/work/statistics_type`)
}
/**
 * 修改业绩统计数据
 * @returns {*}
 */
export function putStatisticsApiAll(data?: ReqData) {
  return request.post(`user/work/statistics_type`, data)
}

/**
 * 下载模板文件
 * @returns {*}
 */
export function templateExportApi(id: Id) {
  return request.get(`enterprise/template/export/${id}`)
}

/**
 * 日程详情
 * @returns {*}
 */
export function scheduleInfoApi(id: Id, data?: ReqData) {
  return request.get(`/schedule/info/${id}`, data)
}

/**
 * 日程删除
 * @returns {*}
 */
export function scheduleDeleteApi(id: Id, data?: ReqData) {
  return request.delete(`/schedule/delete/${id}`, data)
}

/**
 * 日程编辑
 * @returns {*}
 */
export function scheduleEditApi(id: Id, data?: ReqData) {
  return request.put(`/schedule/update/${id}`, data)
}

/**
 * 新建日程类型
 * @returns {*}
 */
export function scheduleTypesCreateApi() {
  return request.get(`/schedule/type/create`)
}
/**
 * 编辑日程类型
 * @returns {*}
 */
export function scheduleTypesEditApi(id: Id) {
  return request.get(`/schedule/type/edit/${id}`)
}
/**
 * 新增日程表单
 * @returns {*}
 */
export function scheduleStoreApi(data?: ReqData) {
  return request.post(`/schedule/store`, data)
}

/**
 * 日程--快捷创建
 * @return {*}
 */
export function scheduleQuickCreateApi(data?: ReqData) {
  return request.post(`/schedule/quick`, data)
}
/**
 * 获取日程列表
 * @returns {*}
 */
export function scheduleListApi(data?: ReqData) {
  return request.post(`/schedule/index`, data)
}

/**
 * 删除日程类型
 * @returns {*}
 */
export function scheduleTypesDeleteApi(id: Id) {
  return request.delete(`/schedule/type/delete/${id}`)
}
/**
 * 修改日程状态
 * @returns {*}
 */
export function scheduleStatusApi(id: Id, data?: ReqData) {
  return request.put(`/schedule/status/${id}`, data)
}

/**
 * 帮助中心结果页搜索
 * @returns {*}
 */
export function helpCenterApi(data?: ReqData) {
  return request.get(`helps/aggregate`, data)
}
/**
 * 备忘录--分类--列表
 * @returns {*}
 */
export function memorialCateListApi(data?: ReqData) {
  return request.get(`user/memorial_cate`, data)
}

/**
 * 备忘录--分类--添加分类
 * @returns {*}
 */
export function memorialCateCreateApi(pid: Id) {
  return request.get(`user/memorial_cate/create/${pid}`)
}

/**
 * 备忘录--分类--编辑分类
 * @returns {*}
 */
export function memorialCateEditApi(id: Id) {
  return request.get(`user/memorial_cate/${id}/edit`)
}

/**
 * 备忘录--分类--删除
 * @returns {*}user/memorial
 */
export function memorialCateDeleteApi(id: Id) {
  return request.delete(`user/memorial_cate/${id}`)
}

/**
 * 备忘录--列表
 * @returns {*}
 */
export function memorialListApi(data?: ReqData) {
  return request.get(`user/memorial`, data)
}

/**
 * 备忘录--详情（切换笔记时按需加载正文）
 * @returns {*}
 */
export function memorialInfoApi(id: Id) {
  return request.get(`user/memorial/info/${id}`)
}

/**
 * 备忘录--保存
 * @returns {*}
 */
export function memorialSaveApi(data?: ReqData) {
  return request.post(`user/memorial`, data)
}

/**
 * 备忘录--修改
 * @param {object} options 请求配置，自动保存传 { silent: true } 可静默成功提示；
 *                          手动保存的成功提示默认 1.5s（全局默认 5s 太长），可传 tipDuration 覆盖
 * @returns {*}
 */
export function memorialEditApi(id: Id, data?: ReqData, options: RequestOptions = {}) {
  return request.put(`user/memorial/${id}`, data, { tipDuration: 1500, ...options })
}

/**
 * 备忘录--删除
 * @returns {*}
 */
export function memorialDeleteApi(id: Id) {
  return request.delete(`user/memorial/${id}`)
}

/**
 * 我的待办--待办概览统计
 * @returns {*}
 */
export function todoOverviewApi() {
  return request.get(`todo/overview`)
}

/**
 * 我的待办--待办列表
 * @param {Object} data - { type: 'assess_self' }
 * @returns {*}
 */
export function todoListApi(data?: ReqData) {
  return request.get(`todo/list`, data)
}

/**
 * 我的待办--待办添加
 * @returns {*}
 */
export function dealtScheduleAddApi(data?: ReqData) {
  return request.post(`user/schedule`, data)
}

/**
 * 我的待办--列表
 * @returns {*} user/schedule/count
 */
export function dealtScheduleListApi(data?: ReqData) {
  return request.get(`user/schedule`, data)
}

/**
 * 我的待办--日历标记提醒
 * @returns {*}
 */
export function dealtScheduleCountApi(data?: ReqData) {
  return request.post(`/schedule/count`, data)
}

/**
 * 我的待办--待办类型获取
 * @returns {*}
 */
export function dealtScheduleTypesApi() {
  return request.get(`user/schedule/types`)
}

/**
 * 我的待办--删除
 * @returns {*}
 */
export function dealtScheduleDeleteApi(id: Id) {
  return request.delete(`user/schedule/${id}`)
}

/**
 * 我的待办--待办编辑
 * @returns {*}
 */
export function dealtScheduleEditApi(id: Id, data?: ReqData) {
  return request.put(`user/schedule/${id}`, data)
}

/**
 * 我的待办--提醒状态确认
 * @returns {*}
 */
export function dealtScheduleRecordApi(id: Id, data?: ReqData) {
  return request.post(`user/schedule/record/${id}`, data)
}

/**
 * 初始值密码修改
 * @param data {Object}
 * @returns {*}
 */
export function userEntSavePasswordApi(data?: ReqData) {
  return request.put(`user/savePassword`, data)
}

/**
 * 获取消息中心列表
 * @param data {Object}
 * @returns {*}
 */
export function noticeMessageListApi(data?: ReqData) {
  return request.get(`company/message`, data)
}

/**
 * 批量修改消息为已读
 * @param isRead {Number}
 * @param data {Object}
 * @returns {*}
 */
export function noticeMessageReadApi(isRead: Id, data?: ReqData) {
  return request.put(`company/message/batch/${isRead}`, data)
}

/**
 * 批量删除消息
 * @param data {Object}
 * @returns {*}
 */
export function noticeMessageDeleteApi(data?: ReqData) {
  return request.delete(`company/message/batch`, data)
}
/**
 * 我的汇报 - 未提交汇报统计
 * @param data {Object}
 * @returns {*}
 */
export function submitStatisticsApi(data?: ReqData) {
  return request.get(`daily/submit_statistics`, data)
}

/**
 * 我的汇报 - 汇报统计
 * @param data {Object}
 * @returns {*}
 */
export function statisticsApi(data?: ReqData) {
  return request.get(`daily/statistics`, data)
}
/**
 * 我的汇报 - 已提交汇报的表格数据
 * @param data {Object}
 * @returns {*}
 */
export function submitListApi(data?: ReqData) {
  return request.get(`daily/submit_list`, data)
}

/**
 * 我的汇报 - 未提交汇报的表格数据
 * @param data {Object}
 * @returns {*}
 */
export function noSubmitListApi(data?: ReqData) {
  return request.get(`daily/no_submit_list`, data)
}

/**
 * 我的汇报 - 删除当天的汇报
 * @returns {*}
 * @param id
 */
export function deleteDailyApi(id: Id) {
  return request.delete(`daily/${id}`)
}

/**
 * 获取工作台快捷菜单
 * @returns {*}
 */
export function userWorkMenusApi() {
  return request.get(`user/work/menus`)
}

/**
 * 绩效考核指标自评
 PUT /ent/user/assess/eval
 * @returns {*}
 */
export function userAssessEvalApi(data?: ReqData) {
  return request.put(`assess/eval`, data)
}

/**
 * 获取工作台待办数量
 * @returns {*}
 */
export function userWorkCountApi() {
  return request.get(`user/work/count`)
}


/**
 * 自定义工作台快捷菜单
 * user/work/menus
 * @returns {*}
 */
export function userWorkFastMenusApi(data?: ReqData) {
  return request.post(`user/work/menus`, data)
}

/**
 * 获取订阅消息列表
 * company/message/subscribe
 * @returns {*}
 */
export function userNoticeSubscribeApi(data?: ReqData) {
  return request.get(`company/message/subscribe`, data)
}

/**
 * 订阅/取消订阅消息
 * company/message/subscribe/{id}
 * @returns {*}
 */
export function userNoticeSubscribeShowApi(id: Id, data?: ReqData) {
  return request.put(`company/message/subscribe/${id}`, data)
}

/**
 获取扫码登录参数
 * @returns {*}
 */
export function userScanKeyApi() {
  return request.get(`user/scan_key`)
}

/**
 * 获取扫码状态
 * @param data {Object}
 * @returns {*}
 */
export function userScanStatusApi(data?: ReqData) {
  return request.post(`user/scan_status`, data)
}
/**
 * @description 下级岗位职责列表
 */
export function subordinateApi(data?: ReqData) {
  return request.get(`jobs/subordinate`, data)
}

/**
 * 下级岗位职责详情
 * @returns {*}
 */
export function subordinateInfoApi(id: Id) {
  return request.get(`jobs/subordinate/${id}`)
}

/**
 * 修改岗位职责接口
 * @returns {*}
 */
export function putSubordinateApi(id: Id, data?: ReqData) {
  return request.put(`jobs/subordinate/${id}`, data)
}
