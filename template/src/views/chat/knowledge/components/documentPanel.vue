<!-- 知识库详情-文档栏：搜索、导入、批量操作与文档选中 -->
<template>
  <div class="doc-panel">
    <div class="panel-head">
      <span class="panel-title">文档列表</span>
      <span v-if="total" class="panel-count">{{ total }}</span>
    </div>

    <div class="panel-toolbar">
      <el-input
        v-model="keyword"
        clearable
        placeholder="按文档名称搜索"
        class="search-input"
        @keyup.enter="handleSearchByKeyword"
        @clear="handleSearchByKeyword"
      >
        <template #prefix><i class="el-input__icon el-icon-search" /></template>
      </el-input>

      <el-button type="primary" class="import-btn" @click="importDialogRef.openBox()">
        导入文档
      </el-button>
    </div>

    <!-- 选中后整行切换成批量操作，未选中时只做全选入口 -->
    <div class="list-head" :class="{ 'is-batch': selectedIds.length }">
      <!-- 勾选态完全由 selectedIds 驱动，点击事件走外层容器：
           el-checkbox 自己的 change 会被浏览器的表单状态恢复触发，刷新后凭空全选 -->
      <span class="head-check" @click="handleSelectAll(!isAllSelected)">
        <el-checkbox :value="isAllSelected" :indeterminate="isIndeterminate" :disabled="!tableData.length" />
      </span>
      <template v-if="selectedIds.length">
        <span class="batch-tip">已选 {{ selectedIds.length }} 项</span>
        <span
          v-for="item in BATCH_ACTIONS"
          :key="item.value"
          class="batch-action"
          :class="{ 'is-danger': item.value === 'delete' }"
          @click="handleBatchCommand(item.value)"
        >
          {{ item.label }}
        </span>
      </template>
      <span v-else class="head-tip">文档信息</span>
    </div>

    <div ref="listRef" v-loading="isLoading" class="doc-list" @scroll="handleListScroll">
      <div
        v-for="row in tableData"
        :key="row.id"
        class="doc-item"
        :class="{
          'is-active': row.id === activeId,
          'is-checked': selectedIds.includes(row.id),
          'is-disabled': row.enabled === 0
        }"
        @click="emit('select', row)"
      >
        <div class="item-pick" @click.stop="toggleSelect(row.id)">
          <img :src="getFileIcon(row.file_type)" alt="" class="file-icon" />
          <el-checkbox class="item-check" :value="selectedIds.includes(row.id)" />
        </div>

        <div class="item-main">
          <div class="item-title over-text" :title="row.title">{{ row.title }}</div>
          <div class="item-meta">
            <span>{{ formatBytes(row.file_size || 0) }}</span>
            <span class="meta-dot">·</span>
            <span>{{ row.chunk_count || 0 }} 分段</span>
            <template v-if="row.enabled === 0">
              <span class="meta-dot">·</span>
              <span>已关闭</span>
            </template>
            <el-tooltip
              v-if="DOC_STATUS_TEXT[row.status]"
              :content="row.error_msg || DOC_STATUS_TEXT[row.status]"
              placement="top"
            >
              <span class="meta-status" :class="statusClass(row.status)">
                {{ DOC_STATUS_TEXT[row.status] }}
              </span>
            </el-tooltip>
          </div>
        </div>

        <el-dropdown trigger="click" placement="bottom-end" @command="handleCommand($event, row)">
          <div class="more-btn" @click.stop>
            <i class="iconfont icongengduo" />
          </div>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item v-for="item in getDocumentActions(row)" :key="item.value" :command="item.value">
                {{ item.label }}
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>

      <div v-if="!isLoading && !tableData.length" class="doc-empty">
        <svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
          <path
            fill="currentColor"
            d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-7-7zm0 2.5L17.5 9H13V4.5zM8 13h8v1.6H8V13zm0 4h8v1.6H8V17z"
          />
        </svg>
        <p>{{ keyword ? '没有找到匹配的文档' : '还没有文档，先导入一份吧' }}</p>
      </div>
    </div>

    <importDialog ref="importDialogRef" :kb-id="kbId" @isOk="handleImported" />
    <embedDialog ref="embedDialogRef" @confirm="handleEmbedConfirm" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { ElMessage as Message } from 'element-plus'
import { formatBytes } from '@/utils/file.ts'
import {
  knowledgeDocumentReprocessApi,
  knowledgeDocumentBatchEnabledApi,
  knowledgeDocumentBatchDeleteApi
} from '@/api/knowledge.ts'
import importDialog from './importDialog.vue'
import embedDialog from './embedDialog.vue'
import { useDocumentList } from '../composables/useDocumentList.ts'
import { BATCH_ACTIONS, DOC_STATUS, DOC_STATUS_TEXT } from '../constants.ts'
import { getFileIcon } from '../utils/fileIcon.ts'
import { confirmDelete } from '../utils/confirm.ts'

