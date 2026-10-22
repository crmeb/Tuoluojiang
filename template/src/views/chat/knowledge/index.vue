<template>
  <div class="divBox">
    <el-card class="normal-page el-card-flex" body-style="padding: 0">
      <div class="knowledge-main">
        <oaFromBox
          title="知识库列表"
          :total="total"
          :search="searchFields"
          :isViewSearch="false"
          :sortSearch="false"
          btnText="创建知识库"
          @confirmData="handleSearch"
          @addDataFn="handleAdd"
        />

        <div v-loading="isLoading" class="knowledge-list-wrap">
          <div ref="containerRef" class="knowledge-list-scroll">
            <defaultPage v-if="isEmpty" :index="14" :min-height="520" />
            <div v-else class="knowledge-list" :style="gridStyle">
              <knowledgeCard
                v-for="item in tableData"
                :key="item.id"
                :data="item"
                @command="handleCommand"
                @click="handleOpen"
              />
            </div>
          </div>
        </div>

        <div class="knowledge-pagination el-pagination-reset">
          <el-pagination
            :current-page="page"
            :page-size="pageSize"
            :total="total"
            layout="total, prev, pager, next, jumper"
            @current-change="handlePageChange"
          />
        </div>
      </div>
    </el-card>

    <!-- 知识库创建 / 设置 -->
    <knowledgeDialog ref="knowledgeDialogRef" @isOk="fetchData" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Message } from 'element-ui'
import { useRouter } from 'vue-router/composables'
import { roterPre } from '@/settings'
import oaFromBox from '@/components/common/oaFromBox.vue'
import defaultPage from '@/components/common/defaultPage.vue'
import knowledgeCard from './components/knowledgeCard.vue'
import knowledgeDialog from './components/knowledgeDialog.vue'
import { useGridPageSize } from '@/composables/useGridPageSize'
import { useKnowledgeList } from './composables/useKnowledgeList'

interface KnowledgeRow {
  id: number
  name: string
  app_count?: number
}

// 取当前实例的 router，不用 @/router 的模块单例：
// 单例在 dev 下可能与挂载时的实例不是同一个，push 只改地址栏、视图不动
const router = useRouter()

const knowledgeDialogRef = ref<any>(null)

// 每页条数由容器尺寸算出，首屏等测量完再拉，避免先按初值请求一次
const knowledgeList = useKnowledgeList({ immediate: false })

const {
  page,
  pageSize,
  total,
  tableData,
  isLoading,
  isEmpty,
  searchFields,
  fetchData,
  handleSearch,
  handleDelete
} = knowledgeList

// 卡片按设计稿 320 × 167 排布，容器放得下几行几列就取几条
const { containerRef, gridStyle } = useGridPageSize(knowledgeList, {
  minColumnWidth: 320,
  rowHeight: 167,
  gap: 20
})

const handlePageChange = (value: number) => {
  page.value = value
}

const handleAdd = () => {
  knowledgeDialogRef.value.openBox()
}

const handleOpen = (row: KnowledgeRow) => {
  router.push({ path: `${roterPre}/chat/knowledge/edit`, query: { id: String(row.id) } })
}

const handleCommand = (command: string, row: KnowledgeRow) => {
  if (command === 'setting') {
    // 设置进详情页，弹窗只留给新建
    handleOpen(row)
    return
  }
  if (command === 'delete') {
    handleDelete(row)
    return
  }
}
</script>

<style scoped lang="scss">
.knowledge-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 20px 20px 10px;
}

// 与 .flex-layout-table 同一套思路：外层只负责占满剩余空间，内容绝对定位撑不高它，
// 否则卡片多排一行会把 .normal-page 顶高，测出的容器高度跟着变大
.knowledge-list-wrap {
  position: relative;
  flex: 1;
  min-height: 0;
  margin-top: 20px;
}

.knowledge-list-scroll {
  position: absolute;
  inset: 0;
  overflow-y: auto;
  // 滚动条出现 / 消失会改变可用宽度，留白常驻避免列数来回抖动
  scrollbar-gutter: stable;
}

.knowledge-list {
  display: grid;
  // 列数与行高由 useGridPageSize 按容器尺寸下发，minmax(0, 1fr) 让整行等分排满
  grid-template-columns: repeat(var(--grid-cols, 4), minmax(0, 1fr));
  grid-auto-rows: var(--grid-row-h, 167px);
  gap: var(--grid-gap, 20px);
  align-content: start;
}

.knowledge-pagination {
  display: flex;
  justify-content: flex-end;
}

.divBox .el-pagination {
  margin-top: 0px;
}
</style>

<style lang="scss">
@import './styles/confirm.scss';
</style>
