import Layout from '@/layout'
import { roterPre } from '@/settings'

/**
 * 项目模块前端固定路由
 *
 * 项目详情、项目动态由列表页跳入，不在后台 menuList 中
 */
const programRouter = {
  path: `${roterPre}/program`,
  component: Layout,
  name: 'program',
  hidden: true,
  meta: { title: '项目' },
  children: [
    {
      path: 'programList/taskDetails',
      component: () => import('@/views/program/programList/taskDetails.vue'),
      name: 'taskDetails',
      meta: { title: '项目详情' }
    },
    {
      path: 'programList/dynamics',
      component: () => import('@/views/program/programList/dynamics.vue'),
      name: 'dynamics',
      meta: { title: '项目动态' }
    }
  ]
}

export default programRouter