interface DocumentRow {
  id: number
  title: string
  file_type: string
  file_size: number
  status: number
  error_msg?: string
  enabled?: 0 | 1
  chunk_count?: number
}

const props = defineProps<{ kbId: number; activeId: number }>()

const emit = defineEmits<{
  (e: 'select', row: DocumentRow | null): void
}>()

const importDialogRef = ref<any>(null)
const embedDialogRef = ref<any>(null)
const listRef = ref<HTMLElement | null>(null)
const keyword = ref('')
const selectedIds = ref<number[]>([])

// 待向量化的文档 id 集合，弹窗确认后才真正发请求
const pendingEmbedIds = ref<number[]>([])

const kbId = computed(() => props.kbId)

const { tableData, total, isLoading, fetchData, handleSearch, loadMore, handleDelete } = useDocumentList(kbId)

const isAllSelected = computed(
  () => !!tableData.value.length && selectedIds.value.length === tableData.value.length
)
const isIndeterminate = computed(
  () => !!selectedIds.value.length && selectedIds.value.length < tableData.value.length
)

const statusClass = (status: number) =>
  status === DOC_STATUS.FAILED ? 'is-failed' : status === DOC_STATUS.PROCESSING ? 'is-processing' : ''

function toggleSelect(id: number) {
  const index = selectedIds.value.indexOf(id)
  if (index === -1) selectedIds.value.push(id)
  else selectedIds.value.splice(index, 1)
}

function handleSelectAll(checked: boolean) {
  if (!tableData.value.length) return
  selectedIds.value = checked ? tableData.value.map((item) => item.id) : []
}

// 列表回来后默认选中第一条，右侧分段栏才有数据可展示；
// 当前选中的文档被删掉后同样回落到第一条
watch(tableData, (rows) => {
  // 翻页追加的行不该被算进已勾选，只保留仍在列表里的 id
  const ids = new Set(rows.map((item) => item.id))
  selectedIds.value = selectedIds.value.filter((id) => ids.has(id))
  if (rows.some((item) => item.id === props.activeId)) return
  emit('select', rows[0] || null)
})

function handleListScroll() {
  const el = listRef.value
  if (!el) return
  if (el.scrollHeight - el.scrollTop - el.clientHeight <= 24) loadMore()
}

fetchData()

function handleSearchByKeyword() {
  handleSearch({ title: keyword.value })
}

function handleImported() {
  keyword.value = ''
  handleSearch('reset')
}

/** 打开向量化弹窗；范围选项接口暂不支持，仅保留交互 */
function openEmbedDialog(ids: number[]) {
  pendingEmbedIds.value = ids
  embedDialogRef.value.openBox()
}

async function handleEmbedConfirm() {
  const ids = pendingEmbedIds.value
  if (!ids.length) return
  const results = await Promise.all(ids.map((id) => knowledgeDocumentReprocessApi(id)))
  if (results.some((res) => res.status !== 200)) return
  Message.success('已提交向量化')
  fetchData()
}

function getDocumentActions(row: DocumentRow) {
  const isDisabled = row.enabled === 0
  return [
    { label: '向量化', value: 'vectorize' },
    { label: isDisabled ? '开启' : '关闭', value: isDisabled ? 'enable' : 'disable' },
    { label: '删除', value: 'delete' }
  ]
}

async function updateDocumentEnabled(ids: number[], enabled: 0 | 1) {
  const res = await knowledgeDocumentBatchEnabledApi({ ids, enabled })
  if (res.status !== 200) return
  await fetchData()
}

/** 批量操作做完就退出勾选态，避免操作条一直挂在列表顶上 */
function clearSelection() {
  selectedIds.value = []
}

async function handleBatchCommand(command: string) {
  const ids = [...selectedIds.value]
  if (!ids.length) return

  if (command === 'enable' || command === 'disable') {
    await updateDocumentEnabled(ids, command === 'enable' ? 1 : 0)
    clearSelection()
    return
  }
  if (command !== 'delete') return

  const isConfirmed = await confirmDelete(`是否删除选中的 ${ids.length} 个文档`, '删除后无法恢复，请谨慎操作')
  if (!isConfirmed) return
  const res = await knowledgeDocumentBatchDeleteApi({ ids })
  if (res.status !== 200) return
  clearSelection()
  await fetchData()
}

async function handleCommand(command: string, row: DocumentRow) {
  if (command === 'vectorize') {
    openEmbedDialog([row.id])
    return
  }
  if (command === 'enable' || command === 'disable') {
    await updateDocumentEnabled([row.id], command === 'enable' ? 1 : 0)
    return
  }
  if (command !== 'delete') return
  await handleDelete(row as any)
}

defineExpose({ refresh: fetchData })
</script>

