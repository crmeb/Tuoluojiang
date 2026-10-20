import { computed } from 'vue'
import type { Ref } from 'vue'
import { useTable } from '@/composables/useTable'
import { knowledgeBaseListApi, knowledgeBaseDeleteApi } from '@/api/knowledge'
import { PAGE_SIZE } from '../constants'
import { confirmDelete } from '../utils/confirm'

interface KnowledgeRow {
  id: number
  name: string
  description?: string
  icon?: string
  color?: string
  doc_count?: number
  chunk_size?: number
  app_count?: number
  user?: { name?: string }
}

interface UseKnowledgeListOptions {
  /** 每页条数，默认取列表页的栅格页长；选择弹窗按自己的排版传入 */
  pageSize?: number
  /** 是否初始化就请求，弹窗内应传 false，打开时再拉 */
  immediate?: boolean
}

/**
 * 知识库列表逻辑
 *
 * 搜索表单只承载 oaFromBox 的字段（知识库名称），分页与请求复用 useTable。
 * 列表页与「选择知识库」弹窗共用，差异只在每页条数。
 */
export const useKnowledgeList = (options: UseKnowledgeListOptions = {}) => {
  const table = useTable(knowledgeBaseListApi, {
    pageSize: options.pageSize || PAGE_SIZE,
    immediate: options.immediate !== false,
    generateSearchForm: () => ({ name: '' })
  })

  const searchFields = [
    {
      form_value: 'input',
      field_name_en: 'name',
      field_name: '知识库名称'
    }
  ]

  const isEmpty = computed(() => !table.isLoading.value && table.tableData.value.length === 0)

  // useTable 是 js 文件，tableData 推断为 never[]，这里收敛成业务行类型供模板使用
  const tableData = table.tableData as unknown as Ref<KnowledgeRow[]>

  const handleDelete = async (row: KnowledgeRow) => {
    const isConfirmed = await confirmDelete(
      `是否删除知识库：${row.name}`,
      `此知识库关联 ${row.app_count} 个应用，删除后无法恢复，请谨慎操作`
    )
    if (!isConfirmed) return
    await knowledgeBaseDeleteApi(row.id)
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
