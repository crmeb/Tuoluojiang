<template>
  <div class="knowledge-card" @click="emit('click', data)">
    <div class="card-header">
      <div class="card-icon" :style="{ backgroundColor: iconColor }">
        <i :class="iconClass" />
      </div>
      <div class="card-title">
        <div class="over-text name" :title="data.name">{{ data.name }}</div>
        <div class="over-text creator">创建者：{{ creator }}</div>
      </div>
    </div>

    <div class="card-desc" :title="data.description">{{ data.description || '暂无描述' }}</div>

    <div class="card-footer">
      <div class="card-stats">
        <template :key="item.label" v-for="(item, index) in stats">
          <el-divider v-if="index > 0" :key="item.label + '-line'" direction="vertical" />
          <span class="stat">{{ item.value }} {{ item.label }}</span>
        </template>
      </div>

      <el-dropdown trigger="click" placement="bottom-end" @command="handleCommand">
        <div class="more-btn" @click.stop>
          <i class="iconfont icongenjinjilu-gengduo" />
        </div>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item v-for="item in CARD_ACTIONS" :key="item.value" :command="item.value">
              {{ item.label }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { CARD_ACTIONS, DEFAULT_ICON_COLOR } from '../constants.ts'
import { getIconClass } from '../utils/icon.ts'

interface KnowledgeRow {
  id: number
  name: string
  description?: string
  icon?: string
  color?: string
  doc_count?: number
  /** 字符数取自动分块长度，单位 byte，卡片按 KB 展示 */
  chunk_size?: number
  app_count?: number
  user?: { name?: string }
}

const props = defineProps<{ data: KnowledgeRow }>()

const emit = defineEmits<{
  (e: 'command', command: string, row: KnowledgeRow): void
  (e: 'click', row: KnowledgeRow): void
}>()

const iconColor = computed(() => props.data.color || DEFAULT_ICON_COLOR)

const iconClass = computed(() => getIconClass(props.data.icon))

const creator = computed(() => (props.data.user && props.data.user.name) || '--')

/** 数量过千折算成 k */
const formatCount = (value: number) => (value >= 1000 ? `${(value / 1000).toFixed(1)}k` : String(value))

/** 字节转 KB，保留一位小数 */
const formatSize = (bytes: number) => `${(bytes / 1024).toFixed(1)}k`

const stats = computed(() => [
  { label: '文档数', value: formatCount(props.data.doc_count || 0) },
  { label: '字符', value: formatSize(props.data.chunk_size || 0) },
  { label: '关联应用', value: formatCount(props.data.app_count || 0) }
])

function handleCommand(command: string) {
  emit('command', command, props.data)
}
</script>

<style scoped lang="scss">
.knowledge-card {
  display: flex;
  flex-direction: column;
  // 撑满 grid 行高，整行卡片才对得齐（行高由 useGridPageSize 下发）
  height: 100%;
  overflow: hidden;
  padding: 14px;
  border: 1px solid #dcdfe6;
  border-radius: 10px;
  background: #fff;
  font-family: PingFang SC, PingFang SC;
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;

  &:hover {
    border-color: #1890ff;
    box-shadow: 0 2px 14px 0 rgba(24, 144, 255, 0.1);
  }
}

.card-header {
  display: flex;
  align-items: center;
}

.card-icon {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  color: #fff;
  font-size: 18px;
}

.card-title {
  flex: 1;
  min-width: 0;
  margin-left: 8px;

  .name {
    font-weight: 500;
    font-size: 14px;
    line-height: 20px;
    color: #303133;
  }

  .creator {
    margin-top: 2px;
    font-size: 12px;
    line-height: 17px;
    color: #606266;
  }
}

.card-desc {
  margin-top: 10px;
  height: 46px;
  font-size: 14px;
  line-height: 23px;
  color: #606266;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.card-footer {
  margin-top: 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-stats {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  font-size: 12px;
  line-height: 17px;
  color: #606266;
  overflow: hidden;

  .stat {
    white-space: nowrap;
  }

  :deep(.el-divider--vertical) {
    margin: 0 12px;
    background-color: #dcdfe6;
  }
}

.more-btn {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  color: #333;
  font-size: 14px;
  cursor: pointer;
  background: #f3f4f5;
}
</style>
