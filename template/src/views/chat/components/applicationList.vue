<!--
  AI 应用卡片列表
  搜索栏 + 卡片栅格 + 分页，以及「使用 / 设置 / 复制链接 / 复制代码 / 删除」五个操作的默认行为。
  应用列表页整页使用；知识库详情「关联应用」页签传 params + actions 复用同一套样式与接口。
-->
<template>
  <div class="application-main">
    <oaFromBox
      :title="title"
      :total="total"
      :search="searchFields"
      :isViewSearch="false"
      :sortSearch="false"
      :isAddBtn="showCreate"
      btnText="创建应用"
      @confirmData="handleSearch"
      @addDataFn="emit('create')"
    />

    <div v-loading="isLoading" class="application-list-wrap">
      <div ref="containerRef" class="application-list-scroll">
        <defaultPage v-if="isEmpty" :index="14" :min-height="520" />
        <div v-else class="application-list" :style="gridStyle">
          <applicationCard
            v-for="item in tableData"
            :key="item.id"
            :data="item"
            :visible-actions="actions"
            @action="handleAction($event, item)"
          />
        </div>
      </div>
    </div>

    <div class="application-pagination el-pagination-reset">
      <el-pagination
        :current-page="page"
        :page-size="pageSize"
        :total="total"
        layout="total, prev, pager, next, jumper"
        @current-change="handlePageChange"
      />
    </div>

    <embedCodeDialog ref="embedCodeDialogRef" />
  </div>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue'
import { ElMessage as Message } from 'element-plus'
import { useUserStore } from '@/stores/user.ts'
import router from '@/router/index.ts'
import { AiEmbeddedManager } from '@/libs/ai/index.ts'
import oaFromBox from '@/components/common/oaFromBox.vue'
import defaultPage from '@/components/common/defaultPage.vue'
import applicationCard from './applicationCard.vue'
import embedCodeDialog from './embedCodeDialog.vue'
import { useGridPageSize } from '@/composables/useGridPageSize.ts'
import { useApplicationList } from '../composables/useApplicationList.ts'
import type { ApplicationRow } from '../composables/useApplicationList.ts'
import { copyVisitorLink } from '../utils/share.ts'
import { APP_TYPE } from '../setting/constants.ts'
import { getApplicationSettingPath } from '../utils/application'

const props = withDefaults(
  defineProps<{
    /** 搜索栏标题 */
    title?: string
    /** 是否展示「创建应用」按钮，点击只抛 create，由使用方决定弹窗 */
    showCreate?: boolean
    /** 需要展示的卡片操作项，不传表示按权限展示全部 */
    actions?: string[]
    /** 搜索表单之外的固定查询条件，如知识库详情页的 { knowledge_id } */
    params?: Record<string, any>
  }>(),
  {
    title: '应用列表',
    showCreate: false,
    actions: undefined,
    params: undefined
  }
)

const emit = defineEmits<{
  (e: 'create'): void
}>()

// 每页条数由容器尺寸算出，首屏等测量完再拉，避免先按初值请求一次
const applicationList = useApplicationList({
  immediate: false,
  extraParams: () => props.params || {}
})

const { page, pageSize, total, tableData, isLoading, isEmpty, searchFields, handleSearch, handleDelete } =
  applicationList

// 卡片按设计稿 364 × 153 排布，容器放得下几行几列就取几条
const { containerRef, gridStyle } = useGridPageSize(applicationList, {
  minColumnWidth: 364,
  rowHeight: 153,
  gap: 16
})

/**
 * 「使用」是唤起悬浮会话，切走页面时销毁。
 *
 * workspace 复用实例并在子应用内切换；visitor 的启动上下文绑定 shareKey，
 * 每次换应用直接销毁旧实例并重建 iframe。
 */
let workspaceInstance: any = null
let visitorInstance: any = null
let visitorRevision = 0

const embedCodeDialogRef = ref<InstanceType<typeof embedCodeDialog> | null>(null)

const handlePageChange = (value: number) => {
  page.value = value
}

const openInstance = async (instance: any, appId?: number) => {
  try {
    await instance?.open(appId)
  } catch (error: any) {
    console.error('AI 会话打开失败', error)
  }
}

/** 同一时刻只留一个临时窗口：两个实例都是无悬浮球的浮层，一起展开会叠在一起 */
const hideOthers = (keep: any) => {
  const others = [workspaceInstance, visitorInstance].filter((item) => item && item !== keep)
  others.forEach((item) => item.hide())
}

const openWorkspaceChat = async (row: ApplicationRow) => {
  if (!workspaceInstance) {
    workspaceInstance = await AiEmbeddedManager.createTemporary({ token: useUserStore().token })
  }
  hideOthers(workspaceInstance)
  await openInstance(workspaceInstance, row.id)
}

const openVisitorChat = async (row: ApplicationRow) => {
  const shareKey = row.share_key || ''
  if (!shareKey) {
    Message.warning('该应用暂无分享链接')
    return
  }

  const revision = ++visitorRevision
  visitorInstance?.destroy()
  visitorInstance = null
  hideOthers(null)

  const created = await AiEmbeddedManager.createTemporary({ identity: 'visitor', shareKey })
  if (revision !== visitorRevision) {
    created?.destroy()
    return
  }
  visitorInstance = created
  await openInstance(visitorInstance)
}

const handleUse = (row: ApplicationRow) => {
  return row.app_type === APP_TYPE.EXTERNAL ? openVisitorChat(row) : openWorkspaceChat(row)
}

const handleSetting = (row: ApplicationRow) => {
  router.push({ path: getApplicationSettingPath(row.app_type), query: { id: String(row.id) } })
}

const handleAction = (command: string, row: ApplicationRow) => {
  if (command === 'use') {
    handleUse(row)
    return
  }
  if (command === 'setting') {
    handleSetting(row)
    return
  }
  if (command === 'copy') {
    copyVisitorLink(row.share_key || '')
    return
  }
  if (command === 'copyCode') {
    embedCodeDialogRef.value?.openBox(row)
    return
  }
  handleDelete(row)
}

onBeforeUnmount(() => {
  visitorRevision += 1
  workspaceInstance?.destroy()
  visitorInstance?.destroy()
})

defineExpose({ refresh: applicationList.fetchData })
</script>

<style scoped lang="scss">
.application-main {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  padding: 20px 20px 10px;
}

// 与 .flex-layout-table 同一套思路：外层只负责占满剩余空间，内容绝对定位撑不高它，
// 否则卡片多排一行会把外层容器顶高，测出的容器高度跟着变大
.application-list-wrap {
  position: relative;
  flex: 1;
  min-height: 0;
  margin-top: 20px;
}

.application-list-scroll {
  position: absolute;
  inset: 0;
  overflow-y: auto;
  // 滚动条出现 / 消失会改变可用宽度，留白常驻避免列数来回抖动
  scrollbar-gutter: stable;
}

.application-list {
  display: grid;
  // 列数与行高由 useGridPageSize 按容器尺寸下发，minmax(0, 1fr) 让整行等分排满
  grid-template-columns: repeat(var(--grid-cols, 4), minmax(0, 1fr));
  grid-auto-rows: var(--grid-row-h, 153px);
  gap: var(--grid-gap, 15px);
  align-content: start;
}

.application-pagination {
  display: flex;
  justify-content: flex-end;

  :deep(.el-pagination) {
    margin-top: 0;
  }
}
</style>
