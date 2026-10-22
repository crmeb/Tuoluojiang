<!-- @FileDescription: PDF 预览组件（pdf.js 渲染，按需分页渲染 + 文本层可选中） -->
<template>
  <div class="pdf-viewer">
    <div ref="scrollerRef" class="pdf-viewer__scroller" @scroll.passive="scheduleUpdate">
      <div v-if="viewStatus === 'loading'" class="pdf-viewer__tip">
        <i class="el-icon-loading" />
        <span>{{ progress > 0 ? `PDF 加载中 ${progress}%` : 'PDF 加载中...' }}</span>
      </div>

      <div v-else-if="viewStatus === 'error'" class="pdf-viewer__tip is-error">
        <i class="el-icon-warning-outline" />
        <span>{{ errorMsg }}</span>
        <el-button size="mini" plain @click="load">重新加载</el-button>
      </div>

      <div v-else class="pdf-viewer__pages">
        <div
          v-for="page in pages"
          :key="page.num"
          class="pdf-page"
          :data-page="page.num"
          :style="pageStyle(page)"
        >
          <canvas class="pdf-page__canvas" />
          <div class="pdf-page__text" />
          <span class="pdf-page__badge" aria-hidden="true">{{ page.num }}</span>
        </div>
      </div>
    </div>

    <div v-if="viewStatus === 'ready'" class="pdf-toolbar">
      <button class="pdf-toolbar__btn" :disabled="currentPage <= 1" title="上一页" @click="goToPage(currentPage - 1)">
        <i class="el-icon-arrow-up" />
      </button>
      <span class="pdf-toolbar__pager">
        <input
          class="pdf-toolbar__input"
          name="pdf-page"
          aria-label="页码"
          :value="pageInput"
          @focus="pageInputActive = true"
          @input="onPageInput"
          @blur="submitPageInput"
          @keyup.enter="submitPageInput"
        />
        <em>/ {{ pages.length }}</em>
      </span>
      <button
        class="pdf-toolbar__btn"
        :disabled="currentPage >= pages.length"
        title="下一页"
        @click="goToPage(currentPage + 1)"
      >
        <i class="el-icon-arrow-down" />
      </button>

      <i class="pdf-toolbar__split" />

      <button class="pdf-toolbar__btn" :disabled="scale <= MIN_SCALE" title="缩小" @click="zoomBy(-1)">
        <i class="el-icon-zoom-out" />
      </button>
      <span class="pdf-toolbar__percent" title="恢复 100%" @click="setScale(1, 'custom')">
        {{ Math.round(scale * 100) }}%
      </span>
      <button class="pdf-toolbar__btn" :disabled="scale >= MAX_SCALE" title="放大" @click="zoomBy(1)">
        <i class="el-icon-zoom-in" />
      </button>
      <button
        class="pdf-toolbar__btn"
        :class="{ 'is-active': zoomMode === 'auto' }"
        title="自适应大小"
        @click="fitWidth"
      >
        <i class="el-icon-rank" />
      </button>

      <i class="pdf-toolbar__split" />

      <button class="pdf-toolbar__btn" title="旋转 90°" @click="rotate">
        <i class="el-icon-refresh-right" />
      </button>
      <a class="pdf-toolbar__btn" :href="url" target="_blank" rel="noopener" title="下载">
        <i class="el-icon-download" />
      </a>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

// 父级 openFile/index.vue 通过 <component> 透传了 fid / file / editBtn 等无关属性，
// 关掉 inheritAttrs 以免它们被渲染成根节点上的无意义 DOM 属性
export default defineComponent({ name: 'PreviewPdf', inheritAttrs: false })
</script>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch, nextTick } from 'vue'
import * as PDFJS from 'pdfjs-dist'
// Vite 下 worker 必须走 ?url 拿到构建后可访问的地址；
// 写成 new URL('pdfjs-dist/build/...', import.meta.url) 是 webpack 习惯，
// 裸包名不会被 Vite 重写，运行时会解析成组件目录下的相对路径而 404
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url'
import { BASE_URL } from '@/config/env'

