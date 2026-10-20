import type { LocationQuery, LocationQueryValue, RouteLocationRaw } from 'vue-router'
import { roterPre } from '@/settings'

const LOGIN_PATH = `${roterPre}/login`

/**
 * 登录页地址。redirect 放进 query，由 vue-router 编码，避免目标地址里的 & 被拆开。
 */
export function loginRouteWithRedirect(fullPath: string) {
  return {
    path: LOGIN_PATH,
    query: { redirect: fullPath }
  }
}

/** 整页跳转登录页。调用方不经过 vue-router，需要自己编码。 */
export function loginHrefWithRedirect(fullPath: string) {
  return `${LOGIN_PATH}?redirect=${encodeURIComponent(fullPath)}`
}

function readRedirect(raw: unknown) {
  if (typeof raw === 'string') return raw
  if (Array.isArray(raw) && typeof raw[0] === 'string') return raw[0]
  return ''
}

function isInternalAdminPath(pathname: string) {
  return pathname === roterPre || pathname.startsWith(`${roterPre}/`)
}

function searchParamsToQuery(params: URLSearchParams): LocationQuery {
  const query: LocationQuery = {}
  params.forEach((value, key) => {
    const current = query[key]
    if (current === undefined) {
      query[key] = value
    } else if (Array.isArray(current)) {
      current.push(value)
    } else {
      query[key] = [current, value]
    }
  })
  return query
}

/**
 * 登录成功后的落点。
 * 只有查询参数名是 uid 时才进入简历页。
 * 其余情况只接受站内 /admin 路径，并丢掉企微回跳用过的 code。
 */
export function resolvePostLoginTarget(rawRedirect: unknown, extraQuery: LocationQuery = {}): RouteLocationRaw {
  const redirect = readRedirect(rawRedirect)
  let url: URL
  try {
    url = new URL(redirect || '/', window.location.origin)
  } catch {
    return { path: '/' }
  }

  if (url.origin !== window.location.origin || !isInternalAdminPath(url.pathname) || url.pathname === LOGIN_PATH) {
    return { path: '/' }
  }

  const uid = url.searchParams.get('uid')
  if (uid) {
    localStorage.setItem('uni', uid)
    return { name: 'resume', query: { uid } }
  }

  url.searchParams.delete('code')
  const query = searchParamsToQuery(url.searchParams)
  Object.entries(extraQuery).forEach(([key, value]) => {
    if (key === 'redirect' || key === 'code' || query[key] !== undefined) return
    query[key] = value as LocationQueryValue | LocationQueryValue[]
  })

  return {
    path: url.pathname,
    query,
    hash: url.hash || undefined
  }
}
