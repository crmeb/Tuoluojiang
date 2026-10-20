import { onBeforeUnmount, ref } from 'vue'
import { AiEmbeddedManager } from '@/libs/ai'
import type { PreviewState } from '../types'

/** 挂载参数，字段与 AI 侧的 create 参数对齐 */
export interface AppPreviewMountOptions {
  appId: string
  /**
   * 用哪种身份预览。
   *
   * 外部应用必须用 visitor：它对外只经访客链路开放，用工作台的凭证与端点
   * 预览出来的就不是访客看到的东西了。
   */
  identity: 'workspace' | 'visitor'
  /** identity 为 visitor 时必填，取应用详情的 share_key */
  shareKey?: string
  /** 工作台形态的登录凭证；访客形态由 SDK 自己生成访客身份，不需要它 */
  token?: string
}

/**
 * 调试预览的生命周期
 *
 * 创建是异步的，组件可能在 SDK 返回前就销毁了，所以拿到实例后要再判一次已销毁标记，
 * 否则会留下一个没人回收的 iframe。
 *
 * 挂载参数用取值函数传入：shareKey 要等应用详情回来才有，composable 不该自己去等。
 */
export const useAppPreview = (getOptions: () => AppPreviewMountOptions) => {
  const containerRef = ref<HTMLElement | null>(null)
  let instance: any = null
  let isDestroyed = false
  let updateTimer: number | null = null
  let pendingState: PreviewState | null = null

  const flushUpdate = () => {
    if (updateTimer !== null) window.clearTimeout(updateTimer)
    updateTimer = null
    if (!instance || !pendingState) return
    const state = pendingState
    pendingState = null
    void instance.update(state).catch((error: unknown) => {
      console.error('AI 预览草稿推送失败', error)
    })
  }

  const create = async () => {
    if (!containerRef.value) return
    try {
      const preview = await AiEmbeddedManager.createAdminPreview({
        mount: containerRef.value,
        ...getOptions()
      })
      if (isDestroyed) {
        preview?.destroy()
        return
      }
      instance = preview
      flushUpdate()
    } catch (error) {
      console.error('AI 预览初始化失败', error)
    }
  }

  /**
   * 推一份完整草稿。
   *
   * 预览目标在挂载时已经固定，运行期只推草稿。输入侧做尾部防抖，
   * 不把逐字合并扩散成 SDK 与父子协议里的状态机。
   */
  const update = (state: PreviewState) => {
    pendingState = {
      prologueText: state.prologueText,
      prologueList: [...state.prologueList]
    }
    if (updateTimer !== null) window.clearTimeout(updateTimer)
    updateTimer = window.setTimeout(flushUpdate, 100)
  }

  onBeforeUnmount(() => {
    isDestroyed = true
    if (updateTimer !== null) window.clearTimeout(updateTimer)
    updateTimer = null
    pendingState = null
    instance?.destroy()
    instance = null
  })

  return { containerRef, create, update }
}