type ViewStatus = 'loading' | 'ready' | 'error'
type PageStatus = 'idle' | 'rendering' | 'done'
type ZoomMode = 'auto' | 'custom'

interface PageItem {
  num: number
  /** 缩放 1、未叠加用户旋转时的宽度（已含 userUnit） */
  baseW: number
  baseH: number
  status: PageStatus
}

const props = withDefaults(
  defineProps<{
    url?: string
    /** 初始缩放比例，0 表示按容器宽度自适应 */
    initialScale?: number
  }>(),
  {
    url: '',
    initialScale: 0
  }
)

const emit = defineEmits<{
  (e: 'pdf-error', err: unknown): void
}>()

const MIN_SCALE = 0.25
const MAX_SCALE = 4
/**
 * 自适应模式的比例上限，取值与 pdf.js 官方 viewer 的 MAX_AUTO_SCALE 一致。
 * 宽屏下纯按容器宽度铺满会算出 300% 上下，正文大得没法读，所以要封顶。
 */
const MAX_AUTO_SCALE = 1.25
/** 单页位图的像素预算，防止高倍缩放下一页就吃掉几十 MB 显存 */
const MAX_CANVAS_PIXELS = 8e6
const ZOOM_STEPS = [0.25, 0.5, 0.75, 1, 1.25, 1.5, 2, 2.5, 3, 4]
/** 页间距，与样式里的 .pdf-viewer__pages gap 保持一致 */
const PAGE_GAP = 16
/** 滚动容器内边距，与样式里的 padding 保持一致 */
const PAGE_GUTTER = 24
/** 超出可视区多少屏仍保留渲染结果 */
const RENDER_BUFFER_SCREENS = 1

PDFJS.GlobalWorkerOptions.workerSrc = pdfWorkerUrl

const scrollerRef = ref<HTMLElement | null>(null)
const viewStatus = ref<ViewStatus>('loading')
const errorMsg = ref('')
const progress = ref(0)
const pages = ref<PageItem[]>([])
const scale = ref(1)
const rotation = ref(0)
const zoomMode = ref<ZoomMode>('auto')
const currentPage = ref(1)
const pageInput = ref('1')
const pageInputActive = ref(false)

// 以下均为非响应式的运行时句柄，放 ref 里只会白白付出代理成本
let loadingTask: ReturnType<typeof PDFJS.getDocument> | null = null
let pdfDoc: Awaited<ReturnType<typeof PDFJS.getDocument>['promise']> | null = null
/** 每次 load 自增，用于丢弃上一次加载遗留的异步回调 */
let loadToken = 0
const renderTasks = new Map<number, { cancel: () => void }>()
const textLayers = new Map<number, { cancel: () => void }>()
let updateTimer = 0
let resizeObserver: ResizeObserver | null = null

/* ---------------- 尺寸计算 ---------------- */

const isSwapped = () => rotation.value % 180 !== 0
const displayWidth = (page: PageItem) => (isSwapped() ? page.baseH : page.baseW) * scale.value
const displayHeight = (page: PageItem) => (isSwapped() ? page.baseW : page.baseH) * scale.value

function pageStyle(page: PageItem) {
  return {
    width: `${Math.round(displayWidth(page))}px`,
    height: `${Math.round(displayHeight(page))}px`,
    // 文本层的字号靠该变量还原，必须与渲染 viewport 的实际缩放一致
    '--total-scale-factor': String(scale.value)
  }
}

/** 各页在滚动容器内的 offsetTop，直接按已知尺寸算，避免逐页读 DOM 触发重排 */
function pageOffsets(): number[] {
  const offsets: number[] = []
  let top = PAGE_GUTTER
  for (const page of pages.value) {
    offsets.push(top)
    top += displayHeight(page) + PAGE_GAP
  }
  return offsets
}

function clampScale(value: number) {
  return Math.min(Math.max(value, MIN_SCALE), MAX_SCALE)
}

