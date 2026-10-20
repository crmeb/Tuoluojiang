import { ref, getCurrentInstance, watch } from 'vue'

/** 字段组件（form-designer 的 field-widget）的 widget 配置，只关心 id */
export interface ActiveField {
  id?: string | number
  [key: string]: unknown
}

/**
 * 只读表单「点击字段就地编辑」时当前处于编辑态的字段。
 * 整个页面同一时刻只允许一个字段处于编辑态：点击另一表单的字段也要收起当前字段并触发保存，
 * 所以是模块级单例，而不是按表单实例 provide。
 * 写入方：fieldMixin（点击只读字段 / 点击空白处）；读取方：VFormRender 及其容器组件。
 */
const activeField = ref<ActiveField>({})

export function setActiveField(field: ActiveField) {
  activeField.value = field
}

/**
 * 在组件内监听当前编辑字段变化（深度监听，与字段对象内部变化同步），组件卸载时自动停止。
 * 必须在 setup / created 等有当前实例的阶段调用。
 */
export function watchActiveField(handler: (field: ActiveField) => void) {
  if (!getCurrentInstance()) {
    throw new Error('[watchActiveField] 需在组件 setup / created 阶段调用')
  }
  return watch(activeField, (field) => handler(field), { deep: true })
}

/**
 * 点击编辑态字段以外的区域时收起该字段（触发字段的保存逻辑）。
 * Element Plus 的抽屉 / 弹窗面板会阻止 click 冒泡，所以在捕获阶段监听 document；
 * 只有当前编辑态字段响应，点击字段自身（含输入框）不收起。返回解绑函数。
 */
export function listenActiveFieldOutsideClick(
  getField: () => ActiveField | undefined,
  getEl: () => Element | null | undefined
) {
  const handler = (event: MouseEvent) => {
    const field = getField()
    if (!field?.id || activeField.value.id !== field.id) return
    const target = event.target as Node | null
    if (target && getEl()?.contains(target)) return
    setActiveField({ id: 0 })
  }
  document.addEventListener('click', handler, true)
  return () => document.removeEventListener('click', handler, true)
}
