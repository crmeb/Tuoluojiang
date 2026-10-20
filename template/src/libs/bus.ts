/**
 * 轻量事件总线
 *
 * 此前这里导出的是 `new Vue()`，仅仅为了借用它的 $on / $off / $emit，
 * 让一个纯粹的发布订阅工具绑死在 Vue 2 上。现改为自行实现，
 * 方法名保留 `$` 前缀，与既有调用点保持一致。
 */
type EventHandler = (...args: any[]) => void

class EventEmitter {
  private handlers = new Map<string, Set<EventHandler>>()

  $on(event: string, handler: EventHandler) {
    let set = this.handlers.get(event)
    if (!set) {
      set = new Set()
      this.handlers.set(event, set)
    }
    set.add(handler)
    return this
  }

  $once(event: string, handler: EventHandler) {
    const wrapped: EventHandler = (...args) => {
      this.$off(event, wrapped)
      handler(...args)
    }
    return this.$on(event, wrapped)
  }

  /** 不传 handler 则移除该事件的全部监听；不传 event 则清空所有监听 */
  $off(event?: string, handler?: EventHandler) {
    if (event === undefined) {
      this.handlers.clear()
      return this
    }
    if (handler === undefined) {
      this.handlers.delete(event)
      return this
    }
    const set = this.handlers.get(event)
    if (set) {
      set.delete(handler)
      if (set.size === 0) {
        this.handlers.delete(event)
      }
    }
    return this
  }

  $emit(event: string, ...args: any[]) {
    const set = this.handlers.get(event)
    if (!set) {
      return this
    }
    // 先快照一份：handler 内部可能 $off 自身或注册新监听，直接遍历原集合会漏触发
    for (const handler of [...set]) {
      // 与 Vue 的 $emit 行为一致，单个 handler 抛错不阻断其余 handler
      try {
        handler(...args)
      } catch (e) {
        console.error(`[EventBus] "${event}" 监听器执行出错`, e)
      }
    }
    return this
  }
}

export const EventBus = new EventEmitter()
