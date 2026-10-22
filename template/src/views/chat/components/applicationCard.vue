<template>
  <div class="application-card">
    <div class="card-header">
      <img :src="data.pic || defaultAppIcon" alt="" class="card-icon" />
      <div class="card-title">
        <div class="over-text name" :title="data.name">{{ data.name }}</div>
        <div class="over-text creator">创建者：{{ creator }}</div>
      </div>
      <div class="card-tags">
        <span v-if="appTypeLabel" class="tag" :class="{ 'is-external': isExternal }">{{ appTypeLabel }}</span>
        <span class="tag">{{ isPublished ? '已发布' : '未发布' }}</span>
      </div>
    </div>

    <div class="card-desc" :title="data.info">{{ data.info }}</div>

    <div class="card-actions">
      <template v-for="(item, index) in actions">
        <el-divider v-if="index > 0" :key="item.value + '-line'" direction="vertical" />
        <span :key="item.value" class="action" @click="emit('action', item.value)">{{ item.label }}</span>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import defaultAppIcon from '@/assets/images/ai.png'
// 应用类型枚举与文案跟设置页共用一份，避免两处各写一套「内部 / 外部应用」
import { APP_TYPE, APP_TYPE_OPTIONS } from '../setting/constants'
import { APP_STATUS } from '../constants'

interface ApplicationRow {
  id: number
  name: string
  info?: string
  pic?: string
  status: number
  app_type: number
  share_key?: string
  auth?: boolean
  user?: { name?: string } | null
}

const props = withDefaults(
  defineProps<{
    data: ApplicationRow
    /** 需要展示的操作项，不传表示全部；仍受权限与应用类型的二次过滤 */
    visibleActions?: string[]
  }>(),
  { visibleActions: undefined }
)

const emit = defineEmits<{
  (e: 'action', command: string): void
}>()

const creator = computed(() => (props.data.user && props.data.user.name) || '--')

const isPublished = computed(() => props.data.status === APP_STATUS.PUBLISHED)

const isExternal = computed(() => props.data.app_type === APP_TYPE.EXTERNAL)

const appTypeLabel = computed(() => {
  const option = APP_TYPE_OPTIONS.find((item) => item.value === props.data.app_type)
  return option ? option.label : ''
})

/**
 * 底部操作项
 *
 * 「设置 / 删除」按编辑权限（auth）显隐；「复制链接」只给外部应用——
 * 内部应用没有访客端入口，链接复制出去也打不开。
 * 「复制代码」再多一层已发布：代码是贴到第三方站点上的，未发布的应用贴过去只会白屏。
 * visibleActions 再做一层白名单，供只读场景（如知识库详情的关联应用）收敛操作项。
 */
const actions = computed(() => {
  const list = [{ label: '使用', value: 'use' }]
  if (props.data.auth) list.push({ label: '设置', value: 'setting' })
  if (isExternal.value) list.push({ label: '复制链接', value: 'copy' })
  if (isExternal.value && isPublished.value) list.push({ label: '复制代码', value: 'copyCode' })
  if (props.data.auth) list.push({ label: '删除', value: 'delete' })
  const allowed = props.visibleActions
  return allowed ? list.filter((item) => allowed.includes(item.value)) : list
})
</script>

<style scoped lang="scss">
.application-card {
  display: flex;
  flex-direction: column;
  // 设计稿卡片固定 153 高，描述统一两行，整行卡片高度才对得齐
  height: 153px;
  padding: 14px;
  border: 1px solid #dcdfe6;
  border-radius: 10px;
  background: #fff;
  font-family: PingFang SC, PingFang SC;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;

  &:hover {
    border-color: #1890ff;
    box-shadow: 0 2px 14px 0 rgba(24, 144, 255, 0.1);
  }
}

.card-header {
  display: flex;
  align-items: center;
  // 高度取图标，标题块（20 + 17）比它高，居中后上下各溢出 2.5px，与设计稿一致
  height: 32px;
}

.card-icon {
  flex-shrink: 0;
  display: block;
  width: 32px;
  height: 32px;
  border-radius: 4px;
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
    font-size: 12px;
    line-height: 17px;
    color: #606266;
  }
}

.card-tags {
  flex-shrink: 0;
  // 标签与图标顶对齐，不跟着标题块居中
  align-self: flex-start;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: 8px;
}

.tag {
  padding: 4px 6px;
  border: 0.5px solid #1890ff;
  border-radius: 3px;
  background: rgba(24, 144, 255, 0.05);
  font-size: 12px;
  line-height: 12px;
  color: #1890ff;
  white-space: nowrap;

  // 外部应用用橙色区分，其余标签（内部应用、发布状态）统一主色
  &.is-external {
    border-color: #ff9900;
    background: rgba(255, 153, 0, 0.05);
    color: #ff9900;
  }
}

.card-desc {
  margin-top: 12px;
  height: 40px;
  font-size: 14px;
  line-height: 20px;
  color: #606266;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.card-actions {
  // 描述不足两行时操作区仍压在卡片底部
  margin-top: auto;
  display: flex;
  align-items: center;
  height: 18px;
  font-size: 13px;
  line-height: 18px;
  color: #1890ff;

  .action {
    cursor: pointer;
  }

  :deep(.el-divider--vertical) {
    height: 12px;
    margin: 0 10px;
    background-color: #dcdfe6;
  }
}
</style>
