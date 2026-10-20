/**
 * 应用实例上下文。
 *
 * Vue 3 没有全局 Vue 构造器，`Vue.component` / `Vue.extend` 这类「模块一加载就往全局注册」
 * 的写法需要拿到 app 实例。这里在 main 创建 app 后调用 setApp 登记，之前的注册请求先排队。
 */
import { createVNode, render, type App, type AppContext, type Component } from 'vue'

let currentApp: App | null = null
const pending: Array<(app: App) => void> = []

export function setApp(app: App) {
  currentApp = app
  pending.splice(0).forEach((fn) => fn(app))
}

/** app 已创建则立即执行，否则等 setApp 时执行 */
export function withApp(fn: (app: App) => void) {
  if (currentApp) {
    fn(currentApp)
  } else {
    pending.push(fn)
  }
}

/** 全局注册组件（替代 Vue.component），可在 app 创建前调用 */
export function registerGlobalComponent(name: string, component: Component) {
  withApp((app) => app.component(name, component))
}

export function getAppContext(): AppContext | null {
  return currentApp ? currentApp._context : null
}

/**
 * 命令式挂载组件到 body（替代 `new (Vue.extend(Comp))().$mount()`），
 * 继承 app 的全局组件、插件、store、router、i18n。
 * 返回的 vm 是组件公开实例，可直接读写 data、调用 methods。
 */
export function mountComponent<T = any>(component: Component, props: Record<string, any> = {}) {
  const container = document.createElement('div')
  const vnode = createVNode(component, props)
  vnode.appContext = getAppContext()
  render(vnode, container)
  document.body.appendChild(container)
  return {
    vm: vnode.component!.proxy as T,
    el: container,
    unmount() {
      render(null, container)
      container.remove()
    },
  }
}
