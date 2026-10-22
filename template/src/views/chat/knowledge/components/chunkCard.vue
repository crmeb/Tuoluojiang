<!-- 分段卡片：单行通栏，内容摘要 + 底部来源类型 / 字符数 / 向量化状态 + 编辑/删除 -->
<template>
  <div class="chunk-card">
    <div class="card-content" :title="data.content">{{ data.content }}</div>

    <div class="card-foot">
      <span class="card-tag">{{ sourceText }}</span>
      <span class="card-count">{{ formatCount(data.content ? data.content.length : 0) }} 字符</span>
      <span v-if="isFailed" class="card-status" :title="errorMsg">向量化失败</span>

      <div class="card-actions">
        <span class="card-action" @click="emit('edit', data)">编辑</span>
        <i class="card-divider" />
        <span class="card-action" @click="emit('delete', data)">删除</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { CHUNK_SOURCE_TEXT, CHUNK_STATUS } from '../constants'
import { formatCount } from '../utils/format'

interface ChunkRow {
  id: number
  chunk_index: number
  content: string
  content_html?: string | null
  status: number
  source_type?: number
  error_msg?: string
  embedding_error?: string
}

const props = defineProps<{ data: ChunkRow }>()

const emit = defineEmits<{
  (e: 'edit', row: ChunkRow): void
  (e: 'delete', row: ChunkRow): void
}>()

// source_type 缺省按自动分块处理，手动分块由新增分段弹窗产生
const sourceText = computed(() => CHUNK_SOURCE_TEXT[props.data.source_type === 2 ? 2 : 1])

const isFailed = computed(() => props.data.status === CHUNK_STATUS.FAILED)

// embedding_error 是 error_msg 的兼容字段，两个都可能下发，取到哪个用哪个
const errorMsg = computed(() => props.data.error_msg || props.data.embedding_error || '')
</script>

<style scoped lang="scss">
.chunk-card {
  padding: 14px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background: #fff;
  font-family: PingFang SC, PingFang SC;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;

  &:hover {
    border-color: #1890ff;
    box-shadow: 0 2px 14px 0 rgba(24, 144, 255, 0.1);
  }
}

.card-content {
  // 设计稿固定两行（20px 行高），多余内容截断，卡片高度才不会被长分段撑开
  height: 40px;
  font-size: 14px;
  line-height: 20px;
  color: #606266;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.card-foot {
  height: 20px;
  margin-top: 14px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.card-tag {
  flex-shrink: 0;
  padding: 2.5px 4px;
  border-radius: 3px;
  background: rgba(24, 144, 255, 0.08);
  font-size: 11px;
  line-height: 15px;
  color: #1890ff;
}

.card-count {
  flex-shrink: 0;
  font-size: 12px;
  line-height: 12px;
  color: #606266;
}

.card-status {
  flex-shrink: 0;
  font-size: 12px;
  line-height: 12px;
  color: #ea0000;
}

.card-actions {
  // 前面几项靠左成组，操作区用 auto 外边距推到行尾
  flex-shrink: 0;
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  line-height: 18px;
  color: #1890ff;
}

.card-action {
  cursor: pointer;
}

.card-divider {
  width: 1px;
  height: 12px;
  background: #dcdfe6;
}
</style>
