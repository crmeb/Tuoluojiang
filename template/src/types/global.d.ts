/**
 * 挂在 window 上的第三方 SDK / 全局变量声明。
 * 新增全局变量时统一往这里加，避免在业务代码里写 `(window as any)`。
 */
export {}

declare module 'vue' {
  interface ComponentCustomProperties {
    $icon: typeof import('@/bootstrap/element-icons').toIcon
    /** 表单设计器轻量多语言，见 utils/smart-vue-i18n */
    $st: typeof import('@/utils/smart-vue-i18n/index').st
    $st2: typeof import('@/utils/smart-vue-i18n/index').st2
    $si18n: typeof import('@/utils/smart-vue-i18n/index').si18n
    /** Element Plus 全量注册时挂载的全局方法 */
    $message: typeof import('element-plus').ElMessage
  }
}

declare global {
  interface Window {
    AMap?: unknown
    WWOpenData?: unknown
    /** 统计脚本加载前的命令队列 */
    _hmt?: unknown[]
    /** 仅开发环境暴露的 app 实例，便于控制台调试 */
    __APP__?: import('vue').App
  }
}
