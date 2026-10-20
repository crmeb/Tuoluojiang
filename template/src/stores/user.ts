import { defineStore } from 'pinia'
import { ref } from 'vue'
import { login, phoneLogin, logout as logoutApi, userScanStatusApi, refreshTokenApi, loginInfo } from '@/api/user'
import { resetRouter } from '@/router'
import { AiEmbeddedManager } from '@/libs/ai'
import { processResourceData } from '@/utils/resourceUtil'
import { getStorageJson } from '@/utils/storage'
import { EventBus } from '@/libs/bus'
import type { ApiResult } from '@/types/api'
import { useAppConfigStore } from './appConfig'
import { useOrganizationStore } from './organization'
import { useWarehouseStore } from './warehouse'

/** 登录用户信息，只列出前端直接读取的字段 */
export interface UserInfo {
  id?: number
  uid?: string | number
  name?: string
  avatar?: string
  phone?: string
  job?: { id: number; name: string } | null
  work_member_id?: string | number
  [key: string]: unknown
}

/** 当前企业信息 */
export interface EnterpriseInfo {
  enterprise_name?: string
  logo?: string
  [key: string]: unknown
}

/** 后端菜单节点 */
export interface MenuItem {
  id: number
  pid?: number
  menu_name?: string
  menu_path?: string
  component?: string
  is_show?: number
  children?: MenuItem[]
  [key: string]: unknown
}

interface AuthTokens {
  token: string
  refresh_token?: string
  expires_in?: number | string
}

/** 用户与企业信息接口（/user/info）返回的 data */
export interface UserSession {
  userInfo?: UserInfo
  enterprise?: EnterpriseInfo
}

export interface LoginPayload {
  activeName: 'passwordLogin' | 'cmsLogin' | string
  userInfo: Record<string, unknown>
}

// 退出登录时清空本地缓存，企业信息保留给登录页展示
function clearAuthStorage() {
  const enterpriseCache = localStorage.getItem('enterprise')
  localStorage.clear()
  if (enterpriseCache) {
    localStorage.setItem('enterprise', enterpriseCache)
  }
  window.sessionStorage.clear()
}

const TOKEN_REFRESH_THRESHOLD_MS = 10 * 60 * 1000

export interface RemoteAuth {
  token: string
  refreshToken: string
  tokenExpiresAt: number
}

function parseJwtExpiresAt(token: string): number {
  try {
    const segment = token.split('.')[1]
    if (!segment) return 0
    const payload = JSON.parse(window.atob(segment.replace(/-/g, '+').replace(/_/g, '/'))) as { exp?: number }
    return Number(payload.exp) * 1000 || 0
  } catch {
    return 0
  }
}

