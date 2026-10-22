import router from '@/router'
import { roterPre, WORKBENCH_PATH } from '@/settings'

/**
 * 路由可访问性判断
 * 菜单变更后动态路由会被重建，用于判断某个路径是否仍然可达
 */

/**
 * 判断路径当前是否可访问
 * @param {string} path 待判断的路径，默认为当前路由
 * @returns {boolean}
 */
export function isCurrentRouteAccessible(path = router.currentRoute.path) {
  const whiteList = [`${roterPre}/login`, `${roterPre}/404`, WORKBENCH_PATH]
  if (whiteList.includes(path) || !path.startsWith(roterPre)) {
    return true
  }
  // 未命中任何动态路由时会落到通配路由，被重定向到 404
  return !router.resolve(path).route.path.startsWith(`${roterPre}/404`)
}

/**
 * 当前路由已不可访问时回退到工作台
 */
export function redirectIfCurrentRouteForbidden() {
  if (!isCurrentRouteAccessible()) {
    router.replace(WORKBENCH_PATH)
  }
}
