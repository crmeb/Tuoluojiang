import iconfontIcons from '@/libs/iconfont-icons'
import { DEFAULT_ICON } from '../constants'

// 存的是 select-icon 抛出的 value：官方图标库为图标名（如 CRMEB-wendang-xianxing），
// ELEMENT 图标为完整类名（如 el-icon-star-on）。两者的渲染方式与展示名都不一样。
const ICON_NAMES: Record<string, string> = {}
iconfontIcons.forEach((item: { icon: string; name: string }) => {
  ICON_NAMES[item.icon] = item.name
})

const EL_ICON_PREFIX = 'el-icon-'

/**
 * 图标 class
 *
 * 官方图标库要配 iconfont 基类才有字体，ELEMENT 图标自带前缀且用的是另一套字体，
 * 再加 iconfont 会被 `font-family: iconfont !important` 覆盖成方框。取值规则与 select-icon 一致。
 */
export const getIconClass = (icon?: string) => {
  const value = icon || DEFAULT_ICON
  return value.startsWith(EL_ICON_PREFIX) ? value : `iconfont ${value}`
}

/** 图标的可读名称：官方图标库取中文名，ELEMENT 图标去掉 el-icon- 前缀 */
export const getIconName = (icon?: string) => {
  if (!icon) return ''
  if (icon.startsWith(EL_ICON_PREFIX)) return icon.slice(EL_ICON_PREFIX.length)
  return ICON_NAMES[icon] || icon
}
