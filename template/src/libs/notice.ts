import noticeHandle from '@/libs/noticeHandle'
import { h } from 'vue'
import { ElMessage, ElNotification, type NotificationHandle } from 'element-plus'
import { roterPre } from '@/settings'
import { messageUnreadCountApi } from '@/api/public'
import pinia from '@/stores'
import { useUserStore } from '@/stores/user'
import { noticeMessageReadApi } from '@/api/user'
import { EventBus } from '@/libs/bus'
import { getMenus } from '@/utils/menu'
import { broadcastMenuInvalidated } from '@/utils/menu-sync'
import SettingMer from '@/libs/settingMer'
let limitConnect = 40 // 断线重连次数
let timeToken = ''
import audioUrl from '../assets/audio/tip.mp3'
const audioTip = new Audio(audioUrl)
/** 推送消息体 */
interface NoticeMessage {
  id: number | string
  uniqid: string
  url?: string
  image?: string
  title?: string
  message?: string
  template_type?: string
  buttons: Array<{ title: string; [key: string]: unknown }>
}

/** websocket 下发的数据包 */
interface SocketPayload {
  type?: string
  event?: string
  data?: any
}

declare global {
  interface Window {
    /** 通知内嵌 HTML 里的按钮通过全局函数回调 */
    onConfirm?: (index: number) => void
    onCancel?: () => void
    onNotice?: () => void
  }
}

const notifications: Record<string, NotificationHandle> = {}
let permissionRefreshTask: Promise<void> | null = null
// 提取消息内容生成函数
function generateMessageContent(message: NoticeMessage) {
  const imgShow = message.image
  let content = `
    <a onclick="onNotice()" href="${roterPre}${message.url}">
      <div class='el-row display-align'>
        <div class='el-col el-col-24 left' ${imgShow == '' ? 'style="display:none"' : 'style="display:block"'}>
           <img src='${imgShow}' alt='' style="width:55px;height:55px" >
        </div>
        <div ${imgShow == '' ? 'class="el-col el-col-24 right width100"' : 'class="el-col el-col-24 right"'}>
          <p class='title over-text'>${message.title}</p>
          <p class='caption over-text2'>${message.message}</p>
        </div>
      </div>
    </a>`

  if (message.buttons.length > 0) {
    content += message.buttons
      .map(
        (value, i) => `
      <div class='text-right'>
        <button type="button" class="el-button el-button--text el-button--small" onclick="onConfirm(${i})">
          <span>${value.title}</span>
        </button>
      </div>`
      )
      .join('')
  }

  return content
}

// Element Plus 的通知图标要求传组件，这里包一个渲染 iconfont 的函数式组件
const NOTICE_ICON = () => h('i', { class: 'iconfont iconxiaoxi' })

// 提取通知显示函数
function showNotification(message: NoticeMessage) {
  const notify = ElNotification({
    title: '消息',
    dangerouslyUseHTMLString: true,
    message: generateMessageContent(message),
    duration: 10000,
    offset: 60,
    icon: NOTICE_ICON,
    customClass: 'message-socket'
  })

  notifications[message.uniqid] = notify
  getMessage()

  // 设置全局回调函数
  window.onConfirm = (index) => {
    const item = message.buttons[index]
    EventBus.$emit('messageSuccess', { item, detail: message })
    closeNotification(message)
  }

  window.onCancel = () => {
    noticeHandle(message, 0)
    closeNotification(message)
  }

  window.onNotice = () => closeNotification(message)
}

// 提取关闭通知函数
function closeNotification(message: NoticeMessage) {
  if (notifications[message.uniqid]) {
    notifications[message.uniqid].close()
    delete notifications[message.uniqid]
    batchMessageRead(1, { ids: [message.id] })
  }
}

// 主函数优化
function notice(token: string) {
  const wsBaseUrl = (SettingMer.wsSocketUrl || `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}`).replace(/\/$/, '')
  const wsUrl = `${wsBaseUrl}/ws?type=ent&token=${token}`
  const ws = new WebSocket(wsUrl)
  timeToken = token
  let pingInterval: ReturnType<typeof setInterval> | undefined
  let manuallyClosed = false

  const send = (type: string, data?: unknown) => ws.send(JSON.stringify({ type, data }))

  ws.onopen = () => {
    limitConnect = 40
    getMessage()
    pingInterval = setInterval(() => send('ping'), 10000)
  }

  ws.onmessage = (res) => {
    const data = parseSocketMessage(res.data)
    if (!data) {
      return
    }
    const type = data.type || data.event
    if (type === 'notice') {
      audioTip.play()
      showNotification(data.data)
    } else if (type === 'permission_changed') {
      handlePermissionChanged(data.data)
    }
  }

  ws.onclose = () => {
    clearInterval(pingInterval)
    if (manuallyClosed) {
      return
    }
    reconnect()
  }

  ws.onerror = () => {
    // 部分浏览器/网络场景 onerror 后不一定触发 onclose,需主动清理避免 pingInterval 残留
    clearInterval(pingInterval)
    if (manuallyClosed) {
      return
    }
    reconnect()
  }

  return () => {
    manuallyClosed = true
    ws.close()
  }
}

function parseSocketMessage(message: unknown): SocketPayload | null {
  if (!message) {
    return null
  }
  try {
    return typeof message === 'string' ? JSON.parse(message) : message
  } catch (e) {
    console.warn('webSocket message parse failed', message)
    return null
  }
}

function handlePermissionChanged(payload: unknown = {}) {
  if (permissionRefreshTask) {
    return permissionRefreshTask
  }

  console.info('permission changed, refreshing menus', payload)
  permissionRefreshTask = getMenus({ checkCurrentRoute: true })
    .then(() => {
      broadcastMenuInvalidated()
    })
    .catch((e) => {
      // 权限被清空时 getMenus 已完成登出与跳转，这里只吞掉异常避免未处理的 rejection
      console.warn('[notice] 权限变更后刷新菜单失败', e)
    })
    .finally(() => {
      permissionRefreshTask = null
    })

  return permissionRefreshTask
}

// 重连
function reconnect() {
  // lockReconnect加锁，防止onclose、onerror两次重连
  if (limitConnect > 0) {
    if (!localStorage.getItem('lockReconnect')) {
      localStorage.setItem('lockReconnect', '1')
      limitConnect--
      console.log('第' + (40 - limitConnect + 1) + '次重连')
      // 进行重连
      setTimeout(function () {
        notice(timeToken)
        localStorage.removeItem('lockReconnect')
      }, 10000)
    }
  } else {
    console.log('webSocket连接已超时')
  }
}
// 批量标记未已读
function batchMessageRead(status: number, data: { ids: Array<number | string> }) {
  noticeMessageReadApi(status, data)
    .then((res) => {
      getMessage()
    })
    .catch((error) => {
      // console.log(error.message);
    })
}
// 消息数量
function getMessage() {
  messageUnreadCountApi()
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

export default notice
