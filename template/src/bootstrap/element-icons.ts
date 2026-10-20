/**
 * element-ui 图标兼容层。
 *
 * Element Plus 去掉了图标字体，图标改为 SVG 组件，组件的 icon / prefix-icon 等属性也只接受组件。
 * 这里保留 element-ui 的图标字体（assets/element-icons），并把每个 `el-icon-xxx` 注册成同名全局组件，
 * 于是 `<i class="el-icon-xxx">` 与 `<el-button icon="el-icon-xxx">` 两种存量写法都能继续使用。
 */
import { h, type App, type Component } from 'vue'
import iconCss from '@/assets/element-icons/icon.css?raw'

/** 全部 element-ui 图标类名，如 ['el-icon-plus', ...] */
export const ELEMENT_ICON_NAMES: string[] = Array.from(
  iconCss.matchAll(/\.(el-icon-[a-zA-Z0-9-]+):before\s*\{/g),
  ([, name]) => name,
)

const classIconCache = new Map<string, Component>()

/**
 * 把任意图标类名（如 `iconfont iconqingchu`）转成组件，供 Element Plus 的 icon 类属性使用。
 * `el-icon-xxx` 已注册为全局组件，原样返回；其余类名直接传字符串会被当成标签名，导致 createElement 抛错。
 */
export function toIcon(name?: string): string | Component | undefined {
  if (!name || ELEMENT_ICON_NAMES.includes(name)) return name || undefined
  let icon = classIconCache.get(name)
  if (!icon) {
    icon = { name: 'ClassIcon', render: () => h('i', { class: name }) }
    classIconCache.set(name, icon)
  }
  return icon
}

export function registerElementIcons(app: App) {
  app.config.globalProperties.$icon = toIcon
  ELEMENT_ICON_NAMES.forEach((name) => {
    app.component(name, {
      name,
      render: () => h('i', { class: name }),
    })
  })
}
