import { ref, type Ref } from 'vue'
import { useTable } from '@/composables/useTable'
import { knowledgeDocumentListApi, knowledgeDocumentBatchDeleteApi } from '@/api/knowledge'
import { DOC_PAGE_SIZE } from '../constants'
import { confirmDelete } from '../utils/confirm'

export interface DocumentRow {
  id: number
  kb_id: number
  title: string
  file_name: string
  file_type: string
  file_size: number
  status: number
  error_msg?: string
  /** 启停开关，0 关闭 1 开启 */
  enabled?: 0 | 1
  chunk_count?: number
}

/**
 * 知识库文档列表逻辑
 *
 * 勾选态交给 BaseTable（el-table）维护，这里只管查询、分页与删除。
 *
 * @param kbId 当前知识库 id
 */
export const useDocumentList = (kbId: Ref<number>) => {
  const table = useTable(knowledgeDocumentListApi, {
    pageSize: DOC_PAGE_SIZE,
    immediate: false,
    generateSearchForm: () => ({ title: '' }),
    extraParams: () => ({ kb_id: kbId.value })
  })

  // useTable 是 js 文件，tableData 推断为 never[]，这里收敛成业务行类型供模板使用
  const tableData = table.tableData as unknown as Ref<DocumentRow[]>
  const loadedPage = ref(1)

  /** 重新加载第一页，并重置触底分页状态 */
  const fetchData = async () => {
    loadedPage.value = 1
    await table.fetchData()
  }

  const handleSearch = (value?: { title: string } | 'reset') => {
    loadedPage.value = 1
    table.handleSearch(value)
  }

  /** 滚动触底后加载下一页，并追加到现有列表 */
  const loadMore = async () => {
    if (table.isLoading.value || tableData.value.length >= table.total.value) return

    table.isLoading.value = true
    try {
      const nextPage = loadedPage.value + 1
      const res = await knowledgeDocumentListApi({
        ...table.searchForm.value,
        kb_id: kbId.value,
        page: nextPage,
        limit: table.pageSize.value
      })
      const nextRows = (res.data?.list || []) as DocumentRow[]
      const loadedIds = new Set(tableData.value.map((item) => item.id))
      tableData.value = [...tableData.value, ...nextRows.filter((item) => !loadedIds.has(item.id))]
      table.total.value = res.data?.count || 0
      loadedPage.value = nextPage
    } catch (error) {
      // 错误提示由 request 统一处理，这里保留当前页数据
    } finally {
      table.isLoading.value = false
    }
  }

  /** 删除单个文档；返回是否真的删掉了，供调用方决定要不要切换选中的文档 */
  const handleDelete = async (row: DocumentRow) => {
    const isConfirmed = await confirmDelete(
      `是否删除文档：${row.title}`,
      `此知识库关联 ${row.chunk_count || 0} 个分段，删除后无法恢复，请谨慎操作`
    )
    if (!isConfirmed) return false
    const res = await knowledgeDocumentBatchDeleteApi({ ids: [row.id] })
    if (res.status !== 200) return false
    await fetchData()
    return true
  }

  return {
    ...table,

    tableData,
    fetchData,
    handleSearch,
    loadMore,
    handleDelete
  }
}