/** 按容器宽度算出的自适应缩放（封顶到 MAX_AUTO_SCALE） */
function computeAutoScale() {
  const el = scrollerRef.value
  const first = pages.value[0]
  if (!el || !first) return 1
  const baseW = isSwapped() ? first.baseH : first.baseW
  const avail = el.clientWidth - PAGE_GUTTER * 2
  if (avail <= 0 || baseW <= 0) return 1
  return clampScale(Math.min(avail / baseW, MAX_AUTO_SCALE))
}

function queryPage(num: number) {
  return scrollerRef.value?.querySelector<HTMLElement>(`[data-page="${num}"]`) ?? null
}

/* ---------------- 加载 ---------------- */

async function load() {
  const token = ++loadToken
  await destroyDoc()

  pages.value = []
  progress.value = 0
  currentPage.value = 1
  pageInput.value = '1'
  rotation.value = 0

  if (!props.url) {
    viewStatus.value = 'error'
    errorMsg.value = 'PDF 地址不能为空'
    return
  }

  viewStatus.value = 'loading'
  errorMsg.value = ''

  try {
    const task = PDFJS.getDocument({
      url: props.url,
      // cmaps 已随 public 目录发布，用 BASE_URL 拼绝对路径，
      // 写相对路径会随当前路由层级漂移
      cMapUrl: `${BASE_URL}cmaps/`,
      cMapPacked: true,
      withCredentials: false
    })
    loadingTask = task
    task.onProgress = ({ loaded, total }: { loaded: number; total: number }) => {
      if (token === loadToken && total > 0) {
        progress.value = Math.min(100, Math.round((loaded / total) * 100))
      }
    }

    const doc = await task.promise
    // 期间已切到别的文件：新一轮 load 开头的 destroyDoc 已经把本次 task 拆掉了
    if (token !== loadToken) return
    pdfDoc = doc

    // 首页尺寸兜底所有页的占位高度，避免为了排版把每一页都取回来
    const first = await doc.getPage(1)
    if (token !== loadToken) return
    const baseViewport = first.getViewport({ scale: 1 })

    pages.value = Array.from({ length: doc.numPages }, (_, i) => ({
      num: i + 1,
      baseW: baseViewport.width,
      baseH: baseViewport.height,
      status: 'idle' as PageStatus
    }))
    viewStatus.value = 'ready'

    await nextTick()
    if (token !== loadToken) return
    if (props.initialScale > 0) {
      zoomMode.value = 'custom'
      scale.value = clampScale(props.initialScale)
    } else {
      zoomMode.value = 'auto'
      scale.value = computeAutoScale()
    }
    await nextTick()
    updateVisible()
  } catch (error) {
    if (token !== loadToken) return
    console.error('PDF 加载失败:', error)
    emit('pdf-error', error)
    viewStatus.value = 'error'
    errorMsg.value = resolveErrorMsg(error)
  }
}

function resolveErrorMsg(error: any) {
  switch (error?.name) {
    case 'MissingPDFException':
      return 'PDF 文件不存在或已损坏'
    case 'InvalidPDFException':
      return 'PDF 文件格式无效'
    case 'PasswordException':
      return '该 PDF 已加密，暂不支持在线预览'
    case 'UnexpectedResponseException':
      return '文件服务响应异常，请稍后重试'
    default:
      return error?.message ? `PDF 加载失败：${error.message}` : 'PDF 加载失败，请检查文件地址或网络'
  }
}

/* ---------------- 渲染 ---------------- */

