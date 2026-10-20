import type { Component } from 'vue'
import JsonViewerModule from 'vue-json-viewer'

/**
 * vue-json-viewer 只发布了 CJS 产物，经 Vite 依赖预构建后默认导入拿到的是 `{ default: 组件 }`，
 * 直接注册会报「Component is missing template or render function」。这里统一解一层，
 * 同时兼容构建产物已正确解出默认导出的情况。
 */
const mod = JsonViewerModule as Component & { default?: Component }
const JsonViewer: Component = mod.default ?? mod

export default JsonViewer
