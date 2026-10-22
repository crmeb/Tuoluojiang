let isAuthVisibilityRegistered = false

/**
 * 页面重新可见时刷新用户信息，借助请求层校验并续期登录状态。
 * @param {import('vuex').Store<any>} store
 */
export function setupAuthVisibilityCheck(store) {
  if (isAuthVisibilityRegistered || typeof document === 'undefined') {
    return
  }
  isAuthVisibilityRegistered = true

  let wasHidden = document.hidden

  document.addEventListener('visibilitychange', () => {
    const becameVisible = wasHidden && !document.hidden
    wasHidden = document.hidden

    if (!becameVisible || !store.getters.token) {
      return
    }

    store.dispatch('user/fetchUserInfo').catch(() => {})
  })
}
