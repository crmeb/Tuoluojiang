import Layout from '@/layout/index.vue'
import { roterPre } from '@/settings'

/**
 * 人事模块前端固定路由
 *
 * 考勤设置的新增页由设置列表页跳入，不在后台 menuList 中
 */
const hrRouter = {
  path: `${roterPre}/hr`,
  component: Layout,
  name: 'hr',
  hidden: true,
  meta: { title: '人事' },
  children: [
    {
      path: 'attendance/setting/addConent',
      component: () => import('@/views/hr/attendance/setting/addConent.vue'),
      name: 'addConent',
      meta: { title: '新增考勤设置' }
    }
  ]
}

export default hrRouter
