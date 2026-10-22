<!-- 知识库详情-分段栏：展示当前文档的分段，支持新增 / 编辑 / 删除 -->
<template>
  <div class="chunk-panel">
    <div class="panel-head">
      <span class="panel-title">分段列表</span>
      <el-button size="small" type="primary" icon="el-icon-plus" :disabled="!docId" @click="handleAdd">
        添加分段
      </el-button>
    </div>

    <div class="panel-toolbar">
      <span class="total-tip">共 {{ total }} 项</span>
      <el-input
        v-model="keyword"
        size="small"
        clearable
        placeholder="请输入分段内容"
        class="search-input"
        @keyup.enter.native="handleSearchByKeyword"
        @clear="handleSearchByKeyword"
      >
        <template #prefix><i class="el-input__icon el-icon-search" /></template>
      </el-input>
      <el-button size="small" icon="iconfont iconqingchu" class="refresh-btn" @click="fetchData" />
    </div>

    <div v-loading="isLoading" class="panel-body">
      <defaultPage v-if="isEmpty" :index="14" :min-height="400" />

      <div class="chunk-list">
        <chunkCard
          v-for="row in tableData"
          :key="row.id"
          :data="row"
          @edit="handleEdit"
          @delete="handleRemove"
        />
      </div>
    </div>

    <div v-if="total > pageSize" class="panel-pagination el-pagination-reset">
      <el-pagination
        :current-page="page"
        :page-size="pageSize"
        :total="total"
        layout="total, prev, pager, next"
        @current-change="page = $event"
      />
    </div>

    <chunkDialog ref="chunkDialogRef" :doc-id="docId" @isOk="fetchData" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import defaultPage from '@/components/common/defaultPage.vue'
import chunkCard from './chunkCard.vue'
import chunkDialog from './chunkDialog.vue'
import { useChunkList } from '../composables/useChunkList'

interface ChunkRow {
  id: number
  chunk_index: number
  content: string
  content_html?: string | null
}

const props = defineProps<{ docId: number }>()

const chunkDialogRef = ref<any>(null)
const keyword = ref('')

const docId = computed(() => props.docId)

const { page, pageSize, total, tableData, isLoading, isEmpty, fetchData, handleSearch, handleDelete } =
  useChunkList(docId)

// 换文档等于换数据源，composable 里已重置查询条件，这里同步清掉输入框
watch(docId, () => {
  keyword.value = ''
})

function handleSearchByKeyword() {
  handleSearch({ content: keyword.value })
}

function handleAdd() {
  chunkDialogRef.value.openBox()
}

function handleEdit(row: ChunkRow) {
  chunkDialogRef.value.openBox(row)
}

function handleRemove(row: ChunkRow) {
  handleDelete(row as any)
}

defineExpose({ refresh: fetchData })
</script>

<style scoped lang="scss">
.chunk-panel {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding: 20px;
}

.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.panel-title {
  font-weight: 500;
  font-size: 18px;
  line-height: 20px;
  color: #303133;
}

.panel-toolbar {
  margin-top: 20px;
  display: flex;
  align-items: center;
  gap: 10px;

  .total-tip {
    flex-shrink: 0;
    font-size: 12px;
    color: #909399;
  }

  .search-input {
    width: 250px;
  }

  .refresh-btn {
    flex-shrink: 0;
  }
}

.panel-body {
  flex: 1;
  min-height: 0;
  margin-top: 20px;
  overflow-y: auto;
}

.chunk-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.panel-pagination {
  display: flex;
  justify-content: flex-end;
  padding-top: 16px;
}
</style>
