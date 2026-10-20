/**
 * 全局组件注册
 * 使用异步组件加载，减少初始包体积
 */
import { defineAsyncComponent, type App } from "vue";

export function registerComponents(app: App) {
  // 工作流节点组件 - 异步加载
  app.component("NodeWrap", defineAsyncComponent(() => import("@/components/workFlow/nodeWrap.vue")));
  app.component("AddNode", defineAsyncComponent(() => import("@/components/workFlow/addNode.vue")));
}
