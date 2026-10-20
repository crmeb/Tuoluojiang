/** 登录 / 续期 / 退出后，同步同源标签页的 Pinia 登录态。 */
import pinia from '@/stores'
import { useUserStore, type RemoteAuth } from '@/stores/user'
import { EventBus } from '@/libs/bus'

let registered = false
let channel: BroadcastChannel | null = null

/** storage 为最新凭证的来源；读取后不回写，避免同步循环。 */
export function syncAuthFromStorage(): RemoteAuth {
  const auth = {
    token: localStorage.getItem('token') || '',
    refreshToken: localStorage.getItem('refresh_token') || '',
    tokenExpiresAt: Number(localStorage.getItem('token_expires_at') || 0)
  }
  const userStore = useUserStore(pinia)
  const previousToken = userStore.token
  if (auth.token !== previousToken || auth.refreshToken !== userStore.refreshToken || auth.tokenExpiresAt !== userStore.tokenExpiresAt) {
    userStore.applyRemoteAuth(auth)
    if (auth.token && auth.token !== previousToken) EventBus.$emit('auth-token-updated', auth.token)
  }
  return auth
}

export function setupAuthSync() {
  if (registered || typeof window === 'undefined') return
  registered = true
  if (typeof BroadcastChannel !== 'undefined') {
    channel = new BroadcastChannel('oa-auth-token')
    channel.onmessage = (event: MessageEvent<unknown>) => {
      const data = event.data
      if (data && typeof data === 'object' && 'type' in data && data.type === 'auth_updated') syncAuthFromStorage()
    }
  }
  EventBus.$on('auth-tokens-changed', () => {
    channel?.postMessage({ type: 'auth_updated' })
  })
  window.addEventListener('storage', (event) => {
    if (event.key === null || ['token', 'refresh_token', 'token_expires_at'].includes(event.key)) syncAuthFromStorage()
  })
}
