import Layout from '@/layout'
import { roterPre } from '@/settings'

/**
 * 开发者中心模块前端固定路由
 *
 * - 字典管理、实体设计等二级页从列表页跳入，走 Layout
 * - 流程设计器 / 事件设计器为全屏独立页，不包 Layout，路径也不在 /develop 下，
 *   因此与 developRouter 并列导出
 */
const developRouter = {
  path: `${roterPre}/develop`,
  component: Layout,
  name: 'develop',
  hidden: true,
  meta: { title: '开发者' },
  children: [
    {
      path: 'dictionary/management',
      component: () => import('@/views/develop/dictionary/management'),
      name: 'management',
      meta: { title: '字典管理' }
    },
    {
      path: 'dictionary/optionSetting',
      component: () => import('@/views/develop/dictionary/optionSetting'),
      name: 'optionSetting',
      meta: { title: '字典设置' }
    },
    {
      path: 'crud/design',
      component: () => import('@/views/develop/crud/design'),
      name: 'design',
      meta: { title: '实体设计' }
    }
  ]
}

const developFullScreenRoutes = [
  {
    path: `${roterPre}/process`,
    component: () => import('@/views/develop/crud/process.vue'),
    name: 'process'
  },
  {
    path: `${roterPre}/event`,
    component: () => import('@/views/develop/crud/event.vue'),
    name: 'event'
  }
]

export default [developRouter, ...developFullScreenRoutes]
