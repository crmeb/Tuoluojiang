import Layout from '@/layout/index.vue'
import { roterPre } from '@/settings'

/**
 * AI 模块前端固定路由
 *
 * - 知识库列表由后台菜单下发，这里只承载列表点不到的编辑页
 * - 二级页统一挂 meta.activeMenu 指向列表页，还原菜单高亮与面包屑父级
 * - hidden 让本组不参与 permission_routes 的菜单渲染，仅作路由承载
 */
const chatRouter = {
  path: `${roterPre}/chat`,
  component: Layout,
  name: 'chat',
  hidden: true,
  meta: { title: 'AI' },
  children: [
    {
      path: 'customerService/setting',
      component: () => import('@/views/chat/setting.vue'),
      name: 'chatCustomerServiceSetting',
      meta: {
        title: '客服设置',
        activeMenu: `${roterPre}/chat/customerService`
      }
    },
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
