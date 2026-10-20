import type { InjectionKey, Ref } from 'vue'

export const SMART_WIDGET_GRID: InjectionKey<{ rowHeight: number; margin: number[] }> = Symbol('smart-widget-grid')
export const SMART_WIDGET_ITEM: InjectionKey<Ref<{ i: string | number; h: number }>> = Symbol('smart-widget-item')
