import NProgress from 'nprogress' // progress bar
import 'nprogress/nprogress.css' // progress bar style
import getPageTitle from '@/utils/get-page-title'
import { roterPre, WORKBENCH_PATH } from '@/settings'
import { getMenus, isEmptyMenuError } from '@/utils/menu'
import { isCurrentRouteAccessible } from '@/utils/route-access'
import { aiFloatEntryController } from '@/libs/ai'

NProgress.configure({ showSpinner: false }) // NProgress Configuration

const whiteList = [`login`, `share`, '/auth-redirect', '404'] // no redirect whitelist
let isPermissionGuardRegistered = false

/**
 * 目标页是否声明了「跨模块进入时收起二级菜单」。
 * 取整条匹配链，父级路由记录上的标记会被子路由继承。
 * 标记来源：@/router 按组件目录自动打（后端菜单）、@/router/modules 手写（前端固定路由）。
 */
const isAutoCollapseRoute = (route) => route.matched.some((record) => record.meta && record.meta.autoCollapse)

export function setupPermissionGuard(router, store) {
  if (isPermissionGuardRegistered) {
    return
  }
  isPermissionGuardRegistered = true

  router.beforeEach(async (to, from, next) => {
    NProgress.start()
    document.title = getPageTitle(to.meta.title)
    const hasToken = store.getters.token

    if (hasToken) {
      if (to.path === `${roterPre}/login`) {
        next({ path: '/' })
        NProgress.done()
        return
      }

      if (store.getters.menuList.length <= 0) {
        try {
          // 空菜单的登录态清理由 getMenus 内部完成，这里只负责导航
          await getMenus({ redirectOnEmpty: false })
        } catch (e) {
          if (isEmptyMenuError(e)) {
            next(`${roterPre}/login`)
            NProgress.done()
            return
          }
          console.warn('[permission] 获取菜单失败，已中止本次导航', e)
          next(false)
          NProgress.done()
          return
        }
        // 模块开关变更后目标路由可能已被移除，回退工作台避免落到 404
        if (!isCurrentRouteAccessible(to.path)) {
          next({ path: WORKBENCH_PATH, replace: true })
          return
        }
        next({ ...to, replace: true })
        return
      }

      // AI 悬浮入口不阻塞路由导航，失败时由控制器内部降级处理。
      aiFloatEntryController.ensure(store.getters.token, to.path)
      next()
      NProgress.done()
    } else if (whiteList.includes(to.name)) {
      next()
    } else {
      await store.dispatch('user/resetToken')
      next(`${roterPre}/login?redirect=${to.fullPath}`)
      NProgress.done()
    }
  })

  router.afterEach((to, from) => {
    // 只在从别的模块进来时收起，模块内部互相跳转保持用户当前的展开状态。
    // 首屏 from 是 START 路由、matched 为空，会被判成「来自其他模块」而收起，与改动前一致。
    if (isAutoCollapseRoute(to) && !isAutoCollapseRoute(from) && store.state.app.sidebar.opened) {
      store.dispatch('app/closeSideBar', { withoutAnimation: false })
    }
    NProgress.done()
  })
}