<style scoped lang="scss">
$primary: #1890ff;

.doc-panel {
  width: 400px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding: 20px;
  // 分割线画在盒子边上，不受内边距影响，才能一路顶到容器上下沿
  border-right: 1px solid #dcdfe6;
}

.panel-head {
  flex-shrink: 0;
  display: flex;
  align-items: center;

  .panel-title {
    font-weight: 500;
    font-size: 18px;
    line-height: 20px;
    color: #303133;
  }

  .panel-count {
    margin-left: 8px;
    padding: 0 8px;
    border-radius: 9px;
    background: #f0f2f5;
    font-size: 12px;
    line-height: 18px;
    color: #909399;
  }
}

.panel-toolbar {
  flex-shrink: 0;
  margin: 16px 0 4px;
  display: flex;
  align-items: center;

  .search-input {
    flex: 1;
    min-width: 0;
  }

  .import-btn {
    margin-left: 10px;
  }
}

.list-head {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  height: 36px;
  margin-top: 8px;
  padding: 0 10px;
  border-bottom: 1px solid #f0f2f5;
  font-size: 12px;
  color: #909399;

  .head-check {
    display: flex;
    align-items: center;
    margin-right: 10px;
    cursor: pointer;

    .el-checkbox {
      pointer-events: none;
    }
  }

  &.is-batch {
    color: #303133;
  }

  .batch-tip {
    margin-right: auto;
  }

  .batch-action {
    margin-left: 14px;
    color: $primary;
    cursor: pointer;

    &:hover {
      opacity: 0.75;
    }

    &.is-danger {
      color: #f56c6c;
    }
  }
}

.doc-list {
  flex: 1;
  min-height: 0;
  margin: 0 -6px;
  padding: 6px;
  overflow-y: auto;

  // 左栏窄，滚动条会挤掉一列宽度，隐藏后靠滚轮 / 触控板滚动
  scrollbar-width: none;
  -ms-overflow-style: none;

  &::-webkit-scrollbar {
    display: none;
  }
}

.doc-item {
  position: relative;
  display: flex;
  align-items: center;
  padding: 9px 8px 9px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;

  & + .doc-item {
    margin-top: 2px;
  }

  &:hover {
    background-color: #f7f8fa;
  }

  // 当前查看的文档高亮，与右侧分段栏的数据源对应
  &.is-active {
    background-color: rgba(24, 144, 255, 0.08);

    .item-title {
      color: $primary;
    }

    // 选中态左侧指示条
    &::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      width: 3px;
      height: 16px;
      margin-top: -8px;
      border-radius: 0 2px 2px 0;
      background: $primary;
    }
  }

  &.is-checked {
    background-color: rgba(24, 144, 255, 0.06);
  }

  // 已关闭的文档不参与检索，名称与文件图标一起置灰；
  // 操作入口保持原样，否则没法再把它开回来
  &.is-disabled {
    .item-title,
    .item-meta {
      color: #c0c4cc;
    }

    .file-icon {
      opacity: 0.45;
    }
  }

  // 勾选框默认藏在文件图标下，hover 或已勾选时才顶上来
  &:hover .item-check,
  &.is-checked .item-check {
    display: block;
  }

  &:hover .file-icon,
  &.is-checked .file-icon {
    opacity: 0;
  }

  &:hover .more-btn {
    opacity: 1;
  }
}

.item-pick {
  position: relative;
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  margin-right: 10px;

  .file-icon {
    width: 24px;
    height: 24px;
  }

  .item-check {
    display: none;
    position: absolute;
    top: 50%;
    left: 50%;
    margin: -7px 0 0 -7px;
    pointer-events: none;

    :deep(.el-checkbox__label) {
      display: none;
    }
  }
}

.item-main {
  flex: 1;
  min-width: 0;
}

.item-title {
  font-size: 13px;
  line-height: 20px;
  color: #303133;
}

.item-meta {
  display: flex;
  align-items: center;
  margin-top: 2px;
  font-size: 12px;
  line-height: 16px;
  color: #909399;

  .meta-dot {
    margin: 0 4px;
  }

  .meta-status {
    margin-left: 8px;
    padding: 0 6px;
    border-radius: 4px;
    background: #f0f2f5;
    cursor: help;

    &.is-failed {
      background: #fef0f0;
      color: #f56c6c;
    }

    &.is-processing {
      background: #ecf5ff;
      color: $primary;
    }
  }
}

.more-btn {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  opacity: 0;
  color: #606266;
  font-size: 14px;
  cursor: pointer;
  transition: opacity 0.2s, background-color 0.2s;

  &:hover {
    background: rgba(0, 0, 0, 0.06);
  }
}

.doc-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #c9cdd4;

  p {
    margin: 10px 0 0;
    font-size: 13px;
  }
}
</style>
