import axios, { type AxiosRequestConfig, type AxiosResponse } from 'axios'
import pinia from '@/stores'
import { useUserStore } from '@/stores/user'
import { getShareUnique } from '@/utils/auth'
import router from '../router'
import SettingMer from '@/libs/settingMer'
import Tips from '@/utils/tips'
import { roterPre } from '@/settings'
import { loginHrefWithRedirect } from '@/utils/login-redirect'
import { normalizeRequestUrl, processResourceData } from '@/utils/resourceUtil'
import { EventBus } from '@/libs/bus'
import { syncAuthFromStorage } from '@/utils/auth-sync'
import type { ApiResult, ReqData } from '@/types/api'

/** 在 axios 配置之上扩展的业务请求选项 */
export interface RequestOptions extends AxiosRequestConfig {
  login?: boolean
  /** 是否允许重复请求，默认不允许 */
  allowRepeat?: boolean
  /** 静默模式：不弹出后端返回的成功/失败提示，由调用方自行展示 */
  silent?: boolean
  /** 成功提示时长（ms） */
  tipDuration?: number
  skipAuthRefresh?: boolean
  _retry?: boolean
}

/** 后端响应体（含提示开关） */
type ResponseBody = ApiResult<any> & { tips?: boolean | number }

/** 业务请求失败时 reject 出去的对象 */
export interface RequestError {
  message: unknown
  res?: AxiosResponse
  data?: ResponseBody
}

type RequestMethod = <T = any>(url: string, data?: ReqData, options?: RequestOptions) => Promise<ApiResult<T>>
type MethodName = 'get' | 'head' | 'post' | 'put' | 'patch' | 'delete'

const instance = axios.create({
  baseURL: SettingMer.https,
  timeout: 60000
})

// 存储正在进行的请求（用于去重）
const pendingRequests = new Map<string, Promise<ResponseBody>>()

// 生成请求唯一标识（基于方法、URL和参数）
const generateRequestKey = (options: RequestOptions) => {
  const { method, url, params, data } = options
  // GET类请求用params，其他用data作为参数标识
  const baseParams = ['get', 'head'].includes(method || '') ? params : data;
  let requestParams: unknown;
  if (baseParams instanceof FormData) {
    // 从FormData中提取键值对（兼容分片上传的唯一标识字段）
    const formParams: Record<string, unknown> = {};
    for (const [key, value] of baseParams.entries()) {
      // 对于文件对象，用"[File]"标记（避免序列化失败）
      formParams[key] = value instanceof File ? '[File]' : value;
    }
    requestParams = formParams;
  } else {
    // 非FormData类型：直接使用原始参数（如对象、字符串等）
    requestParams = baseParams;
  }
  // 序列化参数生成唯一key
  return JSON.stringify({
    method,
    url,
    params: requestParams
  })
}

const defaultOpt: RequestOptions = {
  login: true,
  allowRepeat: false, // 新增：是否允许重复请求，默认不允许
  silent: false, // 静默模式：不弹出后端返回的成功/失败提示，由调用方自行展示
  tipDuration: undefined // 成功提示时长（ms）：可选，保存等高频操作传短时长（如 1500），不传用全局默认 5000
}

const AUTH_EXPIRED_STATUS = [410000, 410001, 410002, 40000, 410003]

let refreshTokenPromise: Promise<unknown> | null = null

const updateTokenFromResponse = (res: AxiosResponse) => {
  const authorization = res.headers?.authorization || res.headers?.Authorization
  if (!authorization || !authorization.startsWith('Bearer ')) {
    return
  }

  const refreshedToken = authorization.slice(7)
  const userStore = useUserStore(pinia)
  if (refreshedToken && refreshedToken !== userStore.token) {
    userStore.setToken(refreshedToken)
    EventBus.$emit('auth-token-updated', refreshedToken)
  }
}

const redirectToLogin = () => {
  useUserStore(pinia).resetToken()
  if (location.pathname !== `${roterPre}/login`) {
    location.href = loginHrefWithRedirect(`${location.pathname}${location.search}${location.hash}`)
  }
  return Promise.resolve()
}

