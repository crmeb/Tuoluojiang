import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import type { Ref } from 'vue'
import { debounce } from 'lodash-es'

/** 卡片栅格的排版参数，各列表页只在这几个值上有差异 */
export interface GridPageSizeOptions {
  /** 单列最小宽度，等价于原来 grid 里 minmax() 的第一个值 */
  minColumnWidth: number
  /** 卡片行高，需与卡片实际高度一致，否则行数算不准 */
  rowHeight: number
  /** 栅格间距，行列共用 */
  gap: number
  /** 末行露出比例：剩余高度达到行高的这个比例就多排一行，超出部分容器内滚动 */
  overflowRatio?: number
  /** 每页条数上限，避免超大屏一次拉太多 */
  maxPageSize?: number
}

/** 只依赖 useTable 中与分页相关的部分，方便单独传入与测试 */
interface GridPageSizeTable {
  page: Ref<number>
  pageSize: Ref<number>
  fetchData: () => void
}

/**
 * 按容器实际宽高换算卡片列表的每页条数
 *
 * 用与 CSS grid 相同的 track 公式算列数，再把列数下发回 grid，
 * 保证「算出来的列数」就是「实际排布的列数」，每行都能排满。
 * 容器尺寸变化时防抖重算，条数变了交给 useTable 的 watch(pageSize) 重新请求。
 *
 * 使用方需给容器加 `min-height: 0; overflow-y: auto`，
 * 否则内容会把 flex 项撑高，测出的高度不断变大形成正反馈。
 *
 * @param table   useTable 的返回值（用到 page / pageSize / fetchData）
 * @param options 栅格排版参数
 */
export const useGridPageSize = (table: GridPageSizeTable, options: GridPageSizeOptions) => {
  const { minColumnWidth, rowHeight, gap, overflowRatio = 0.5, maxPageSize = 60 } = options

  const containerRef = ref<HTMLElement | null>(null)
  const columns = ref(1)
  const rows = ref(1)

  /** 列数、行高、间距一并交给 CSS，避免样式里再写一套 auto-fill 各算各的 */
  const gridStyle = computed(() => ({
    '--grid-cols': String(columns.value),
    '--grid-row-h': `${rowHeight}px`,
    '--grid-gap': `${gap}px`
  }))

  /** n 条 track 占 n * track + (n - 1) * gap，反解出容器放得下几条 */
  const countTracks = (extent: number, track: number) => Math.max(1, Math.floor((extent + gap) / (track + gap)))

  // 首次测量结果可能与初始 pageSize 相同，那时 watch 不触发，需要主动拉一次
  let isInitialized = false

  const applyPageSize = () => {
    const size = Math.min(columns.value * rows.value, maxPageSize)
    if (size !== table.pageSize.value) {
      // useTable 内部 watch(pageSize) 会重新请求，并处理当前页越界回第 1 页
      table.pageSize.value = size
    } else if (!isInitialized) {
      table.fetchData()
    }
    isInitialized = true
  }

  const measure = () => {
    const el = containerRef.value
    if (!el) return

    const { clientWidth: width, clientHeight: height } = el
    // 被 keep-alive 切走时尺寸为 0，保留上一次结果，避免误算成 1 行 1 列多发请求
    if (width <= 0 || height <= 0) return

    const nextColumns = countTracks(width, minColumnWidth)
    const fitRows = countTracks(height, rowHeight)
    const rest = height - (fitRows * rowHeight + (fitRows - 1) * gap)
    // 末行留白够半张卡就多排一行，露出半张提示「下面还有」，容器内滚动看全
    const nextRows = rest >= rowHeight * overflowRatio ? fitRows + 1 : fitRows

    if (isInitialized && nextColumns === columns.value && nextRows === rows.value) return
    columns.value = nextColumns
    rows.value = nextRows
    applyPageSize()
  }

  // 拖窗口会连续触发，等停手后再改 pageSize，避免请求风暴
  const measureLazy = debounce(measure, 150)

  let observer: ResizeObserver | null = null

  // 翻页后回到顶部，否则停在上一页的滚动位置
  watch(table.page, () => {
    if (containerRef.value) containerRef.value.scrollTop = 0
  })

  onMounted(() => {
    // 首屏同步测一次，走防抖会让列表空等一个防抖周期
    measure()
    if (!containerRef.value) return
    observer = new ResizeObserver(measureLazy)
    observer.observe(containerRef.value)
  })

  onBeforeUnmount(() => {
    measureLazy.cancel()
    observer?.disconnect()
    observer = null
  })

  return { containerRef, columns, rows, gridStyle, measure }
}
