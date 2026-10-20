import { getCurrentInstance } from 'vue'
import type { LocationQuery } from 'vue-router'
import { restartNotice } from '@/bootstrap/notification'
import { getMenus, isEmptyMenuError } from '@/utils/menu'
import { openAuthDialog } from '@/utils/authDialog'
import { getStorageJson } from '@/utils/storage'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import { resolvePostLoginTarget } from '@/utils/login-redirect'

/**
 * 登录成功后的公共后置流程
 * 密码登录、短信登录、扫码登录、企业微信登录共用：
 * 拉取菜单与用户信息 → 企微成员绑定 → 跳转目标页 → 建立消息长连接
 */
export const useLoginSession = () => {
  const proxy = getCurrentInstance()!.proxy!
  const userStore = useUserStore()
  const appStore = useAppStore()

  // 登录成功后缓存账号，下次进入登录页回显
  const rememberAccount = (account: string) => {
    if (account) {
      localStorage.setItem('lastLoginAccount', account)
    }
  }

  // redirect 之外的 query 需要在跳转时透传
  const getOtherQuery = () => {
    const query = proxy.$route.query || {}
    return Object.keys(query).reduce<LocationQuery>((acc, key) => {
      if (key !== 'redirect') {
        acc[key] = query[key]
      }
      return acc
    }, {})
  }

  const redirectAfterLogin = () => {
    const target = resolvePostLoginTarget(proxy.$route.query.redirect, getOtherQuery())
    return proxy.$router.push(target).catch(() => {})
  }

  /**
   * 执行登录后置流程
   * @param {string} account 本次登录使用的账号，用于下次回显
   * @param {string} corpId 企业微信 corpid，已配置但账号未绑定成员时需先完成绑定
   * @returns {Promise<boolean>} 是否成功进入系统
   */
  const finishLogin = async ({ account = '', corpId = '' }: { account?: string; corpId?: string } = {}) => {
    if (!userStore.isLogin) return false

    rememberAccount(account)
    appStore.setSidebarParentCur(0)

    // 菜单与用户信息并行请求，省去一次往返
    // 用户信息先接住失败，等菜单校验通过后才写入 store，
    // 避免账号无权限被登出后又把用户信息写回本地
    let userInfoError: unknown = null
    const userInfoTask = userStore.fetchUserInfo({ apply: false }).catch((error) => {
      userInfoError = error
      return null
    })
    const menusTask = getMenus()

    try {
      await menusTask
    } catch (error) {
      // 账号无任何菜单权限时 getMenus 已清除登录态并提示，此处停留在登录页
      if (!isEmptyMenuError(error)) {
        proxy.$message.error((error as Error).message || '获取菜单失败，请稍后重试')
      }
      return false
    }

    const userInfoData = await userInfoTask
    if (userInfoError) {
      throw userInfoError
    }
    userStore.setUserSession(userInfoData ?? undefined)

    if (corpId && !getStorageJson('userInfo', {}).work_member_id) {
      await openAuthDialog('')
    }

    await redirectAfterLogin()

    restartNotice()
    return true
  }

  return {
    finishLogin
  }
}