const refreshAccessToken = () => {
  syncAuthFromStorage()
  const userStore = useUserStore(pinia)
  if (!userStore.refreshToken) {
    return Promise.reject(new Error('缺少刷新TOKEN'))
  }

  if (!refreshTokenPromise) {
    refreshTokenPromise = userStore.refreshAuthToken().finally(() => {
      refreshTokenPromise = null
    })
  }

  return refreshTokenPromise
}

const handleAuthExpired = (options: RequestOptions): Promise<ResponseBody> => {
  if (options.skipAuthRefresh || options._retry) {
    return redirectToLogin().then(() => Promise.reject({ message: '登录状态已失效' }))
  }

  return refreshAccessToken()
    .then(() => {
      const retryOptions = Object.assign({}, options, {
        headers: Object.assign({}, options.headers || {}),
        allowRepeat: true,
        _retry: true
      })
      return baseRequest(retryOptions)
    }, (error) => {
      return redirectToLogin().then(() => Promise.reject(error))
    })
}

function baseRequest(options: RequestOptions): Promise<ResponseBody> {
  options.url = normalizeRequestUrl(options.url, SettingMer.https)
  // 生成当前请求的唯一标识
  const requestKey = generateRequestKey(options)

  // 如果已有相同请求且不允许重复，直接返回已有请求的Promise
  if (pendingRequests.has(requestKey) && !options.allowRepeat) {
    return pendingRequests.get(requestKey)!
  }

  syncAuthFromStorage()
  const token = useUserStore(pinia).token
  const unique = getShareUnique()
  const headers: Record<string, unknown> = { ...(options.headers || {}) }
  // 后端按请求头 laravel_lang 返回对应语言的提示，前端固定中文
  const lang = 'zh-cn'

  if (token) {
    headers['Authorization'] = 'Bearer ' + token
  }

  if (unique && router.currentRoute.value.name === 'share') {
    headers['Curd-Unique'] = unique
  }
  headers['laravel_lang'] = lang
  options.headers = headers as RequestOptions['headers']

  // 创建请求Promise并缓存
  const requestPromise = new Promise<ResponseBody>((resolve, reject) => {
    instance(options)
      .then((res) => {
        updateTokenFromResponse(res)
        const data: ResponseBody = processResourceData(res.data || {})
        if (res.status !== 200) {
          return reject({ message: '请求失败', res, data })
        } else if (AUTH_EXPIRED_STATUS.indexOf(data.status ?? 0) !== -1) {
          handleAuthExpired(options).then(resolve).catch(reject)
        } else if (data.status === 200) {
          if (data.tips && data.message !== 'ok' && !options.silent) {
            Tips.msgSuccess(data.message ?? '', options.tipDuration)
          }
          return resolve(data)
        } else if (data.status === 400) {
          if (data.tips && data.message !== 'error' && !options.silent) {
            Tips.msgError(data.message ?? '')
          }
          return resolve(data)
        } else if (data.status === 410005) {
          // 处理特定状态码
        } else {
          return reject({ message: data.message, res, data })
        }
      })
      .catch((message) => {
        const data = processResourceData(message.response?.data || {})
        if (AUTH_EXPIRED_STATUS.indexOf(data.status ?? 0) !== -1) {
          handleAuthExpired(options).then(resolve).catch(reject)
          return
        }
        reject({ message })
      })
      .finally(() => {
        // 请求完成（成功/失败）后移除缓存
        pendingRequests.delete(requestKey)
      })
  })

  // 缓存当前请求
  pendingRequests.set(requestKey, requestPromise)
  return requestPromise
}

/**
 * http 请求基础类
 */
const withData =
  (method: MethodName): RequestMethod =>
  (url, data = {}, options = {}) =>
    baseRequest(Object.assign({ url, data, method }, defaultOpt, options))
const withParams =
  (method: MethodName): RequestMethod =>
  (url, params = {}, options = {}) =>
    baseRequest(Object.assign({ url, params, method }, defaultOpt, options))

const request: Record<MethodName, RequestMethod> = {
  post: withData('post'),
  put: withData('put'),
  patch: withData('patch'),
  delete: withData('delete'),
  get: withParams('get'),
  head: withParams('head')
}

export default request
