import { ref } from 'vue'
import { assessTargetCateListApi } from '@/api/enterprise'
import { useLoading } from './useLoading'

/**
 * 考核 / 指标分类列表（组件实例内只加载一次）
 *
 * 分类数据变动极少，却被模板库 / 指标库弹窗高频读取（每次打开都要用），
 * 这里在实例内做一次性加载：首次 fetchCate 真正请求，之后直接复用已有列表；
 * 组件销毁重建后重新请求，分类被增删改后可用 force 主动重拉。
 *
 * @param {number}   types                分类类型：0 指标；1 考核
 * @param {Object}   [options]
 * @param {Function} [options.decorate]   对接口分类做业务加工（如插入「我的模板」等前端虚拟分类），
 *                                        入参为接口返回的分类列表，需返回最终展示用的列表
 */
export const useAssessCate = (types, options = {}) => {
  const { decorate = (list) => list } = options

  const cateList = ref([])
  const { isLoading, startLoading, stopLoading } = useLoading()

  // 本实例是否已成功加载过；请求失败不置位，下次调用会重试
  let loaded = false
  // 在途请求，避免弹窗被连续打开时重复发起
  let pending = null

  /**
   * 读取分类，已加载过则直接复用
   * @param {boolean} [force=false] 强制重新拉取
   */
  const fetchCate = (force = false) => {
    if (!force && loaded) return Promise.resolve(cateList.value)
    if (pending) return pending

    startLoading()
    pending = (async () => {
      try {
        const res = await assessTargetCateListApi({ types })
        cateList.value = decorate(res.data || [])
        loaded = true
      } catch (error) {
        // 错误提示由 request 统一处理，这里保证列表可渲染
        cateList.value = decorate([])
      } finally {
        stopLoading()
        pending = null
      }
      return cateList.value
    })()

    return pending
  }

  return {
    cateList,
    isCateLoading: isLoading,

    fetchCate
  }
}