async function renderPage(page: PageItem) {
  if (page.status !== 'idle' || !pdfDoc) return
  const token = loadToken
  page.status = 'rendering'

  try {
    const proxy = await pdfDoc.getPage(page.num)
    // 每个 await 后都要复核：期间可能已切换文件、改了缩放或被移出可视区
    if (token !== loadToken || page.status !== 'rendering') return

    // 拿到真实尺寸后修正占位，混排页面（横竖混合）才不会跳版
    const rawViewport = proxy.getViewport({ scale: 1 })
    if (page.baseW !== rawViewport.width || page.baseH !== rawViewport.height) {
      page.baseW = rawViewport.width
      page.baseH = rawViewport.height
      await nextTick()
      if (token !== loadToken || page.status !== 'rendering') return
    }

    const wrapper = queryPage(page.num)
    const canvas = wrapper?.querySelector('canvas') as HTMLCanvasElement | null
    const textEl = wrapper?.querySelector('.pdf-page__text') as HTMLElement | null
    if (!canvas || !textEl) {
      page.status = 'idle'
      return
    }

    const viewport = proxy.getViewport({
      scale: scale.value,
      rotation: (proxy.rotate + rotation.value) % 360
    })
    // 高分屏下按 DPR 放大位图，封顶 2 倍：再高肉眼收益极小，显存却成倍上涨
    let outputScale = Math.min(window.devicePixelRatio || 1, 2)
    const pixels = viewport.width * viewport.height * outputScale * outputScale
    if (pixels > MAX_CANVAS_PIXELS) {
      outputScale *= Math.sqrt(MAX_CANVAS_PIXELS / pixels)
    }
    canvas.width = Math.floor(viewport.width * outputScale)
    canvas.height = Math.floor(viewport.height * outputScale)

    const task = proxy.render({
      canvas,
      viewport,
      transform: outputScale === 1 ? undefined : [outputScale, 0, 0, outputScale, 0, 0]
    })
    renderTasks.set(page.num, task)
    await task.promise
    renderTasks.delete(page.num)
    if (token !== loadToken || page.status !== 'rendering') return

    textEl.textContent = ''
    const textLayer = new PDFJS.TextLayer({
      textContentSource: proxy.streamTextContent(),
      container: textEl,
      viewport
    })
    textLayers.set(page.num, textLayer)
    await textLayer.render()
    textLayers.delete(page.num)
    if (token !== loadToken || page.status !== 'rendering') return

    page.status = 'done'
  } catch (error: any) {
    renderTasks.delete(page.num)
    textLayers.delete(page.num)
    // 主动取消（翻页/缩放）不是异常，静默回到待渲染态即可
    if (error?.name !== 'RenderingCancelledException' && token === loadToken) {
      console.error(`PDF 第 ${page.num} 页渲染失败:`, error)
    }
    if (page.status === 'rendering') page.status = 'idle'
  }
}

/** 释放远离可视区页面的位图与文本层，长文档下这是内存不爆的关键 */
function releasePage(page: PageItem) {
  if (page.status === 'idle') return
  page.status = 'idle'

  renderTasks.get(page.num)?.cancel()
  renderTasks.delete(page.num)
  textLayers.get(page.num)?.cancel()
  textLayers.delete(page.num)

  const wrapper = queryPage(page.num)
  const canvas = wrapper?.querySelector('canvas') as HTMLCanvasElement | null
  if (canvas) {
    canvas.width = 0
    canvas.height = 0
  }
  const textEl = wrapper?.querySelector('.pdf-page__text') as HTMLElement | null
  if (textEl) textEl.textContent = ''
}

function resetAllPages() {
  pages.value.forEach(releasePage)
}

function updateVisible() {
  const el = scrollerRef.value
  if (!el || viewStatus.value !== 'ready') return

  const offsets = pageOffsets()
  const viewH = el.clientHeight
  const top = el.scrollTop - viewH * RENDER_BUFFER_SCREENS
  const bottom = el.scrollTop + viewH * (1 + RENDER_BUFFER_SCREENS)
  // 取视口上三分之一处所在页作为「当前页」，比用顶边更贴近阅读位置
  const focus = el.scrollTop + viewH / 3

  let current = 1
  pages.value.forEach((page, i) => {
    const offset = offsets[i]
    const height = displayHeight(page)
    if (offset <= focus) current = page.num
    if (offset < bottom && offset + height > top) renderPage(page)
    else releasePage(page)
  })

  currentPage.value = current
  if (!pageInputActive.value) pageInput.value = String(current)
}

function scheduleUpdate() {
  if (updateTimer) return
  updateTimer = requestAnimationFrame(() => {
    updateTimer = 0
    updateVisible()
  })
}

