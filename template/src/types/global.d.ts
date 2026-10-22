/**
 * 挂在 window 上的第三方 SDK / 全局变量声明。
 * 新增全局变量时统一往这里加，避免在业务代码里写 `(window as any)`。
 */
export {}

declare global {
  interface Window {
    AMap?: unknown
    WWOpenData?: unknown
  }
}
