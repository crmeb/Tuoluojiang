<template>
  <el-dialog
    class="knowledge-select-dialog"
    title="选择知识库"
    v-model="visible"
    :width="DIALOG_SIZE.XL"
    :close-on-click-modal="false"
    @close="reset"
  >
    <div class="toolbar">
      <span class="total-text">共 {{ total }} 项</span>
      <el-input
        v-model="keyword"
        class="search-input"
        placeholder="请输入知识库名称"
        prefix-icon="el-icon-search"
        clearable
        @keyup.enter="handleKeyword"
        @clear="handleKeyword"
      />
      <div class="reset-btn" title="重置" @click="handleReset">
        <i class="iconfont iconqingchu" />
      </div>
    </div>

    <div v-loading="isLoading" class="knowledge-grid-wrap">
      <defaultPage v-if="isEmpty" :index="14" :min-height="360" />
      <div v-else class="knowledge-grid">
        <div
          v-for="item in tableData"
          :key="item.id"
          class="knowledge-item"
          :class="{ 'is-checked': isChecked(item.id) }"
          @click="toggle(item)"
        >
          <el-checkbox :value="isChecked(item.id)" class="item-check" @click.stop @change="toggle(item)" />
          <div class="item-icon" :style="{ backgroundColor: item.color || DEFAULT_ICON_COLOR }">
            <i :class="getIconClass(item.icon)" />
          </div>
          <div class="item-info">
            <div class="over-text name" :title="item.name">{{ item.name }}</div>
            <div class="over-text desc" :title="item.description">{{ item.description || '暂无描述' }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="pagination el-pagination-reset">
      <el-pagination
        :current-page="page"
        :page-size="pageSize"
        :total="total"
        layout="total, prev, pager, next, jumper"
        @current-change="handlePageChange"
      />
    </div>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" @click="submit">确定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import defaultPage from '@/components/common/defaultPage.vue'
import { useKnowledgeList } from '../../knowledge/composables/useKnowledgeList.ts'
import { DEFAULT_ICON_COLOR } from '../../knowledge/constants.ts'
import { getIconClass } from '../../knowledge/utils/icon.ts'
import { KNOWLEDGE_PAGE_SIZE } from '../constants.ts'
import type { KnowledgeItem } from '../types.ts'

const emit = defineEmits<{ (e: 'submit', list: KnowledgeItem[]): void }>()

// 列表逻辑直接复用知识库列表页的 composable，弹窗只改每页条数与首屏不预取
const { page, pageSize, total, tableData, isLoading, isEmpty, handleSearch, fetchData } = useKnowledgeList({
  pageSize: KNOWLEDGE_PAGE_SIZE,
  immediate: false
})

const visible = ref(false)
const keyword = ref('')
/** 跨页保留选中项，所以存完整对象而不是勾选态 */
const selected = ref<KnowledgeItem[]>([])

const isChecked = (id: number) => selected.value.some((item) => item.id === id)

const toggle = (row: KnowledgeItem) => {
  if (isChecked(row.id)) {
    selected.value = selected.value.filter((item) => item.id !== row.id)
    return
  }
  selected.value.push({
    id: row.id,
    name: row.name,
    description: row.description,
    icon: row.icon,
    color: row.color
  })
}

const handlePageChange = (value: number) => {
  page.value = value
}

const handleKeyword = () => handleSearch({ name: keyword.value })

const handleReset = () => {
  keyword.value = ''
  handleSearch('reset')
}

const reset = () => {
  keyword.value = ''
  selected.value = []
}

const openBox = (list: KnowledgeItem[] = []) => {
  selected.value = JSON.parse(JSON.stringify(list))
  keyword.value = ''
  visible.value = true
  handleSearch({ name: '' })
}

const submit = () => {
  emit('submit', selected.value)
  visible.value = false
}

defineExpose({ openBox, fetchData })
</script>

<style scoped lang="scss">
.toolbar {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
}

.total-text {
  flex-shrink: 0;
  margin-right: 10px;
  font-size: 12px;
  color: #909399;
}

.search-input {
  width: 250px;
}

.reset-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  margin-left: 10px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  color: #606266;
  font-size: 14px;
  cursor: pointer;
}

.knowledge-grid-wrap {
  min-height: 360px;
}

.knowledge-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.knowledge-item {
  display: flex;
  align-items: center;
  padding: 12px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.2s;

  &:hover,
  &.is-checked {
    border-color: #1890ff;
  }
}

.item-check {
  flex-shrink: 0;
  margin-right: 12px;
}

.item-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 5px;
  color: #fff;
  font-size: 20px;
}

.item-info {
  flex: 1;
  min-width: 0;
  margin-left: 12px;

  .name {
    font-weight: 500;
    font-size: 13px;
    line-height: 22px;
    color: #303133;
  }

  .desc {
    font-size: 12px;
    line-height: 22px;
    color: #909399;
  }
}

.pagination {
  display: flex;
  justify-content: flex-end;
  padding-top: 16px;
}
</style>
