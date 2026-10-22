import { roterPre } from '@/settings'

/**
 * 系统设置模块前端固定路由
 *
 * 图标选择、权限选择、图片上传均为独立全屏页（组件直接作为页面被 iframe / 弹窗引用），
 * 不包 Layout，因此以数组形式平铺导出
 */
const settingRoutes = [
  {
    path: `${roterPre}/setting/icons`,
    component: () => import('@/components/form-common/select-icon.vue'),
    name: 'icons'
  },
  {
    path: `${roterPre}/setting/auth`,
    component: () => import('@/components/systemAuth/index'),
    name: 'systemAuth'
  },
  {
    path: `${roterPre}/setting/uploadPicture`,
    component: () => import('@/components/uploadPicture/index.vue'),
    name: 'uploadPicture'
  }
]

export default settingRoutes
