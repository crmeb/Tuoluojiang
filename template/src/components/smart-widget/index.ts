import type { App } from 'vue'
import SmartWidget from './SmartWidget.vue'
import SmartWidgetGrid from './SmartWidgetGrid.vue'

export { SmartWidget, SmartWidgetGrid }

/** 与原 vue-smart-widget 插件一致：全局注册 smart-widget / smart-widget-grid */
export default {
  install(app: App) {
    app.component('SmartWidget', SmartWidget)
    app.component('SmartWidgetGrid', SmartWidgetGrid)
  },
}
