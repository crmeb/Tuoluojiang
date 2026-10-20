import { h, nextTick, onMounted } from 'vue'
import { ElNotification } from 'element-plus'
import { auth } from '@/api/setting'
import { configRuleApproveApi } from '@/api/config'
import { enterpriseUserJoinApi } from '@/api/user'
import { pageJumpTo } from '@/libs/public'

/**
 * 提醒授权弹窗
 * @param {String} message 提示文案
 */
let authNotifySeq = 0

function showAuthNotify(message: string) {
  const content = `<div class='el-row display-align'>
    <div class="el-col el-col-24 right width100">
      <p class='title over-text'>授权提醒</p>
      <p class='caption over-text2'>${message}</p>
    </div>
  </div>
  <div class='text-right'>
    <button type="button" class="auth-notify-btn el-button el-button--text el-button--small"><span>立即授权</span></button>
  </div>`

  const markClass = `auth-notify-${++authNotifySeq}`
  let oBtn: Element | null = null
  const handleClick = () => {
    pageJumpTo('/setting/auth/auth/index')
    notify.close()
  }
  const notify = ElNotification({
    title: '消息',
    dangerouslyUseHTMLString: true,
    message: content,
    duration: 10000,
    offset: 60,
    icon: () => h('i', { class: 'iconfont iconxiaoxi' }),
    customClass: `message-socket ${markClass}`,
    onClose: () => oBtn && oBtn.removeEventListener('click', handleClick)
  })

  // 从这条通知自身的 DOM 里取按钮。此前按钮写死 id="messageOpen" 再用
  // getElementById 反查：多条通知并存时 id 重复，只会命中文档中的第一条，
  // 后续通知的按钮绑不上监听器，点「立即授权」没有任何反应。
  // Element Plus 的通知句柄不暴露 DOM，按本条通知独有的 class 查找。
  nextTick(() => {
    const el = document.querySelector(`.${markClass}`)
    oBtn = el && el.querySelector('.auth-notify-btn')
    if (oBtn) oBtn.addEventListener('click', handleClick)
  })
}

/**
 * 获取授权信息，临近过期时提示
 */
async function entAuth() {
  const obj = await auth()
  const data = obj.data
  if (data.status === -1) {
    showAuthNotify(`您的授权证书还有${data.day}天过期,请及时前往陀螺匠官方进行授权认证!`)
  }
}

/**
 * 预取审批配置
 * 返回值在工作台无任何消费方，此处仅保留原有的请求时机
 */
async function getConfigApprove() {
  await configRuleApproveApi(0)
}

/**
 * 链接邀请：本地存有邀请信息时自动加入企业并清除
 */
function getInvitation() {
  const invitationStorage = localStorage.getItem('invitationStorage')
  if (!invitationStorage) return
  try {
    const invitation = JSON.parse(invitationStorage)
    joinEnterprise(invitation)
  } catch (e) {
    console.error('解析邀请信息失败:', e)
  }
}

async function joinEnterprise(invitation: { invitation?: string | null }) {
  await enterpriseUserJoinApi({
    invitation: invitation.invitation
  })
  localStorage.removeItem('invitationStorage')
}

/**
 * 工作台页面级一次性任务：授权到期提醒、审批配置预取、邀请链接兑换。
 * 均与卡片展示、页面布局无关，统一收拢在此，页面挂载时执行一次。
 */
export function useWorkbenchTasks() {
  async function initPageTasks() {
    try {
      await Promise.all([entAuth(), getConfigApprove()])
    } catch (error) {
      console.error('异步任务执行出错:', error)
    }
    getInvitation()
  }

  onMounted(() => {
    initPageTasks()
  })
}
