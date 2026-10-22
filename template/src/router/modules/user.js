import Layout from '@/layout'
import { roterPre } from '@/settings'

/**
 * 用户中心模块前端固定路由（原 router/company.js 的用户相关部分）
 *
 * 简历、云盘、待办、日程、订阅消息等不走后台菜单，统一挂在 /user 下
 */
const userRouter = {
  path: `${roterPre}/user`,
  name: 'users',
  meta: {
    title: '用户管理'
  },
  alwaysShow: true,
  component: Layout,
  children: [
    {
      path: 'resume',
      component: () => import('@/views/user/resume/index'),
      name: 'resume',
      meta: { title: '我的简历' }
    },
    {
      path: 'cloudfile/index',
      component: () => import('@/views/user/cloudfile/index'),
      name: 'cloudfile',
      meta: { title: '云盘' }
    },
    {
      path: 'todo',
      component: () => import('@/views/user/todo/index'),
      name: 'todo',
      meta: { title: '待办' }
    },
    {
      path: 'calendar',
      component: () => import('@/views/user/calendar/index'),
      name: 'calendar',
      meta: { title: '日程' }
    },
    {
      path: 'news/subscribe',
      component: () => import('@/views/user/news/subscribe'),
      name: 'subscribe',
      meta: { title: '订阅消息' }
    }
  ]
}

export default userRouter
