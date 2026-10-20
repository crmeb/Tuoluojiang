import Layout from '@/layout/index.vue'
import { roterPre } from '@/settings'

/**
 * 库存模块前端固定路由
 *
 * - 侧边栏与面包屑均以后台 menuList 为数据源，二级页不在其中，
 *   因此统一通过 meta.activeMenu 指向所属列表页，还原菜单高亮与面包屑父级
 * - hidden 让本组不参与 permission_routes 的菜单渲染，仅作路由承载
 */
const inventoryRouter = {
  path: `${roterPre}/inventory`,
  component: Layout,
  name: 'inventory',
  hidden: true,
  meta: { title: '库存' },
  children: [
    {
      path: 'inbound/create',
      component: () => import('@/views/inventory/inbound/create.vue'),
      name: 'inventoryInboundCreate',
      meta: {
        title: '新建入库',
        activeMenu: `${roterPre}/inventory/inbound/index`
      }
    },
    {
      path: 'outbound/create',
      component: () => import('@/views/inventory/outbound/create.vue'),
      name: 'inventoryOutboundCreate',
      meta: {
        title: '新建出库',
        activeMenu: `${roterPre}/inventory/outbound/index`
      }
    },
    {
      path: 'transfer/create',
      component: () => import('@/views/inventory/transfer/create.vue'),
      name: 'inventoryTransferCreate',
      meta: {
        title: '新增调拨',
        activeMenu: `${roterPre}/inventory/transfer/index`
      }
    },
    {
      path: 'qualityConvert/create',
      component: () => import('@/views/inventory/qualityConvert/create.vue'),
      name: 'inventoryQualityConvertCreate',
      meta: {
        title: '新增正次转换',
        activeMenu: `${roterPre}/inventory/qualityConvert/index`
      }
    },
    {
      path: 'keeper/create',
      component: () => import('@/views/inventory/keeper/create.vue'),
      name: 'inventoryKeeperCreate',
      meta: {
        title: '新增库管',
        activeMenu: `${roterPre}/inventory/keeper/index`
      }
    },
    {
      path: 'stocktaking/create',
      component: () => import('@/views/inventory/stocktaking/create.vue'),
      name: 'inventoryStocktakingCreate',
      meta: {
        title: '新建盘点',
        activeMenu: `${roterPre}/inventory/stocktaking/index`
      }
    }
  ]
}

export default inventoryRouter
