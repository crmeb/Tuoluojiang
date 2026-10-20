import { computed } from 'vue'
import type { Ref } from 'vue'
import { modalSure } from '@/libs/public'
import { useTable } from '@/composables/useTable'
import { getApplicationsListApi, delApplicationsApi } from '@/api/chatAi'
import { PAGE_SIZE } from '../constants'

/** 列表接口返回的应用行 */
export interface ApplicationRow {
  id: number
  name: string
  info?: string
  pic?: string
  /** 发布状态，见 APP_STATUS */
  status: number
  /** 应用类型：0=内部应用，1=外部应用，见 setting/constants 的 APP_TYPE */
  app_type: number
  /** 访客端分享标识，仅外部应用返回 */
  share_key?: string
  /** 当前登录人是否有该应用的编辑权限，无权限时只能「使用」 */
  auth?: boolean
  user?: { name?: string } | null
}

interface UseApplicationListOptions {
  /** 是否初始化就请求；每页条数按容器尺寸算时应传 false，等测量完再拉 */
  immediate?: boolean
  /** 搜索表单之外的固定查询条件（如知识库详情页按 knowledge_id 过滤），每次请求时合并 */
  extraParams?: () => Record<string, any>
}

/**
 * AI 应用列表逻辑
 *
 * 搜索表单只承载 oaFromBox 的字段（应用名称），分页与请求复用 useTable。
 */
export const useApplicationList = (options: UseApplicationListOptions = {}) => {
  const table = useTable(getApplicationsListApi, {
    pageSize: PAGE_SIZE,
    immediate: options.immediate !== false,
    extraParams: options.extraParams,
    generateSearchForm: () => ({ name: '' })
  })

  const searchFields = [
    {
      form_value: 'input',
      field_name_en: 'name',
      field_name: '应用名称'
    }
  ]

  const isEmpty = computed(() => !table.isLoading.value && table.tableData.value.length === 0)

  // useTable 是 js 文件，tableData 推断为 never[]，这里收敛成业务行类型供模板使用
  const tableData = table.tableData as unknown as Ref<ApplicationRow[]>

  const handleDelete = async (row: ApplicationRow) => {
    try {
      await modalSure('删除后该应用将不再提供服务，请谨慎操作')
    } catch {
      return
    }
    await delApplicationsApi(row.id)
    if (table.page.value > 1 && table.tableData.value.length <= 1) {
      // 当页删空时回退页码，watch 会自动重查
      table.page.value--
    } else {
      table.fetchData()
    }
  }

  return {
    ...table,

    tableData,
    isEmpty,
    searchFields,

    handleDelete
  }
}