/* ---------------- 交互 ---------------- */

function setScale(next: number, mode: ZoomMode) {
  const value = clampScale(next)
  zoomMode.value = mode
  if (Math.abs(value - scale.value) < 0.001) return

  const el = scrollerRef.value
  const ratio = value / scale.value
  const anchor = el ? el.scrollTop : 0

  scale.value = value
  resetAllPages()

  nextTick(() => {
    // 页高整体按同一比例变化，滚动位置同比缩放即可保持视线落点
    if (el) el.scrollTop = anchor * ratio
    updateVisible()
  })
}

function zoomBy(direction: number) {
  const current = scale.value
  const next =
    direction > 0
      ? ZOOM_STEPS.find((step) => step > current + 0.001)
      : [...ZOOM_STEPS].reverse().find((step) => step < current - 0.001)
  setScale(next ?? (direction > 0 ? MAX_SCALE : MIN_SCALE), 'custom')
}

function fitWidth() {
  setScale(computeAutoScale(), 'auto')
}

function rotate() {
  rotation.value = (rotation.value + 90) % 360
  resetAllPages()
  nextTick(() => {
    if (zoomMode.value === 'auto') scale.value = computeAutoScale()
    nextTick(updateVisible)
  })
}

function goToPage(num: number) {
  const target = Math.min(Math.max(Math.round(num) || 1, 1), pages.value.length)
  const el = scrollerRef.value
  if (!el) return
  el.scrollTop = Math.max(0, pageOffsets()[target - 1] - PAGE_GUTTER / 2)
  currentPage.value = target
  pageInput.value = String(target)
  updateVisible()
}

function onPageInput(event: Event) {
  pageInput.value = (event.target as HTMLInputElement).value
}

function submitPageInput() {
  pageInputActive.value = false
  const num = Number(pageInput.value)
  if (!Number.isFinite(num)) {
    pageInput.value = String(currentPage.value)
    return
  }
  goToPage(num)
}

/** Ctrl / ⌘ + 滚轮缩放，与浏览器原生 PDF 阅读器习惯一致 */
function onWheel(event: WheelEvent) {
  if (!event.ctrlKey && !event.metaKey) return
  event.preventDefault()
  zoomBy(event.deltaY < 0 ? 1 : -1)
}

/* ---------------- 生命周期 ---------------- */

async function destroyDoc() {
  renderTasks.forEach((task) => task.cancel())
  renderTasks.clear()
  textLayers.forEach((layer) => layer.cancel())
  textLayers.clear()

  const task = loadingTask
  pdfDoc = null
  loadingTask = null
  try {
    // v6 的 PDFDocumentProxy 已不再暴露 destroy()，
    // 释放 worker 与网络请求只能通过 loadingTask
    await task?.destroy()
  } catch {
    // 文档尚未加载完成时 destroy 会让 task.promise 以异常收场，属预期行为
  }
}

onMounted(() => {
  const el = scrollerRef.value
  if (!el) return
  el.addEventListener('wheel', onWheel, { passive: false })

  if (typeof ResizeObserver !== 'undefined') {
    let lastWidth = el.clientWidth
    resizeObserver = new ResizeObserver(() => {
      // 只认宽度变化：缩放会改变页高进而影响滚动条，若跟着高度一起重算，
      // 滚动条的出现/消失会和自适应比例互相触发形成抖动
      if (el.clientWidth === lastWidth) return
      lastWidth = el.clientWidth
      if (zoomMode.value === 'auto') setScale(computeAutoScale(), 'auto')
      else scheduleUpdate()
    })
    resizeObserver.observe(el)
  }
})

onBeforeUnmount(() => {
  loadToken++
  if (updateTimer) cancelAnimationFrame(updateTimer)
  scrollerRef.value?.removeEventListener('wheel', onWheel)
  resizeObserver?.disconnect()
  resizeObserver = null
  destroyDoc()
})

// 原实现把 loadPdf 挂在 immediate watch 上，created 阶段就去写 $refs.pdfContent，
// 此时模板还没挂载，必定抛 "Cannot set properties of undefined"，PDF 因此从未开始加载
watch(() => props.url, load, { immediate: false })
onMounted(load)

