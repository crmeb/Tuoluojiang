import { createPinia } from 'pinia'

/**
 * 全局 Pinia 实例。
 * 组件内直接调用 useXxxStore()；路由守卫、请求层、指令等组件外代码
 * 传入该实例（useXxxStore(pinia)），不依赖 app.use 之后才设置的 activePinia。
 */
export const pinia = createPinia()

export default pinia
