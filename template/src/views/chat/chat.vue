<template>
  <div class="divBox">
    <el-card
      v-loading="loading"
      class="ai-page el-card-flex"
      element-loading-text="AI 助手加载中..."
      body-style="padding: 0"
    >
      <div v-if="loadError" class="ai-error">
        <span>AI 助手加载失败</span>
        <el-button type="text" @click="mountChat">重新加载</el-button>
      </div>
      <div v-show="!loadError" ref="containerRef" class="ai-frame" />
    </el-card>
  </div>
</template>

<script lang="ts" setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import store from '@/store'
import router from '@/router'
import { AiEmbeddedManager } from '@/libs/ai'

/**
 * AI 助手整页。
 *
 * 与悬浮球是同一套子应用，差别只在承载方式：inline 填满下面这个容器，
 * 没有悬浮球也没有缩放收起按钮，高度由 OA 这边的容器决定 ——
 * 子应用不认识宿主页面的布局，容器没有确定高度它就撑不开。
 */
const containerRef = ref<HTMLElement | null>(null)
const loadError = ref(false)
const loading = ref(true)

let instance: any = null
let isDestroyed = false
let readyTimer: number | null = null
let loadRevision = 0

/** 握手迟迟不来时的兜底：子应用可能只是慢，不该让转圈一直挂着 */
const READY_TIMEOUT = 15000

/** 不带 id 就开工作台首页，带上就直接进那个应用 */
const appId = String(router.currentRoute.query.id || '') || undefined

const stopWaitingReady = () => {
  if (readyTimer !== null) {
    clearTimeout(readyTimer)
    readyTimer = null
  }
}

const finishLoading = () => {
  stopWaitingReady()
  loading.value = false
}

/**
 * createInline 返回时 iframe 才刚插进 DOM；whenReady resolve 才表示握手完成。
 */
const waitForReady = async (target: any, revision: number) => {
  try {
    await Promise.race([
      target.whenReady(),
      new Promise<void>((resolve) => {
        readyTimer = window.setTimeout(resolve, READY_TIMEOUT)
      })
    ])
  } catch {
    if (revision === loadRevision && loading.value) loadError.value = true
  } finally {
    if (revision === loadRevision) finishLoading()
  }
}

const destroyInstance = () => {
  stopWaitingReady()
  instance?.destroy()
  instance = null
}

const mountChat = async () => {
  if (!containerRef.value) return
  const revision = ++loadRevision
  loadError.value = false
  loading.value = true
  destroyInstance()

  try {
    // 先走一次宿主请求层；access token 过期时会等待 refresh token 刷新并重试，
    // 确保 SDK 初始化时拿到的是最新 token。
    await store.dispatch('user/fetchUserInfo')
  } catch {
    if (revision === loadRevision) {
      loadError.value = true
      loading.value = false
    }
    return
  }

  if (isDestroyed || revision !== loadRevision || !containerRef.value) return

  const created = await AiEmbeddedManager.createInline({
    mount: containerRef.value,
    token: store.getters.token,
    appId
  })

  // 创建是异步的，组件可能在这之前就销毁了，否则会留下一个没人回收的 iframe
  if (isDestroyed || revision !== loadRevision) {
    created?.destroy()
    return
  }
  if (!created) {
    loadError.value = true
    loading.value = false
    return
  }
  instance = created
  void waitForReady(created, revision)
}

onMounted(mountChat)

onBeforeUnmount(() => {
  isDestroyed = true
  loadRevision += 1
  destroyInstance()
})
</script>

<style scoped lang="scss">
/* 高度必须是确定值而不是 min-height：iframe 撑不开一个没有高度的父级 */
.ai-page {
  height: calc(100vh - 82px);
  overflow: hidden;
}

.ai-frame {
  flex: 1;
  min-height: 0;
}

.ai-error {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #909399;
}

.divBox .el-card {
  border-radius: 8px;
}
</style>
