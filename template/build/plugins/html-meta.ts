import type { Plugin } from 'vite'

/**
 * 注入页面标题与构建时间，替代 vue-cli 的 html 模板变量（webpackConfig.name / BASE_URL）。
 */
export function htmlMetaPlugin(title: string): Plugin {
  return {
    name: 'oa-html-meta',
    transformIndexHtml: {
      order: 'pre',
      handler(html: string) {
        return {
          html: html.replace(/<%=\s*title\s*%>/g, title),
          tags: [
            {
              tag: 'meta',
              // 用 Intl 而非 moment：只为一个时间戳把 moment 拉进配置加载链不划算
              attrs: { name: 'build-time', content: new Date().toLocaleString('zh-CN', { hour12: false }) },
              injectTo: 'head' as const,
            },
          ],
        }
      },
    },
  }
}
