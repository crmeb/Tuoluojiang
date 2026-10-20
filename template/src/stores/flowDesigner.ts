import { defineStore } from 'pinia'
import { ref } from 'vue'

/** 节点抽屉的编辑上下文：value 为节点配置副本，flag 置 true 表示抽屉已保存待回写，id 标识来源节点 */
export interface NodeDrawerConfig<T = unknown> {
  value?: T
  flag?: boolean
  id?: string | number
  [key: string]: unknown
}

/**
 * 审批流程设计器：递归的节点组件（nodeWrap）与挂在设计器根部的发起人 / 审批人 / 抄送人 / 条件抽屉
 * 分处不同子树，通过这里交换「打开哪个抽屉、编辑哪个节点、保存结果」。
 * 表单设计侧（formSettingProps / conditionsFields）供条件抽屉与表单设计器读取可用字段。
 */
export const useFlowDesignerStore = defineStore('flowDesigner', () => {
  const promoterDrawer = ref(false)
  const flowPermission = ref<NodeDrawerConfig>({})
  const approverDrawer = ref(false)
  const approverConfig = ref<NodeDrawerConfig>({})
  const copyerDrawer = ref(false)
  const copyerConfig = ref<NodeDrawerConfig>({})
  // 审批流程的条件设置抽屉
  const conditionDrawer = ref(false)
  const conditionsConfig = ref<NodeDrawerConfig>({ conditionNodes: [] })
  // 审批表单设计的字段，条件抽屉据此列出可选条件
  const formSettingProps = ref<unknown[]>([])
  // 流程里已作为条件的字段，表单设计器据此限制删除
  const conditionsFields = ref<unknown[]>([])

  return {
    promoterDrawer,
    flowPermission,
    approverDrawer,
    approverConfig,
    copyerDrawer,
    copyerConfig,
    conditionDrawer,
    conditionsConfig,
    formSettingProps,
    conditionsFields
  }
})
