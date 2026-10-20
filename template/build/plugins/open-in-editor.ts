import launchEditorMiddleware from 'launch-editor-middleware'
import type { Plugin } from 'vite'

/** Vue DevTools 与 Vite 错误覆盖层共用的端点名 */
const ENDPOINT = '__open-in-editor'

/**
 * 恢复「在编辑器中打开」：dev server 上挂 launch-editor 中间件，收到文件路径后
 * 拉起本机编辑器并跳到指定行列。
 *
 * vue-cli 时代这件事由 webpack-dev-server 的 before 钩子接 launch-editor-middleware
 * 完成，Vite 只在客户端发请求、不内置服务端实现，所以迁移后这个功能就断了：
 * Vue DevTools 里点文件图标没反应，控制台报 404。
 *
 * 两个调用方对 base 的处理并不一致，两条路径都得挂：
 * - Vue DevTools（legacy / Vue 2）把端点硬编码成根路径 `/__open-in-editor`，不带 base；
 * - Vite 错误覆盖层发的是 `${base}__open-in-editor`（见 vite/dist/client/client.mjs），
 *   本项目 base 为 `/admin/`，与上面不是同一个 URL。
 *
 * 编辑器默认由 launch-editor 探测本机正在运行的进程（Windows 走 Get-Process）。探测
 * 不准时用 `LAUNCH_EDITOR` 环境变量覆盖，例如 `LAUNCH_EDITOR=code pnpm dev`；也可以
 * 直接给本插件传入编辑器命令。
 *
 * @param specifiedEditor 编辑器命令或可执行文件路径，省略则自动探测
 */
export function openInEditorPlugin(specifiedEditor?: string): Plugin {
  return {
    name: 'oa-open-in-editor',
    apply: 'serve',
    configureServer(server) {
      const { root, base, logger } = server.config

      // 相对路径按项目根解析：Vue DevTools 传的是 __file（dev 下为绝对路径），
      // 而错误覆盖层的 codeframe 可能给出相对路径，交给 srcRoot 兜住。
      const middleware = launchEditorMiddleware(specifiedEditor, root, (file, errorMessage) => {
        logger.error(
          `[open-in-editor] 打开 ${file} 失败：${errorMessage || '未能识别本机编辑器'}\n` +
            `  可用 LAUNCH_EDITOR 指定编辑器命令后重试，例如 LAUNCH_EDITOR=code pnpm dev`
        )
      })

      // base 为 '/' 时两条路径相同，用 Set 去重避免重复注册
      for (const endpoint of new Set([`/${ENDPOINT}`, `${base}${ENDPOINT}`])) {
        server.middlewares.use(endpoint, middleware)
      }
    },
  }
}
