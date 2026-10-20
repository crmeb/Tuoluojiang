/**
 * 组件树内的事件中心：由祖先组件 provide，后代 inject 后收发事件，
 * 替代 Vue 2 时代 element-ui 风格的 dispatch / broadcast（依赖 $parent / $children 逐级查找组件名）。
 *
 * 订阅方负责在卸载时退订：on() 返回退订函数，Options API 组件可配合 hubSubscriberMixin 自动退订。
 */
import type { InjectionKey } from 'vue'

export type EventHandler = (...args: any[]) => void

export interface EventHub {
  on(event: string, handler: EventHandler): () => void
  off(event: string, handler?: EventHandler): void
  emit(event: string, ...args: unknown[]): void
}

export function createEventHub(): EventHub {
  const handlers = new Map<string, EventHandler[]>()
  const off: EventHub['off'] = (event, handler) => {
    const list = handlers.get(event)
    if (!list) return
    if (!handler) {
      handlers.delete(event)
      return
    }
    const index = list.indexOf(handler)
    if (index > -1) list.splice(index, 1)
  }
  return {
    on(event, handler) {
      const list = handlers.get(event) ?? []
      list.push(handler)
      handlers.set(event, list)
      return () => off(event, handler)
    },
    off,
    emit(event, ...args) {
      // 复制一份再遍历：回调里退订不会跳过后续监听
      handlers.get(event)?.slice().forEach((handler) => handler(...args))
    },
  }
}

/** 表单渲染器（VFormRender）的事件中心：字段 → 渲染器、渲染器 → 字段 / 容器 */
export const FORM_RENDER_HUB: InjectionKey<EventHub> = Symbol('formRenderHub')
/** 设计器属性面板（SettingPanel）的事件中心：属性编辑器 → 面板 */
export const SETTING_PANEL_HUB: InjectionKey<EventHub> = Symbol('settingPanelHub')

/**
 * Options API 订阅辅助：`this.subscribeHub(hub, event, handler)` 订阅，组件卸载时自动退订。
 * hub 为空（组件不在对应的祖先下，如设计态字段不在 VFormRender 内）时静默忽略，与原 dispatch 找不到目标时一致。
 */
export const hubSubscriberMixin = {
  beforeUnmount(this: { _hubOffs?: (() => void)[] }) {
    this._hubOffs?.forEach((off) => off())
    this._hubOffs = []
  },
  methods: {
    subscribeHub(this: { _hubOffs?: (() => void)[] }, hub: EventHub | null | undefined, event: string, handler: EventHandler) {
      if (!hub) return
      ;(this._hubOffs ??= []).push(hub.on(event, handler))
    },
  },
}
