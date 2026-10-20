<template>
  <BaseContainer class="knowledge-edit none-padding">
    <!-- 顶部：返回 + 知识库信息 + 页签 -->
    <div class="edit-header">
      <i class="el-icon-arrow-left back-btn" @click="handleBack" />

      <div class="kb-icon" :style="{ backgroundColor: iconColor }">
        <i :class="iconClass" />
      </div>

      <div class="kb-main">
        <div class="over-text kb-name" :title="detail.name">{{ detail.name }}</div>
        <div class="kb-tabs">
          <span
            v-for="item in EDIT_TABS"
            :key="item.value"
            class="tab-item"
            :class="{ 'is-active': activeTab === item.value }"
            @click="activeTab = item.value"
          >
            {{ item.label }}
          </span>
        </div>
      </div>
    </div>

    <!-- 切到另一个知识库时 path 不变、只有 query 变，加 key 让面板整体重建 -->
    <div :key="kbId" v-loading="isDetailLoading" class="edit-body">
      <div v-if="activeTab === 'document'" class="doc-tab">
        <documentPanel :kb-id="kbId" :active-id="activeDocId" @select="handleSelectDoc" />
        <chunkPanel :doc-id="activeDocId" />
      </div>

      <!-- 关联应用：复用应用列表页的卡片与接口，只读场景仅保留「使用 / 复制链接」 -->
      <applicationList
        v-else-if="activeTab === 'application'"
        title="关联应用"
        :actions="APPLICATION_ACTIONS"
        :params="{ knowledge_id: kbId }"
      />

      <settingPanel v-else :detail="detail" @saved="fetchDetail" />
    </div>
  </BaseContainer>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseContainer from '@/components/BaseContainer/index.vue'
import { knowledgeBaseDetailApi } from '@/api/knowledge.ts'
import documentPanel from './components/documentPanel.vue'
import chunkPanel from './components/chunkPanel.vue'
import settingPanel from './components/settingPanel.vue'
import applicationList from '../components/applicationList.vue'
import { EDIT_TABS, DEFAULT_ICON_COLOR } from './constants.ts'
import { getIconClass } from './utils/icon.ts'

/** 关联应用页签是只读视图，设置与删除留在应用列表页 */
const APPLICATION_ACTIONS = ['use', 'copy']

interface DocumentRow {
  id: number
}

// 取当前实例的 router / route，不用 @/router 的模块单例：
// 单例在 dev 下可能与挂载时的实例不是同一个，push 只改地址栏、视图不动
const route = useRoute()
const router = useRouter()

// 知识库 id 走 query，路由内切换（edit?id=1 → edit?id=2）时 path 不变，
// 这里必须跟着 query 走，否则会一直停在首次进入的那个知识库
const kbId = computed(() => Number(route.query.id) || 0)

const activeTab = ref('document')

const detail = ref<any>({})
const isDetailLoading = ref(false)
const activeDocId = ref(0)

const iconColor = computed(() => detail.value.color || DEFAULT_ICON_COLOR)
const iconClass = computed(() => getIconClass(detail.value.icon))

async function fetchDetail() {
  isDetailLoading.value = true
  try {
    const res = await knowledgeBaseDetailApi(kbId.value)
    detail.value = res.data || {}
  } finally {
    isDetailLoading.value = false
  }
}

function handleSelectDoc(row: DocumentRow | null) {
  activeDocId.value = row ? row.id : 0
}

function handleBack() {
  router.back()
}

watch(
  kbId,
  () => {
    activeTab.value = 'document'
    activeDocId.value = 0
    fetchDetail()
  },
  { immediate: true }
)
</script>

<style scoped lang="scss">
.knowledge-edit {
  display: flex;
  flex-direction: column;
}

.edit-header {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  height: 70px;
  padding: 0 20px;
  border-bottom: 1px solid #dcdfe6;
}

.back-btn {
  font-size: 18px;
  color: #606266;
  cursor: pointer;
}

.kb-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  color: #fff;
  margin-inline: 10px 15px;
  .iconfont {
    font-size: 24px;
  }
}

.kb-main {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding-top: 14px;
}

.kb-name {
  font-weight: 500;
  font-size: 14px;
  line-height: 20px;
  color: #303133;
}

.kb-tabs {
  display: flex;
  align-items: center;
  gap: 24px;
}

.tab-item {
  position: relative;
  font-size: 13px;
  line-height: 20px;
  color: #606266;
  cursor: pointer;
  padding-bottom: 9px;

  &.is-active {
    font-weight: 500;
    color: #1890ff;

    // 选中态下划线，设计稿宽 26px 居中
    &::after {
      content: '';
      position: absolute;
      left: 50%;
      bottom: 0px;
      width: 26px;
      height: 2px;
      margin-left: -13px;
      background: #1890ff;
    }
  }
}

.edit-body {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

// 不留外层内边距：两栏之间的分割线由文档栏的 border-right 撑满整个高度，
// 一旦这里有 padding-top，分割线就接不上顶部页签的下边框
.doc-tab {
  flex: 1;
  min-height: 0;
  display: flex;
}
</style>

<style lang="scss">
@import './styles/confirm.scss';
</style>
