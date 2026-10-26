import type { Plugin } from 'vite'

/**
 * dev 环境把根路径重定向到路由前缀，等价于原 vue.config.js 里 devServer.before 的 app.get(/^\/$/)。
 */
export function redirectRootPlugin(base: string): Plugin {
  return {
    name: 'oa-redirect-root',
    apply: 'serve',
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        const url = (req.url || '').split('?')[0]
        if (url === '/') {
          res.writeHead(302, { Location: base })
          res.end()
          return
        }
        next()
      })
    },
  }
}
