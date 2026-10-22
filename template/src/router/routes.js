import { roterPre, WORKBENCH_PATH } from '@/settings'
import Layout from '@/layout'

const defaultRoutes = [
  // 仅做重定向，不渲染组件，因此无需挂 Layout
  {
    path: roterPre,
    redirect: WORKBENCH_PATH
  },
  // 工作台旧地址。authDialog 的 redirect_uri、permission 的路由兜底、
  // route-access 的白名单等多处仍在使用，保留重定向保证其继续可用
  {
    path: `${roterPre}/user/work`,
    redirect: WORKBENCH_PATH
  },
  {
    path: '/',
    component: Layout,
    redirect: WORKBENCH_PATH,
    children: [
      {
        path: WORKBENCH_PATH,
        component: () => import('@/views/user/workbench/index'),
        name: 'dashboard',
        meta: { title: '工作台', icon: 'dashboard' }
      },
      // {
      //   path: `${roterPre}/search`,
      //   name: 'search',
      //   component: () => import('@/views/search/index'),
      //   meta: { title: '搜索' }
      // },
      // {
      //   path: `${roterPre}/user/forum/index`,
      //   name: 'forum',
      //   component: () => import('@/views/user/forum/index'),
      //   meta: { title: '知识社区' }
      // },

      {
        path: `${roterPre}/user/notice/index`,
        name: 'notice',
        component: () => import('@/views/user/notice/index'),
        meta: { title: '企业动态' }
      }
    ]
  },
  {
    path: `${roterPre}/login`,
    name: 'login',
    component: () => import('@/views/login/index'),
    hidden: true
  },
  {
    path: `${roterPre}/share/:id`,
    name: 'share',
    component: () => import('@/views/share'),
    hidden: true
  },
  {
    path: roterPre + '/401',
    component: () => import('@/views/error-page/401'),
    hidden: true
  },
  {
    path: roterPre + '/openFile',
    component: () => import('@/components/openFile/index.vue'),
    name: 'previewPage',
    meta: { title: '文件预览' }
  },

  // {
  //   path: roterPre + '/forum/index',
  //   component: () => import('@/views/user/forum/index'),
  //   name: 'forum',
  //   meta: { title: '知识社区' }
  // },

  {
    path: roterPre + '/dashboard-design',
    component: () => import('@/views/system/dashboard-design/charts/index.vue'),
    name: 'dashboardDesign',
    meta: { title: '图表设计' }
  },
  {
    path: roterPre + '/404',
    name: '404',
    component: () => import('@/views/error-page/404'),
    hidden: true
  }
]
export default defaultRoutes
