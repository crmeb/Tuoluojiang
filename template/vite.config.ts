import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig, type Plugin } from 'vite'
import vue from '@vitejs/plugin-vue2'
import { visualizer } from 'rollup-plugin-visualizer'
import autoprefixer from 'autoprefixer'
import browserslist from 'browserslist'
import { browserslistToTargets } from 'lightningcss'
import { roterPre, title } from './src/settings.js'
import { welcomePlugin } from "./build/plugins/welcome"
import { htmlMetaPlugin } from "./build/plugins/html-meta"
import { redirectRootPlugin } from "./build/plugins/redirect-root"
import { openInEditorPlugin } from "./build/plugins/open-in-editor"

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const resolve = (dir: string) => path.join(__dirname, dir)

const assetsDir = 'system'

// 必须与 package.json 的 browserslist 逐条对齐：build.target 决定 JS 降级到什么语法，
// browserslist 决定 autoprefixer 加哪些 CSS 前缀，两者说的是同一批浏览器。
// 显式写死而不吃 Vite 默认的 'baseline-widely-available'，是因为那个默认值会随 Vite
// 版本推移而漂移（当前 7.3 恰好等于下面这组），一升级就和 browserslist 悄悄脱钩且不报错。
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
  '^/ws': {
    target: 'http://dev.oa.crmeb.net',
    changeOrigin: true,
    ws: true,
  },
}

/**
 * 只对「确实位于首屏同步依赖里」的基础库做手动分包，让它们拥有独立且稳定的
 * 长缓存文件，业务代码改动不会使其失效。
 *
 * 其余重依赖（xlsx / echarts / wangeditor / ace / pdfjs …）一律不在这里点名：
 * 它们在原 vue.config.js 里标的是 `chunks: 'async'`，而 rollup 没有 initial/async
 * 的概念——一旦手动指定 chunk，只要其中任一模块存在同步引用链，整个 chunk 就会被
 * 提升进首屏（实测 chunk-xlsx 1.8MB 因此被 modulepreload）。交给 rollup 按真实
 * 引用关系自动分包，才是 `chunks: 'async'` 的忠实等价物。
 */
const VENDOR_CHUNKS: Array<[RegExp, string]> = [
  [/[\\/]node_modules[\\/](element-ui|vue|vue-router|vuex|vue-i18n|vue-demi)[\\/]/, 'chunk-vendor'],
]

export default defineConfig(({ command }) => {
  const base = roterPre + '/'
  const isBuild = command === "build";

  return {
    base,
    plugins: [
      welcomePlugin(),
      vue({
        template: {
          compilerOptions: {
            whitespace: 'condense',
          },
        },
      }),
      htmlMetaPlugin(title),
      redirectRootPlugin(base),
      openInEditorPlugin(),
      process.env.ANALYZE &&
      visualizer({
        filename: 'dist/stats.html',
        gzipSize: true,
        brotliSize: true,
        open: true,
      }),
    ].filter(Boolean) as Plugin[],

    resolve: {
      alias: [
        // `~xxx` 是 webpack sass-loader / css-loader 专有的「从 node_modules 解析」写法，
        // Vite 不认识，这里统一抹平：`~@/` 指向 src，其余 `~` 直接剥掉走正常包解析。
        { find: /^~@\//, replacement: resolve('src') + '/' },
        { find: /^~(?!@\/)/, replacement: '' },
        { find: '@', replacement: resolve('src') },
        // vue 2.7 的 package.json 同时给了 import / require 两个条件导出，指向两个不同文件：
        //   import  -> dist/vue.runtime.esm.js   （业务代码 `import Vue from 'vue'` 走这条）
        //   require -> dist/vue.runtime.common.js（element-ui 只有 CJS 入口，其
        //              lib/element-ui.common.js 里 `module.exports = require("vue")`，
        //              build 时由 @rollup/plugin-commonjs 按 require 条件解析，走这条）
        // 两条同时命中就会把 Vue 运行时打进产物两份，各自持有独立的 Dep / observer 体系。
        // 后果集中爆发在 el-table：它把列信息存在 `Vue.extend` 出来的独立 store 实例上，
        // store 归其中一份 Vue 管，而组件的 columns computed 归另一份，依赖收集跨不过去，
        // computed 永远返回初始空数组——表格连表头都渲染不出来（dev 不复现，因为
        // optimizeDeps 预构建已把 element-ui 的 require('vue') 统一到同一份预构建产物）。
        // 锁死到 ESM 单文件，让两条解析路径落到同一个模块。
        { find: /^vue$/, replacement: 'vue/dist/vue.runtime.esm.js' },
      ],
      // 防止 vue 因软链 / 多层 node_modules 出现多个包副本，与上面的入口锁定互为兜底
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
      postcss: {
        plugins: [autoprefixer()],
      },
      // 供 build.cssMinify: 'lightningcss' 读取；targets 仍来自 package.json 的
      // browserslist，与 BUILD_TARGET 的对齐约定不变。
      lightningcss: {
        targets: browserslistToTargets(browserslist()),
      },
      preprocessorOptions: {
        scss: {
          api: 'modern-compiler',
          // node_modules 里的 sass（element-ui 等）整体静音：它们的弃用写法我们改不了，
          // 比逐条列 silenceDeprecations 更省心，也不会顺手把自有样式的告警一起吞掉。
          quietDeps: true,
          // 自有样式同样大量使用 @import 与 `/` 除法，属于已知待改造项，先静音避免刷屏。
          // legacy-js-api 不用列：Vite 7 已默认走 modern-compiler API，那条弃用告警不会再出现。
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
          './src/main.js',
          './src/App.vue',
          './src/permission.js',
          './src/bootstrap/*.js',
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
      rollupOptions: {
        output: {
          // 路由级懒加载会切出大量几百字节的碎片 chunk（实测 727 个 chunk 里有 188 个
          // 不足 2KB，合计才 194KB）。让 rollup 把它们并进相邻 chunk，省下的是请求数，
          // 总体积几乎不变。
          experimentalMinChunkSize: 10_000,
          entryFileNames: `${assetsDir}/js/[name].[hash].js`,
          chunkFileNames: `${assetsDir}/js/[name].[hash].js`,
          assetFileNames: ({ names }) => {
            const info = (names && names[0]) || ''
            if (/\.css$/i.test(info)) return `${assetsDir}/css/[name]-[hash][extname]`
            if (/\.(png|jpe?g|gif|svg|webp|ico)$/i.test(info)) return `${assetsDir}/img/[name]-[hash][extname]`
            if (/\.(woff2?|eot|ttf|otf)$/i.test(info)) return `${assetsDir}/fonts/[name]-[hash][extname]`
            return `${assetsDir}/assets/[name]-[hash][extname]`
          },
          manualChunks(id: string) {
            if (!id.includes('node_modules')) return
            for (const [pattern, chunkName] of VENDOR_CHUNKS) {
              if (pattern.test(id)) return chunkName
            }
            return
          },
        },
      },
    },

    esbuild: {
      // 生产环境去掉 debugger，console 保留（业务侧有依赖日志排查的习惯）
      drop: isBuild ? ['debugger'] : [],
    },
  }
})
