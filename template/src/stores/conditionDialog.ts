import { defineStore } from 'pinia'
import { ref } from 'vue'

/** 条件设置弹窗的输入 / 输出：list 为条件列表，type 为触发器类型，additional_search_boolean 为「且 / 或」 */
export interface ConditionFieldOptions {
  list?: unknown[]
  type?: string
  additional_search_boolean?: string
  resetList?: unknown[]
  [key: string]: unknown
}

/**
 * 低代码条件设置弹窗（components/develop/conditionDialog）：
 * 宿主页面写入 fieldOptions 并打开弹窗，弹窗确认后把结果写回 fieldOptions 再关闭。
 * 路由切换时由 layout 统一重置，避免上一个页面的条件串到下一个页面。
 */
export const useConditionDialogStore = defineStore('conditionDialog', () => {
  const visible = ref(false)
  const fieldOptions = ref<ConditionFieldOptions>({})

  function setFieldOptions(options: ConditionFieldOptions) {
    fieldOptions.value = options
  }

  function reset() {
    visible.value = false
    fieldOptions.value = { list: [], type: '', additional_search_boolean: '1' }
  }

  return { visible, fieldOptions, setFieldOptions, reset }
})
