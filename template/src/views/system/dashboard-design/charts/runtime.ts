import { defineAsyncComponent, type Component } from 'vue'
import { withApp } from '@/app-context'

// JSON 按名称引用图表，递归容器也需要访问，因此保留全局名称，但只在渲染时加载实现。
// 新增图表类型时，同时更新 charts-schema.js 与此处映射；名称需与 JSON 的 type + '-widget' 一致。
const components: Record<string, () => Promise<{ default: Component }>> = {
  'dashboard-container-widget': () => import('./dashboard-container/dashboard-container-widget.vue'),
  'dashboard-container-item': () => import('./dashboard-container/dashboard-container-item.vue'),
  'section-widget': () => import('./section/section-widget.vue'),
  'section-item': () => import('./section/section-item.vue'),
  'barChart-widget': () => import('./chart-widget/bar-chart-widget.vue'),
  'barXChart-widget': () => import('./chart-widget/bar-xchart-widget.vue'),
  'funnelChart-widget': () => import('./chart-widget/funnel-chart-widget.vue'),
  'lineChart-widget': () => import('./chart-widget/line-chart-widget.vue'),
  'listTable-widget': () => import('./chart-widget/list-table-widget.vue'),
  'pieChart-widget': () => import('./chart-widget/pie-chart-widget.vue'),
  'progressbar-widget': () => import('./chart-widget/progressbar-widget.vue'),
  'radarChart-widget': () => import('./chart-widget/radar-chart-widget.vue'),
  'search-widget': () => import('./chart-widget/search-widget.vue'),
  'statistic-widget': () => import('./chart-widget/statistic-widget.vue'),
  'tab-widget': () => import('./chart-widget/tab-widget.vue')
}

withApp((app) => {
  Object.entries(components).forEach(([name, loader]) => {
    if (!app.component(name)) app.component(name, defineAsyncComponent(loader))
  })
})
