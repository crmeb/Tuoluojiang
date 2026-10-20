/**
 * 环境变量统一出口。
 *
 * 业务代码一律从这里取值，不要在各处直接写 `import.meta.env.VITE_XXX`：
 * - 变量重命名、加默认值、改类型只需动这一个文件
 * - env 取到的值永远是 `string | undefined`，在这里一次性收敛成好用的类型
 *   （例如开关类变量直接给出 boolean，调用方不用再各自写 `=== 'true'`）
 *
 * 新增变量的步骤：`.env.*` 里加 `VITE_` 开头的项 → 在 `src/types/env.d.ts`
 * 补类型 → 在本文件导出一个语义化常量。
 */

const env = import.meta.env

/** 后端接口基础地址。留空时由 `@/libs/settingMer` 回落到当前站点 origin */
export const BASE_API: string = env.VITE_BASE_API ?? ''

/** WebSocket 连接地址。留空时由 `@/libs/settingMer` 依据接口地址推导 */
export const WS_URL: string = env.VITE_WS_URL ?? ''

/** AI 服务基础地址。留空时回落到接口地址 / 当前 origin */
export const AI_BASE_URL: string = env.VITE_AI_BASE_URL ?? ''

/** 演示站模式：开启后登录页预填账号、页面显示悬浮客服（`pnpm run build:demo`） */
export const DEMO_SUPPORT: boolean = env.VITE_DEMO_SUPPORT === 'true'

/** 演示站预填账号，仅 `build:demo` 注入 */
export const DEMO_ACCOUNT: string = env.VITE_DEMO_ACCOUNT ?? ''

/** 演示站预填密码，仅 `build:demo` 注入 */
export const DEMO_PASSWORD: string = env.VITE_DEMO_PASSWORD ?? ''

// ---- 以下为 Vite 内置变量，同样从这里统一出口，避免业务代码直接摸 import.meta ----

/** 部署基础路径，等于 vite.config.ts 里的 `base`（即 `roterPre + '/'`） */
export const BASE_URL: string = env.BASE_URL

/** 是否为开发模式 */
export const IS_DEV: boolean = env.DEV

/** 是否为生产构建 */
export const IS_PROD: boolean = env.PROD
