import type { Router } from 'vue-router'
import NProgress from 'nprogress' // progress bar
import 'nprogress/nprogress.css' // progress bar style
import getPageTitle from '@/utils/get-page-title'
import { roterPre, WORKBENCH_PATH } from '@/settings'
import { loginRouteWithRedirect } from '@/utils/login-redirect'
import { getMenus, isEmptyMenuError } from '@/utils/menu'
import { isCurrentRouteAccessible } from '@/utils/route-access'
import { aiFloatEntryController } from '@/libs/ai'
import pinia from '@/stores'
import { useUserStore } from '@/stores/user'
import { startRouteLoading, finishRouteLoading } from '@/router/loading'

NProgress.configure({ showSpinner: false }) // NProgress Configuration

const whiteList: unknown[] = [`login`, `share`, '/auth-redirect', '404'] // no redirect whitelist
let isPermissionGuardRegistered = false

export function setupPermissionGuard(router: Router) {
  if (isPermissionGuardRegistered) {
    return
  }
  isPermissionGuardRegistered = true

  router.beforeEach(async (to) => {
    NProgress.start()
    startRouteLoading()
    document.title = getPageTitle()
    const userStore = useUserStore(pinia)
    const hasToken = userStore.token

    if (hasToken) {
      if (to.path === `${roterPre}/login`) {
        return { path: '/' }
      }

      // 空数组只表示「没有可显示的菜单」。是否拉过菜单看 menusLoaded，避免滤空后原地 replace。
      if (!userStore.menusLoaded) {
        try {
          // 空菜单的登录态清理由 getMenus 内部完成，这里只负责导航
          await getMenus({ redirectOnEmpty: false })
        } catch (e) {
          if (isEmptyMenuError(e)) {
            return `${roterPre}/login`
          }
          console.warn('[permission] 获取菜单失败，已中止本次导航', e)
          return false
        }
        // 模块开关变更后目标路由可能已被移除，回退工作台避免落到 404。
        // 已经在工作台时不再 replace 自己。
        if (!isCurrentRouteAccessible(to.path) && to.path !== WORKBENCH_PATH) {
          return { path: WORKBENCH_PATH, replace: true }
        }
        // 菜单注册了新路由，重新解析目标地址，避免沿用首轮匹配到的兜底路由。
        return { path: to.path, query: to.query, hash: to.hash, replace: true }
      }

      // 菜单已加载但被滤空时，深链不能再按「未加载」重拉。只去一次工作台。
      if (userStore.menuList.length === 0 && to.path !== WORKBENCH_PATH && !isCurrentRouteAccessible(to.path)) {
        return { path: WORKBENCH_PATH, replace: true }
      }

      // 菜单已加载后，未知地址沿用原来的 /404 跳转；首屏则先让上面的菜单加载分支尝试补齐动态路由。
      if (to.matched.some((record) => record.path === '/:pathMatch(.*)*')) {
        return `${roterPre}/404`
      }

      // AI 悬浮入口不阻塞路由导航，失败时由控制器内部降级处理。
      aiFloatEntryController.ensure(userStore.token, to.path)
    } else if (!whiteList.includes(to.name)) {
      userStore.resetToken()
      return loginRouteWithRedirect(to.fullPath)
    }
  })

  router.afterEach(() => {
    finishRouteLoading()
    NProgress.done()
  })

  router.onError(() => {
    finishRouteLoading()
    NProgress.done()
  })
}
