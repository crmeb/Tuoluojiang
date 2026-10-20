import SettingMer from '@/libs/settingMer'
import { ElMessage } from 'element-plus'
import router from '../router'
import pinia from '@/stores'
import { useUserStore } from '@/stores/user'
import { roterPre } from '@/settings'
import { loginRouteWithRedirect } from '@/utils/login-redirect'
import { approveVerifyStatusApi } from '@/api/business'
import { userAssessSetShow } from '@/api/user'
import { messageListApi } from '@/api/public'
let templateType = ['business_approval']

/** 推送消息体 */
interface NoticeData {
  template_type?: string
  url?: string
  other?: { id: number | string }
}

function handleNotice(data: NoticeData = {}, type = 1) {
  let types = data.template_type
  if (type === 0 && (!types || !templateType.includes(types))) return
  if (Object.keys(data).length <= 0) {
    return false
  } else {
    if (types === 'enterprise_verify') {
      // 已通过平台审核，重新登录系统
      useUserStore(pinia).logout()
      router.push(loginRouteWithRedirect(router.currentRoute.value.fullPath))
    } else if (types === 'assess_start') {
      // 开启考核提醒
      userAssess(data.other!.id)
    } else {
      router.push({ path: `${roterPre}${data.url}` })
    }
  }
}

// 业务审批提醒 同意与拒绝
function approveVerify(id: number | string, status: number | string) {
  approveVerifyStatusApi(id, status)
    .then((res) => {
      ElMessage({
        message: res.message,
        type: 'success'
      })
    })
    .catch((error) => {
      ElMessage({
        message: error.message,
        type: 'error'
      })
    })
}

// 开启考核提醒
function userAssess(id: number | string) {
  userAssessSetShow(id)
    .then((res) => {
      ElMessage({
        message: res.message,
        type: 'success'
      })
    })
    .catch((error) => {
      ElMessage({
        message: error.message,
        type: 'error'
      })
    })
}

// 消息数量
function getMessage() {
  messageListApi({ page: 1, limit: 5 })
    .then((res) => {
      const num = res.data.messageNum ? res.data.messageNum : 0
      useUserStore(pinia).setMessageCount(num)
    })
    .catch((error) => {
      ElMessage({
        message: error.message,
        type: 'error'
      })
    })
}
export default handleNotice
