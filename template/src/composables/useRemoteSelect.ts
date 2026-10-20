import { ref, getCurrentInstance, onUnmounted } from 'vue'
import type { Ref } from 'vue'

interface RemoteSelectOptions<T = any> {
  /** 每次拉取的条数，即下拉框默认展示的条数 */
  limit?: number
  /** 关键词对应的接口字段名，多数列表接口是 name */
  keywordKey?: string
  /** 关键词以外的固定查询条件，每次请求时调用并合并 */
  params?: () => Record<string, any>
  /** 对接口返回的 list 做一次加工（改字段名、过滤等） */
  transform?: (list: any[]) => T[]
  /** 是否在初始化时立即拉一次首屏数据 */
  immediate?: boolean
  /** 远程搜索防抖时长（毫秒） */
  wait?: number
  /** 取选项唯一标识，供 ensureOption 去重，默认取 id */
  getKey?: (item: T) => string | number
}

/**
 * 远程搜索下拉逻辑
 *
 * 适配 el-select 的 `filterable + remote` 用法：默认加载前 N 条，
 * 用户输入后带关键词重新请求。请求做了防抖与竞态兜底（只认最后一次请求的结果），
 * 避免快速输入时先发的响应把后发的覆盖掉。
 *
 * 编辑回显场景用 `ensureOption` 把详情里的选中项补进选项，
 * 否则选中值不在首屏 N 条内时下拉框只会显示裸的 id。
 *
 * @param fetchApi 列表接口，接收查询参数，返回 Promise<{ data: { list, count } }>
 * @param options  见 RemoteSelectOptions
 */
export const useRemoteSelect = <T = any>(
  fetchApi: (params: Record<string, any>) => Promise<any>,
  options: RemoteSelectOptions<T> = {}
) => {
  const {
    limit = 10,
    keywordKey = 'name',
    params,
    transform,
    immediate = true,
    wait = 300,
    getKey = (item: any) => item && item.id
  } = options

  const list = ref([]) as Ref<T[]>
  const isLoading = ref(false)
  const keyword = ref('')

  let timer: ReturnType<typeof setTimeout> | null = null
  // 请求序号：响应回来时序号已被后续请求顶掉的，直接丢弃
  let requestId = 0

  const fetchData = async (kw = '') => {
    keyword.value = kw
    const current = ++requestId
    isLoading.value = true
    try {
      const res = await fetchApi({
        ...(params ? params() : {}),
        [keywordKey]: kw,
        page: 1,
        limit
      })
      if (current !== requestId) return
      const rows = (res && res.data && res.data.list) || []
      list.value = transform ? transform(rows) : rows
    } catch {
      // 错误提示由 request 统一处理，这里只保证选项回到空
      if (current === requestId) list.value = []
    } finally {
      if (current === requestId) isLoading.value = false
    }
  }

  /** 直接挂到 el-select 的 remote-method 上 */
  const remoteSearch = (kw = '') => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      timer = null
      fetchData((kw || '').trim())
    }, wait)
  }

  /** 回到无关键词的首屏数据，下拉框关闭或表单重置时调用；返回本次请求的 Promise */
  const reset = () => {
    if (timer) clearTimeout(timer)
    timer = null
    return fetchData()
  }

  /** 把已选中项并入选项列表（已存在则忽略），供编辑回显 */
  const ensureOption = (option?: T | null) => {
    if (!option) return
    const key = getKey(option)
    if (key === undefined || key === null) return
    if (list.value.some((item) => getKey(item) === key)) return
    list.value = [option, ...list.value]
  }

  if (getCurrentInstance()) {
    onUnmounted(() => {
      if (timer) clearTimeout(timer)
      timer = null
    })
  }

  if (immediate) fetchData()

  return {
    list,
    isLoading,
    keyword,

    fetchData,
    remoteSearch,
    reset,
    ensureOption
  }
}
