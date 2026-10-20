/**
 * v-resize="handler"：元素尺寸变化时回调 handler({ width, height })。
 * 替代 Vue 2 专属的 @theshy/v-resize。
 */
import type { App, Directive } from 'vue'

const observers = new WeakMap<HTMLElement, ResizeObserver>()

const resize: Directive<HTMLElement, (size: { width: number; height: number }) => void> = {
  mounted(el, binding) {
    const observer = new ResizeObserver(([entry]) => {
      const { width, height } = entry.contentRect
      binding.value?.({ width, height })
    })
    observer.observe(el)
    observers.set(el, observer)
  },
  unmounted(el) {
    observers.get(el)?.disconnect()
    observers.delete(el)
  },
}

export default {
  install(app: App) {
    app.directive('resize', resize)
  },
}
