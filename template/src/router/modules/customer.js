import Layout from '@/layout'
import { roterPre } from '@/settings'

/**
 * 客户模块前端固定路由
 *
 * - 字典设置、渠道活码等二级页不在后台 menuList 中，统一用 meta.activeMenu
 *   指向所属列表页，还原菜单高亮与面包屑父级
 * - hidden 让本组不参与 permission_routes 的菜单渲染，仅作路由承载
 */
const customerRouter = {
  path: `${roterPre}/customer`,
  component: Layout,
  name: 'customer',
  hidden: true,
  meta: { title: '客户' },
  children: [
    {
      path: 'weChatMass/createGroup',
      component: () => import('@/views/customer/weChatMass/createGroup.vue'),
      name: 'weChatMasscreateGroup',
      meta: {
        activeMenu: `${roterPre}/customer/weChatMass/createGroupList`,
        title: (route) => (route.query.id ? '编辑渠道活码' : '新建渠道活码')
      }
    },
    {
      path: 'weChatMass/groupCodeStatistics',
      component: () => import('@/views/customer/weChatMass/groupCodeStatistics.vue'),
      name: 'weChatMassgroupCodeStatistics',
      meta: {
        activeMenu: `${roterPre}/customer/weChatMass/createGroupList`,
        title: '渠道码数据分析'
      }
    },
    {
      path: 'weChatMass/groupInvoteConfig/form',
      component: () => import('@/views/customer/weChatMass/groupInvoteConfigForm.vue'),
      name: 'groupInvoteConfigForm',
      meta: {
        activeMenu: `${roterPre}/customer/weChatMass/groupInvoteConfig`,
        title: (route) => (route.query.id ? '编辑配置' : '新增配置')
      }
    },
    {
      path: 'clue/dictSetting',
      component: () => import('@/views/customer/clue/dictSetting'),
      name: 'clueDictSetting',
      meta: { title: '线索字典设置' }
    },
    {
      path: 'list/dictSetting',
      component: () => import('@/views/customer/list/dictSetting'),
      name: 'customerDictSetting',
      meta: { title: '客户字典设置' }
    },
    {
      path: 'opportunityManagement/dictSetting',
      component: () => import('@/views/customer/opportunityManagement/dictSetting'),
      name: 'opportunityDictSetting',
      meta: { title: '商机字典设置' }
    },
    {
      path: 'contract/dictSetting',
      component: () => import('@/views/customer/contract/dictSetting'),
      name: 'contractDictSetting',
      meta: { title: '订单字典设置' }
    },
    {
      path: 'liaison/dictSetting',
      component: () => import('@/views/customer/liaison/dictSetting'),
      name: 'liaisonDictSetting',
      meta: { title: '联系人字典设置' }
    },
    {
      path: 'product/dictSetting',
      component: () => import('@/views/customer/product/dictSetting'),
      name: 'productDictSetting',
      meta: { title: '产品字典设置' }
    },
    {
      path: 'product/addProduct',
      component: () => import('@/views/customer/product/addProduct.vue'),
      name: 'addProduct',
      meta: { title: '添加产品' }
    }
  ]
}

export default customerRouter
