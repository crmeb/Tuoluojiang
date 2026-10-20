import type { Component } from 'vue'
import approvalGroup from '@/components/hr/approvalGroup.vue'

/** 控件组（请假、加班、合同等）的规则 type，与 config/rule 下同名规则一致 */
export const APPROVAL_GROUP_TYPES = [
  'leaveFrom',
  'overtimeFrom',
  'outFrom',
  'refillFrom',
  'tripFrom',
  'contractPayment',
  'contractRenewal',
  'contractExpenditure',
  'issueInvoice',
  'voidedInvoice',
  'contractSigning'
] as const

/** form-create 实例（formCreate.factory() 的返回值）中本模块用到的部分 */
interface ComponentRegistry {
  component(name: string, component: Component): unknown
}

/**
 * 把控件组 type 注册为容器组件。
 * 未注册时 form-create 会渲染成同名未知标签，在 Element Plus 的 flex 表单项里按内容收缩，组内字段随之变窄。
 */
export function registerApprovalGroups(...registries: ComponentRegistry[]) {
  APPROVAL_GROUP_TYPES.forEach((name) => {
    registries.forEach((registry) => registry.component(name, approvalGroup))
  })
}
