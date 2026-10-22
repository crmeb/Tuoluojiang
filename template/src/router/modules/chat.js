import Layout from '@/layout'
import { roterPre } from '@/settings'

/**
 * AI 模块前端固定路由
 *
 * - 知识库列表由后台菜单下发，这里只承载列表点不到的编辑页
 * - 二级页统一挂 meta.activeMenu 指向列表页，还原菜单高亮与面包屑父级
 * - hidden 让本组不参与 permission_routes 的菜单渲染，仅作路由承载
 * - meta.autoCollapse 挂在父级由子路由继承：本组不走 getRouterMenus，
 *   拿不到那边按组件目录自动打的标记，只能在这里声明一次
 */
const chatRouter = {
  path: `${roterPre}/chat`,
  component: Layout,
  name: 'chat',
  hidden: true,
  meta: { title: 'AI', autoCollapse: true },
  children: [
    {
      // 知识库 id 走 query（?id=1），不用路径参数
      path: 'knowledge/edit',
      component: () => import('@/views/chat/knowledge/edit.vue'),
      name: 'chatKnowledgeEdit',
      meta: {
        title: '知识库详情',
        activeMenu: `${roterPre}/chat/knowledge`
      }
    }
  ]
}

export default chatRouter
