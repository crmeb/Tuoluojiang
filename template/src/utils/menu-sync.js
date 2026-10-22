/**
 * 菜单变更的跨标签页同步
 * 某个标签页修改权限/模块开关后，通知同浏览器的其他标签页重新拉取菜单
 */

const MENU_SYNC_KEY = 'permissionMenuCacheSyncAt'
let refreshTask = null
let syncRegistered = false
let channel = null
let lastSyncAt = 0

/**
 * 广播菜单已失效，触发其他标签页刷新
 */
export function broadcastMenuInvalidated() {
  const payload = { type: 'permission_changed', time: Date.now() }
  lastSyncAt = payload.time
  if (channel) {
    channel.postMessage(payload)
  }
  localStorage.setItem(MENU_SYNC_KEY, String(payload.time))
}

/**
 * 注册跨标签页同步监听
 * @param {Function} refreshMenus 收到广播后用于重新拉取菜单的函数
 */
export function setupMenuSync(refreshMenus) {
  if (syncRegistered) {
    return
  }
  syncRegistered = true

  const refresh = () => {
    if (refreshTask) {
      return refreshTask
    }
    refreshTask = Promise.resolve(refreshMenus({ checkCurrentRoute: true }))
      .catch((e) => {
        // 权限被清空时刷新方法内部已完成登出与跳转，此处仅避免未处理的 rejection
        console.warn('[menu-sync] 跨标签页刷新菜单失败', e)
      })
      .finally(() => {
        refreshTask = null
      })
    return refreshTask
  }

  if (typeof BroadcastChannel !== 'undefined') {
    channel = new BroadcastChannel('oa-permission-menu')
    channel.onmessage = (event) => {
      if (event.data && event.data.type === 'permission_changed') {
        if (event.data.time && event.data.time === lastSyncAt) {
          return
        }
        refresh()
      }
    }
  }

  window.addEventListener('storage', (event) => {
    if (event.key === MENU_SYNC_KEY && event.newValue) {
      if (Number(event.newValue) === lastSyncAt) {
        return
      }
      refresh()
    }
  })
}
