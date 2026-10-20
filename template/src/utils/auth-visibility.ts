import pinia from '@/stores'
import { useUserStore } from '@/stores/user'

let isAuthVisibilityRegistered = false

/**
 * 页面重新可见时：先临近过期预刷，再刷新用户信息。
 */
export function setupAuthVisibilityCheck() {
  if (isAuthVisibilityRegistered || typeof document === 'undefined') {
    return
  }
  isAuthVisibilityRegistered = true

  let wasHidden = document.hidden

  document.addEventListener('visibilitychange', () => {
    const becameVisible = wasHidden && !document.hidden
    wasHidden = document.hidden

    const userStore = useUserStore(pinia)
    if (!becameVisible || !userStore.token) {
      return
    }

    userStore.ensureValidAccessToken().catch(() => {}).finally(() => {
      if (userStore.token) userStore.fetchUserInfo().catch(() => {})
    })
  })
}