defineExpose({ reload: load, goToPage })
</script>

<style scoped lang="scss">
.pdf-viewer {
  position: relative;
  height: calc(100vh - 60px);
  background: #f2f3f5;

  &__scroller {
    height: 100%;
    overflow: auto;
    padding: 24px;
    box-sizing: border-box;
  }

  &__pages {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
  }

  &__tip {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #909399;
    font-size: 14px;

    &.is-error {
      color: #f56c6c;
    }
  }
}

.pdf-page {
  position: relative;
  flex: none;
  background: #fff;
  box-shadow: 0 1px 6px rgba(0, 0, 0, 0.12);

  &__canvas {
    display: block;
    width: 100%;
    height: 100%;
  }

  &__badge {
    position: absolute;
    right: 8px;
    bottom: 8px;
    padding: 1px 8px;
    border-radius: 10px;
    background: rgba(0, 0, 0, 0.35);
    color: #fff;
    font-size: 12px;
    line-height: 18px;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s;
  }

  &:hover &__badge {
    opacity: 1;
  }
}

// pdf.js 的文本层样式：只摘 pdf_viewer.css 里渲染必需的那几条，
// 整份引入是 160KB+ 且大半是标注/编辑器用不到的规则
.pdf-page__text {
  position: absolute;
  inset: 0;
  overflow: clip;
  line-height: 1;
  text-align: initial;
  text-size-adjust: none;
  forced-color-adjust: none;
  transform-origin: 0 0;
  --min-font-size: 1;
  --text-scale-factor: calc(var(--total-scale-factor) * var(--min-font-size));
  --min-font-size-inv: calc(1 / var(--min-font-size));
  // TextLayer.render() 会用 round(down, …, var(--scale-round-x)) 写死层宽高，
  // 这两个变量缺省会让该表达式整体失效
  --scale-round-x: 1px;
  --scale-round-y: 1px;
}

.pdf-page__text ::v-deep {
  span,
  br {
    position: absolute;
    color: transparent;
    white-space: pre;
    cursor: text;
    transform-origin: 0% 0%;
    user-select: text;
  }

  > :not(.markedContent),
  .markedContent span:not(.markedContent) {
    z-index: 1;
    --font-height: 0;
    --scale-x: 1;
    --rotate: 0deg;
    font-size: calc(var(--text-scale-factor) * var(--font-height));
    transform: rotate(var(--rotate)) scaleX(var(--scale-x)) scale(var(--min-font-size-inv));
  }

  .markedContent {
    display: contents;
  }

  span::selection {
    background: rgba(64, 158, 255, 0.35);
  }
}

.pdf-toolbar {
  position: absolute;
  left: 50%;
  bottom: 24px;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 6px 10px;
  border-radius: 22px;
  background: rgba(48, 49, 51, 0.9);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.24);
  color: #fff;
  font-size: 13px;
  user-select: none;

  &__btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    padding: 0;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: inherit;
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.2s;

    &:hover:not(:disabled) {
      background: rgba(255, 255, 255, 0.16);
    }

    &:disabled {
      opacity: 0.35;
      cursor: not-allowed;
    }

    &.is-active {
      background: rgba(255, 255, 255, 0.24);
    }
  }

  &__split {
    width: 1px;
    height: 16px;
    margin: 0 6px;
    background: rgba(255, 255, 255, 0.24);
  }

  &__pager {
    display: flex;
    align-items: center;
    gap: 4px;

    em {
      font-style: normal;
      color: rgba(255, 255, 255, 0.7);
    }
  }

  &__input {
    width: 40px;
    height: 24px;
    padding: 0 4px;
    border: 1px solid rgba(255, 255, 255, 0.24);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    font-size: 13px;
    text-align: center;
    outline: none;

    &:focus {
      border-color: rgba(255, 255, 255, 0.6);
    }
  }

  &__percent {
    min-width: 42px;
    text-align: center;
    cursor: pointer;
  }
}
</style>
