/// <reference types="vite/client" />

/**
 * 项目自有的环境变量声明。
 *
 * 全部使用 Vite 默认的 `VITE_` 前缀——只有该前缀的变量才会被注入客户端。
 * 业务代码不要直接读 `import.meta.env`，统一走 `@/config/env` 的具名导出。
 */
interface ImportMetaEnv {
  /** 后端接口基础地址 */
  readonly VITE_BASE_API?: string
  /** WebSocket 连接地址 */
  readonly VITE_WS_URL?: string
  /** AI 服务基础地址 */
  readonly VITE_AI_BASE_URL?: string
  /** 演示站开关，'true' 为开启 */
  readonly VITE_DEMO_SUPPORT?: string
  /** 演示站预填账号 */
  readonly VITE_DEMO_ACCOUNT?: string
  /** 演示站预填密码 */
  readonly VITE_DEMO_PASSWORD?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
