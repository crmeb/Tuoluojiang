/**
 * vuedraggable 4 的 `item-key` 取值函数：对象项按引用分配稳定的自增 id，原始值项直接用值本身。
 * 用于原来以下标作 key、列表项本身没有唯一字段的拖拽列表。
 */
const ids = new WeakMap<object, number>()
let seq = 0

export function draggableKey(item: unknown): string | number {
  if (item !== null && (typeof item === 'object' || typeof item === 'function')) {
    let id = ids.get(item as object)
    if (id === undefined) {
      id = ++seq
      ids.set(item as object, id)
    }
    return `__drag_${id}`
  }
  return String(item)
}
