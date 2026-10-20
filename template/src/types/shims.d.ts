/**
 * 全局补充声明。
 *
 * 存量 JS 模块不在这里兜底 —— `allowJs: true` 已经能让 TS 从 js 源码推断类型，
 * 比统一 declare 成 any 更有价值。个别推断不出来的模块，就近补一个同名 `.d.ts`。
 */

// vite/client 未覆盖、但项目里实际会 import 的静态资源后缀
declare module '*.xlsx' {
  const src: string
  export default src
}

// vue-json-viewer 未附带类型声明，用法见 components/common/jsonViewer.ts
declare module 'vue-json-viewer' {
  import type { Component } from 'vue'
  const JsonViewer: Component
  export default JsonViewer
}

// simple-mind-map 的 xmind 解析子模块未附带类型声明，用法见 components/xmind-editor/utils.ts
declare module 'simple-mind-map/src/parse/xmind' {
  const XmindParse: {
    transformToXmind(data: unknown, fileName: string): Promise<Blob>
  }
  export default XmindParse
}
