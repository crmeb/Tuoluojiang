import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig, type UserConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import autoprefixer from 'autoprefixer'
import browserslist from 'browserslist'
import { browserslistToTargets } from 'lightningcss'
import { roterPre, title } from './src/settings.ts'
import { welcomePlugin } from "./build/plugins/welcome.ts"
import { htmlMetaPlugin } from "./build/plugins/html-meta.ts"
import { redirectRootPlugin } from "./build/plugins/redirect-root.ts"
import { openInEditorPlugin } from "./build/plugins/open-in-editor.ts"

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const resolve = (dir: string) => path.join(__dirname, dir)

const assetsDir = 'system'

// 必须与 package.json 的 browserslist 逐条对齐：build.target 决定 JS 降级到什么语法，
// browserslist 决定 autoprefixer 加哪些 CSS 前缀，两者说的是同一批浏览器。
// 显式写死而不吃 Vite 默认的 'baseline-widely-available'，是因为那个默认值会随 Vite
// 版本推移而漂移，一升级就和 browserslist 悄悄脱钩且不报错。
const BUILD_TARGET = ['chrome107', 'edge107', 'firefox104', 'safari16']

/** dev server 与 preview server 共用一份后端代理，避免两处各写一遍后失配 */
const DEV_PROXY = {
  '^/api': {
    target: 'http://dev.oa.crmeb.net',
    changeOrigin: true,
  },
  '^/uploads': {
    target: 'http://dev.oa.crmeb.net',
    changeOrigin: true,
  },
  // 后端静态资源（如 AI 模型供应商图标 /static/ai/providers/*.svg）
  '^/static': {
    target: 'http://dev.oa.crmeb.net',
    changeOrigin: true,
  },
  '^/ws': {
    target: 'http://dev.oa.crmeb.net',
    changeOrigin: true,
    ws: true,
  },
}

/**
 * 只对「确实位于首屏同步依赖里」的基础库单独分包，让它们拥有独立且稳定的
 * 长缓存文件，业务代码改动不会使其失效。
 *
 * 其余重依赖（xlsx / echarts / wangeditor / ace / pdfjs …）一律不在这里点名：
 * 只要其中任一模块存在同步引用链，整个分组就会被提升进首屏。交给打包器按真实
 * 引用关系自动分包，才能保证它们只在用到的页面异步加载。
 */
const VENDOR_CHUNKS = [
  {
    name: 'chunk-vendor',
    test: /[\\/]node_modules[\\/](element-plus|@element-plus|@vue|vue|vue-router|pinia|vue-demi)[\\/]/,
  },
]

export default defineConfig(async (): Promise<UserConfig> => {
  const base = roterPre + '/'

  return {
    base,
    plugins: [
      welcomePlugin(),
      vue(),
      htmlMetaPlugin(title),
      redirectRootPlugin(base),
      openInEditorPlugin(),
      ...(process.env.ANALYZE
        ? [
            (await import('rollup-plugin-visualizer')).visualizer({
              filename: 'dist/stats.html',
              gzipSize: true,
              brotliSize: true,
              open: true,
            }),
          ]
        : []),
    ],

    resolve: {
      alias: [
        // `~xxx` 是 webpack sass-loader / css-loader 专有的「从 node_modules 解析」写法，
        // Vite 不认识，这里统一抹平：`~@/` 指向 src，其余 `~` 直接剥掉走正常包解析。
        { find: /^~@\//, replacement: resolve('src') + '/' },
        { find: /^~(?!@\/)/, replacement: '' },
        { find: '@', replacement: resolve('src') },
      ],
      // 防止 vue 因软链 / 多层 node_modules 出现多个包副本
      dedupe: ['vue'],
      // .ts / .tsx 排在 .js 之前：同名文件优先命中新写的 TS 版本，
      // 便于存量 js 模块被逐个改写为 ts 而无需改动调用方的 import 路径。
      extensions: ['.mjs', '.ts', '.tsx', '.js', '.jsx', '.json', '.vue'],
    },

    css: {
      // 转换阶段必须留在 postcss：`src/styles/variables.module.scss` 靠 ICSS 的
      // `:export` 把 SCSS 变量交给 JS（见 Sidebar/index.vue），而 lightningcss 的
      // CSS Modules 实现不认这条语法——实测切过去后导出对象为空，侧边栏取色全成
      // undefined。lightningcss 只用在 build.cssMinify 上，那一步不碰 CSS Modules。
      // autoprefixer 与 Vite 经 pnpm 解析到不同的 PostCSS 小版本；运行时接口相同。
      postcss: {
        plugins: [autoprefixer()],
      } as unknown as NonNullable<UserConfig['css']>['postcss'],
      // 供 build.cssMinify: 'lightningcss' 读取；targets 仍来自 package.json 的
      // browserslist，与 BUILD_TARGET 的对齐约定不变。
      lightningcss: {
        targets: browserslistToTargets(browserslist()),
      },
      preprocessorOptions: {
        scss: {
          // node_modules 里的 sass（element-plus 等）整体静音：它们的弃用写法我们改不了，
          // 比逐条列 silenceDeprecations 更省心，也不会顺手把自有样式的告警一起吞掉。
          quietDeps: true,
          // 自有样式同样大量使用 @import 与 `/` 除法，属于已知待改造项，先静音避免刷屏。
          silenceDeprecations: ['import', 'global-builtin', 'slash-div', 'color-functions'],
        },
      },
    },

    server: {
      host: true,
      port: 9527,
      // 预热首屏必经的模块，避免冷启动后第一次请求时才逐个 transform
      warmup: {
        clientFiles: [
          './src/main.ts',
          './src/App.vue',
          './src/permission.ts',
          './src/bootstrap/*.ts',
          './src/layout/index.vue',
          './src/layout/components/**/*.vue',
        ],
      },
      proxy: DEV_PROXY,
    },

    // `vite preview` 不会继承 server.proxy，不带上就没法拿构建产物联调后端
    preview: {
      proxy: DEV_PROXY,
    },

    build: {
      assetsDir,
      target: BUILD_TARGET,
      cssMinify: 'lightningcss',
      reportCompressedSize: false,
      chunkSizeWarningLimit: 1200,
      rolldownOptions: {
        output: {
          entryFileNames: `${assetsDir}/js/[name].[hash].js`,
          chunkFileNames: `${assetsDir}/js/[name].[hash].js`,
          assetFileNames: ({ names }) => {
            const info = (names && names[0]) || ''
            if (/\.css$/i.test(info)) return `${assetsDir}/css/[name]-[hash][extname]`
            if (/\.(png|jpe?g|gif|svg|webp|ico)$/i.test(info)) return `${assetsDir}/img/[name]-[hash][extname]`
            if (/\.(woff2?|eot|ttf|otf)$/i.test(info)) return `${assetsDir}/fonts/[name]-[hash][extname]`
            return `${assetsDir}/assets/[name]-[hash][extname]`
          },
          codeSplitting: {
            groups: VENDOR_CHUNKS,
          },
        },
      },
    },
  }
})
