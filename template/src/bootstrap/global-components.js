/**
 * 全局组件注册
 * 使用异步组件加载，减少初始包体积
 */

export function registerComponents(Vue) {
  // 工作流节点组件 - 异步加载
  Vue.component("NodeWrap", () => import("@/components/workFlow/nodeWrap"));
  Vue.component("AddNode", () => import("@/components/workFlow/addNode"));
}
