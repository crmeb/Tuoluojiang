interface MenuNode {
  is_show?: number
  children?: MenuNode[]
}

/**
 * 判断一级菜单下的二级菜单栏是否需要展示：
 * - 二级菜单多于一条：展示
 * - 没有二级菜单：隐藏
 * - 仅一条二级菜单且它没有三级菜单：整栏没有可切换的入口，隐藏
 * - 仅一条二级菜单但带三级菜单：按该条的 is_show 判断
 */
export function shouldShowSubSidebar(menu?: MenuNode | null): boolean {
  const children = Array.isArray(menu && menu.children) ? (menu as MenuNode).children! : []
  if (children.length > 1) {
    return true
  }

  const firstChild = children[0]
  if (!firstChild) {
    return false
  }

  const grandChildren = Array.isArray(firstChild.children) ? firstChild.children : []
  if (!grandChildren.length) {
    return false
  }

  return firstChild.is_show !== 0
}
