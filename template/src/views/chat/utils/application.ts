import { roterPre } from '@/settings'
import { APP_TYPE } from '../setting/constants'

/** 两类应用共用设置组件，路由分别归属于各自的列表菜单。 */
export const getApplicationSettingPath = (appType: number) =>
  `${roterPre}/chat/${appType === APP_TYPE.EXTERNAL ? 'customerService/setting' : 'setting'}`
