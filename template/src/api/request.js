import axios from 'axios'
import store from '@/store'
import router from '../router'
import SettingMer from '@/libs/settingMer'
import Tips from '@/utils/tips'
import { roterPre } from '@/settings'
import { normalizeRequestUrl, processResourceData } from '@/utils/resourceUtil'
import { EventBus } from '@/libs/bus'

const instance = axios.create({
  baseURL: SettingMer.https,
  timeout: 60000
})

// 存储正在进行的请求（用于去重）
const pendingRequests = new Map()

// 生成请求唯一标识（基于方法、URL和参数）
const generateRequestKey = (options) => {
  const { method, url, params, data } = options
  // GET类请求用params，其他用data作为参数标识
  let baseParams = ['get', 'head'].includes(method) ? params : data;
  let requestParams;
  if (baseParams instanceof FormData) {
    // 从FormData中提取键值对（兼容分片上传的唯一标识字段）
    requestParams = {};
    for (const [key, value] of baseParams.entries()) {
      // 对于文件对象，用"[File]"标记（避免序列化失败）
      requestParams[key] = value instanceof File ? '[File]' : value;
    }
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

const defaultOpt = {
  login: true,
  allowRepeat: false, // 新增：是否允许重复请求，默认不允许
  silent: false, // 静默模式：不弹出后端返回的成功/失败提示，由调用方自行展示
  tipDuration: undefined // 成功提示时长（ms）：可选，保存等高频操作传短时长（如 1500），不传用全局默认 5000
}

const AUTH_EXPIRED_STATUS = [410000, 410001, 410002, 40000, 410003]

let refreshTokenPromise = null

const updateTokenFromResponse = (res) => {
  const authorization = res.headers?.authorization || res.headers?.Authorization
  if (!authorization || !authorization.startsWith('Bearer ')) {
    return
  }

  const refreshedToken = authorization.slice(7)
  if (refreshedToken && refreshedToken !== store.getters.token) {
    store.commit('user/SET_TOKEN', refreshedToken)
    EventBus.$emit('auth-token-updated', refreshedToken)
  }
}

const redirectToLogin = () => {
  return store.dispatch('user/resetToken').then(() => {
    if (location.pathname !== '/admin/login') {
      location.href = `${roterPre}/login?redirect=${location.pathname}`
    }
  })
}

const refreshAccessToken = () => {
  if (!store.getters.refreshToken) {
    return Promise.reject(new Error('缺少刷新TOKEN'))
  }

  if (!refreshTokenPromise) {
    refreshTokenPromise = store.dispatch('user/refreshToken').finally(() => {
      refreshTokenPromise = null
    })
  }

  return refreshTokenPromise
}

const handleAuthExpired = (options) => {
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

function baseRequest(options) {
  options.url = normalizeRequestUrl(options.url, SettingMer.https)
  // 生成当前请求的唯一标识
  const requestKey = generateRequestKey(options)

  // 如果已有相同请求且不允许重复，直接返回已有请求的Promise
  if (pendingRequests.has(requestKey) && !options.allowRepeat) {
    return pendingRequests.get(requestKey)
  }

  const token = store.getters.token
  const unique = localStorage.getItem('unique')
  const headers = options.headers || {}
  const lang = store.getters.lang || 'zh-cn'

  if (token) {
    headers['Authorization'] = 'Bearer ' + token
  }

  if (unique && router.app._route && router.app._route.name === 'share') {
    headers['Curd-Unique'] = unique
  }
  headers['laravel_lang'] = lang
  options.headers = headers

  // 创建请求Promise并缓存
  const requestPromise = new Promise((resolve, reject) => {
    instance(options)
      .then((res) => {
        updateTokenFromResponse(res)
        const data = processResourceData(res.data || {})
        if (res.status !== 200) {
          return reject({ message: '请求失败', res, data })
        } else if (AUTH_EXPIRED_STATUS.indexOf(data.status) !== -1) {
          handleAuthExpired(options).then(resolve).catch(reject)
        } else if (data.status === 200) {
          if (data.tips && data.message !== 'ok' && !options.silent) {
            Tips.msgSuccess(data.message, options.tipDuration)
          }
          return resolve(data)
        } else if (data.status === 400) {
          if (data.tips && data.message !== 'error' && !options.silent) {
            Tips.msgError(data.message)
          }
          return resolve(data, res)
        } else if (data.status === 410005) {
          // 处理特定状态码
        } else {
          return reject({ message: data.message, res, data })
        }
      })
      .catch((message) => {
        const data = processResourceData(message.response?.data || {})
        if (AUTH_EXPIRED_STATUS.indexOf(data.status) !== -1) {
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
const request = ['post', 'put', 'patch', 'delete'].reduce((request, method) => {
    request[method] = (url, data = {}, options = {}) => {
      return baseRequest(Object.assign({ url, data, method }, defaultOpt, options))
    }
    return request
  }, {})

;['get', 'head'].forEach((method) => {
  request[method] = (url, params = {}, options = {}) => {
    return baseRequest(Object.assign({ url, params, method }, defaultOpt, options))
  }
})

export default request
