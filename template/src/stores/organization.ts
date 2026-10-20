import { defineStore } from 'pinia'
import { ref } from 'vue'
import { frameTreeApi, frameUserApi } from '@/api/public'
import type { ApiResult } from '@/types/api'

/** 部门 / 人员树节点，字段随接口透传 */
export interface FrameNode {
  id: number
  label?: string
  name?: string
  children?: FrameNode[]
  [key: string]: unknown
}

/**
 * 企业组织架构缓存：部门树、人员树。
 * 选人 / 选部门组件实例众多，共用一份数据避免重复请求；组织架构变更后传 force 刷新。
 */
export const useOrganizationStore = defineStore('organization', () => {
  const departmentList = ref<FrameNode[]>([])
  const memberList = ref<FrameNode[]>([])

  async function getMember({ force = false } = {}) {
    if (!force && memberList.value.length) {
      return memberList.value
    }
    const response: ApiResult<FrameNode[]> = await frameUserApi({ role: 0, leave: 0 })
    const data = response.data
    memberList.value = data?.[0]?.children ? data[0].children : data || []
    return memberList.value
  }

  async function getDepartment({ force = false } = {}) {
    if (!force && departmentList.value.length) {
      return departmentList.value
    }
    const response: ApiResult<FrameNode[]> = await frameTreeApi()
    departmentList.value = response.data || []
    return departmentList.value
  }

  function reset() {
    departmentList.value = []
    memberList.value = []
  }

  return { departmentList, memberList, getMember, getDepartment, reset }
})
