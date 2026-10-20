import { computed, watch, onMounted } from 'vue'
import type { Ref } from 'vue'
import { useTable } from '@/composables/useTable'
import {
  knowledgeDocumentChunksApi,
  knowledgeChunkDeleteApi,
  knowledgeChunkEnabledApi,
  knowledgeChunkEmbedApi
} from '@/api/knowledge'
import { CHUNK_PAGE_SIZE } from '../constants'
import { confirmDelete } from '../utils/confirm'

export interface ChunkRow {
  id: number
  doc_id: number
  chunk_index: number
  content: string
  content_html?: string | null
  enabled: number
  status: number
  source_type?: number
  error_msg?: string
  embedding_error?: string
}

/**
 * 文档分段列表逻辑
 *
 * 分段接口挂在文档下（/document/{id}/chunks），未选中文档时不该发请求，
 * 这里用一个空结果的假响应短路掉，免得 useTable 里再加分支。
 *
 * 注意：分段内容关键词过滤后端暂未实现，content 参数照常带上，
 * 后端补上过滤后前端无需改动。
 *
 * @param docId 当前选中的文档 id，0 表示未选中
 */
export const useChunkList = (docId: Ref<number>) => {
  const fetchApi = (params: Record<string, any>) =>
    docId.value
      ? knowledgeDocumentChunksApi(docId.value, params)
      : Promise.resolve({ data: { list: [], count: 0 } })

  const table = useTable(fetchApi, {
    pageSize: CHUNK_PAGE_SIZE,
    immediate: false,
    generateSearchForm: () => ({ content: '' })
  })

  // useTable 是 js 文件，tableData 推断为 never[]，这里收敛成业务行类型供模板使用
  const tableData = table.tableData as unknown as Ref<ChunkRow[]>

  const isEmpty = computed(() => !table.isLoading.value && tableData.value.length === 0)

  // 切换文档等于换一份数据源：清掉关键词并回到第 1 页
  watch(docId, () => {
    table.handleSearch('reset')
  })

  // 页签用 v-if 切换，切回文档页时组件是全新实例，而 docId 由父级保留着上次选中的文档，
  // watch(docId) 拿不到变化，这里补一次首屏请求；首次进入 docId 为 0，交给 watch 触发
  onMounted(() => {
    if (docId.value) table.fetchData()
  })

  // 开启 / 关闭与向量化：新设计稿去掉了分段上的入口，方法先保留，需要时直接接回 UI
  const handleToggleEnabled = async (row: ChunkRow, enabled: boolean) => {
    const res = await knowledgeChunkEnabledApi(row.id, { enabled: enabled ? 1 : 0 })
    if (res.status !== 200) return
    row.enabled = enabled ? 1 : 0
  }

  const handleEmbed = async (row: ChunkRow) => {
    const res = await knowledgeChunkEmbedApi(row.id)
    if (res.status !== 200) return
    table.fetchData()
  }

  const handleDelete = async (row: ChunkRow) => {
    const isConfirmed = await confirmDelete(
      `是否删除分段：分段 ${row.chunk_index}`,
      '删除后无法恢复，请谨慎操作'
    )
    if (!isConfirmed) return
    const res = await knowledgeChunkDeleteApi(row.id)
    if (res.status !== 200) return
    // 当页删空时回退页码，watch 会自动重查
    if (table.page.value > 1 && tableData.value.length <= 1) {
      table.page.value--
    } else {
      table.fetchData()
    }
  }

  return {
    ...table,

    tableData,
    isEmpty,

    handleToggleEnabled,
    handleEmbed,
    handleDelete
  }
}
