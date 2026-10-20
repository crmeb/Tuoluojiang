import Layout from '@/layout/index.vue'
import { roterPre } from '@/settings'

/** 审批设置列表页（后台菜单），新建 / 编辑页挂在它下面 */
const EXAMINE_LIST = `${roterPre}/business/examine/index`

/**
 * 人事-办公审批模块前端固定路由
 *
 * 新建 / 编辑审批流程由审批设置列表页跳入，不在后台 menuList 中；
 * 编辑页通过 query.id 传审批流程 id
 */
const businessRouter = {
  path: `${roterPre}/business`,
  component: Layout,
  name: 'business',
  hidden: true,
  meta: { title: '办公审批' },
  children: [
    {
      path: 'examine/create',
      component: () => import('@/views/business/examine/edit.vue'),
      name: 'businessExamineCreate',
      meta: { title: '新建审批流程', activeMenu: EXAMINE_LIST }
    },
    {
      path: 'examine/edit',
      component: () => import('@/views/business/examine/edit.vue'),
      name: 'businessExamineEdit',
      meta: { title: '编辑审批流程', activeMenu: EXAMINE_LIST }
    }
  ]
}

export default businessRouter