/** 登录态：token、用户 / 企业信息、菜单与按钮权限、未读消息数 */
export const useUserStore = defineStore('user', () => {
  const token = ref(localStorage.getItem('token') || '')
  const refreshToken = ref(localStorage.getItem('refresh_token') || '')
  const tokenExpiresAt = ref(Number(localStorage.getItem('token_expires_at') || 0))
  const isLogin = ref(!!token.value)
  let refreshing: Promise<AuthTokens> | null = null
  const userInfo = ref<UserInfo>(getStorageJson('userInfo', {}))
  const enterprise = ref<EnterpriseInfo>(getStorageJson('enterprise', {}))
  const menuList = ref<MenuItem[]>([])
  const menusLoaded = ref(false)
  const permissions = ref<string[]>([])
  const messageCount = ref(0)

  function setToken(value: string) {
    token.value = value
    localStorage.setItem('token', value)
  }

  function setRefreshToken(value: string) {
    refreshToken.value = value
    localStorage.setItem('refresh_token', value)
  }

  function setTokenExpiresAt(value: number) {
    tokenExpiresAt.value = value || 0
    if (value) localStorage.setItem('token_expires_at', String(value))
    else localStorage.removeItem('token_expires_at')
  }

  /** 远端已写入 storage，仅更新内存，避免同步循环 */
  function applyRemoteAuth(auth: RemoteAuth) {
    token.value = auth.token
    refreshToken.value = auth.refreshToken
    tokenExpiresAt.value = auth.tokenExpiresAt
    isLogin.value = !!auth.token
  }

  /** 写入登录凭证并标记已登录 */
  function setAuthTokens(data: AuthTokens) {
    setToken(data.token)
    setRefreshToken(data.refresh_token || '')
    setTokenExpiresAt(Number(data.expires_in) > 0
      ? Date.now() + Number(data.expires_in) * 1000
      : parseJwtExpiresAt(data.token))
    isLogin.value = !!data.token
    EventBus.$emit('auth-tokens-changed', {
      token: token.value, refreshToken: refreshToken.value, tokenExpiresAt: tokenExpiresAt.value
    })
  }

  function setUserInfo(data: UserInfo | null) {
    // 退出登录时会写入 null，沿用原有运行时行为
    userInfo.value = processResourceData(data) as UserInfo
    if (data) {
      localStorage.setItem('userInfo', JSON.stringify(userInfo.value))
    } else {
      localStorage.removeItem('userInfo')
    }
  }

  function setEnterprise(data: EnterpriseInfo | null) {
    enterprise.value = processResourceData(data) as EnterpriseInfo
    if (data) {
      localStorage.setItem('enterprise', JSON.stringify(enterprise.value))
    } else {
      localStorage.removeItem('enterprise')
    }
  }

  function setMenuList(list: MenuItem[]) {
    menuList.value = list
  }

  function setMenusLoaded(loaded: boolean) {
    menusLoaded.value = loaded
  }

  function setPermissions(list: string[]) {
    permissions.value = list
  }

  function setMessageCount(count: number) {
    messageCount.value = count
  }

  function resetUserState() {
    setToken('')
    setRefreshToken('')
    setTokenExpiresAt(0)
    setMenuList([])
    setMenusLoaded(false)
    setPermissions([])
    setUserInfo({})
    isLogin.value = false
    messageCount.value = 0
    useOrganizationStore().reset()
    useWarehouseStore().reset()
    useAppConfigStore().resetConfig()
    clearAuthStorage()
    EventBus.$emit('auth-tokens-changed', { token: '', refreshToken: '', tokenExpiresAt: 0 })
  }

  async function loginByAccount(data: LoginPayload) {
    const loginApi = data.activeName === 'passwordLogin' ? login : phoneLogin
    const response: ApiResult<AuthTokens | null> = await loginApi(data.userInfo)
    // 业务校验失败（如验证码错误）时请求层已弹出提示且不会返回 token，此处不能写入登录态
    if (!response.data || !response.data.token) {
      throw new Error(response.message || '登录失败')
    }
    setAuthTokens(response.data)
    return response.data
  }

  /** 轮询扫码状态；返回数据无 status 表示已确认登录，token 随之写入 */
  async function scanLogin(data: { key: string }) {
    const response: ApiResult<Partial<AuthTokens> & { status?: number }> = await userScanStatusApi(data)
    if (response.data.status === undefined && response.data.token) {
      setAuthTokens(response.data as AuthTokens)
    }
    return response.data
  }

  /**
   * 拉取用户与企业信息
   * @param apply 是否立即写入 store；登录流程需等菜单校验通过后再写入，可传 false 自行接管写入时机
   */
  async function fetchUserInfo({ apply = true } = {}) {
    const response: ApiResult<UserSession> | undefined = await loginInfo()
    const data = response?.data || {}
    if (apply) {
      setUserSession(data)
    }
    return data
  }

  /** 用 storage 中最新 refresh_token 续期；预刷与请求重试共用同一任务 */
  function refreshAuthToken(): Promise<AuthTokens> {
    if (refreshing) return refreshing
    const latestRefreshToken = localStorage.getItem('refresh_token') || refreshToken.value
    if (!latestRefreshToken) return Promise.reject(new Error('缺少刷新TOKEN'))
    const task = (async () => {
      const response: ApiResult<AuthTokens | null> = await refreshTokenApi({ refresh_token: latestRefreshToken })
      if (!response.data?.token || !response.data.refresh_token) {
        throw new Error(response.message || '登录状态已失效')
      }
      setAuthTokens(response.data)
      EventBus.$emit('auth-token-updated', response.data.token)
      fetchUserInfo().catch(() => {})
      return response.data
    })()
    refreshing = task.finally(() => { refreshing = null })
    return refreshing
  }

  async function ensureValidAccessToken() {
    const latestToken = localStorage.getItem('token') || token.value
    if (!latestToken) return null
    const expiresAt = Number(localStorage.getItem('token_expires_at') || tokenExpiresAt.value || 0) || parseJwtExpiresAt(latestToken)
    if (expiresAt && Date.now() < expiresAt - TOKEN_REFRESH_THRESHOLD_MS) return null
    if (!(localStorage.getItem('refresh_token') || refreshToken.value)) return null
    return refreshAuthToken()
  }

  /** 把用户与企业信息写入 store 与本地缓存 */
  function setUserSession(data: UserSession = {}) {
    if (data.userInfo) {
      setUserInfo(data.userInfo)
    }
    if (data.enterprise) {
      setEnterprise(data.enterprise)
    }
  }

  async function logout() {
    await logoutApi().catch(() => {})
    resetUserState()
    resetRouter()
    AiEmbeddedManager.destroyAll()
  }

  /** 清除本地登录态（不请求退出接口） */
  function resetToken() {
    resetUserState()
    AiEmbeddedManager.destroyAll()
  }

  return {
    token,
    refreshToken,
    tokenExpiresAt,
    applyRemoteAuth,
    ensureValidAccessToken,
    isLogin,
    userInfo,
    enterprise,
    menuList,
    menusLoaded,
    permissions,
    messageCount,
    setToken,
    setAuthTokens,
    setUserInfo,
    setEnterprise,
    setMenuList,
    setMenusLoaded,
    setPermissions,
    setMessageCount,
    login: loginByAccount,
    scanLogin,
    fetchUserInfo,
    refreshAuthToken,
    setUserSession,
    logout,
    resetToken
  }
})
