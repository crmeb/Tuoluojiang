import { inject, provide, reactive, ref } from 'vue'
import type { InjectionKey, Ref } from 'vue'
import { getApplicationsInfoApi, chatPutApplicationsApi, chatPublishApplicationsApi } from '@/api/chatAi'
import { getStorageJson } from '@/utils/storage'
import type { ApplicationDetail, ApplicationForm, KnowledgeItem, McpItem } from '../types'
import { buildApplicationPayload, createApplicationForm, fillApplicationForm } from './useApplicationForm'

interface ApplicationSettingContext {
  appId: string
  detail: Ref<ApplicationDetail>
  form: ApplicationForm
  /** 已绑定知识库详情，提交时映射成 knowledge_ids */
  knowledge: Ref<KnowledgeItem[]>
  /** 已绑定 MCP 服务详情，取自详情接口的 mcp_services，提交时映射成 mcp_service_ids */
  mcpServices: Ref<McpItem[]>
  isSaving: Ref<boolean>
  fetchDetail: () => Promise<void>
  /** 只保存草稿，不改变已发布配置 */
  save: () => Promise<boolean>
  /** 把当前表单发布为线上配置 */
  publish: () => Promise<boolean>
  isPluginEnabled: (type: number) => boolean
  togglePlugin: (type: number, enabled: boolean) => void
}

const SETTING_KEY: InjectionKey<ApplicationSettingContext> = Symbol('applicationSetting')

/**
 * AI 应用设置页上下文
 *
 * 表单被顶部操作区、左侧应用信息、中间模型/插件三块共同读写，用 provide / inject 共享，
 * 避免十几个字段在三层组件之间来回传 props。
 */
export const provideApplicationSetting = (appId: string): ApplicationSettingContext => {
  const detail = ref<ApplicationDetail>({})
  const form = reactive<ApplicationForm>(createApplicationForm())
  const knowledge = ref<KnowledgeItem[]>([])
  const mcpServices = ref<McpItem[]>([])
  const isSaving = ref(false)

  const fetchDetail = async () => {
    const res = await getApplicationsInfoApi(appId)
    const data: ApplicationDetail = res.data && !Array.isArray(res.data) ? res.data : {}
    detail.value = data
    fillApplicationForm(form, data)
    knowledge.value = data.knowledge ? JSON.parse(JSON.stringify(data.knowledge)) : []
    mcpServices.value = data.mcp_services ? JSON.parse(JSON.stringify(data.mcp_services)) : []
    // 历史应用没有图标时回落站点 logo，保证预览与列表页都有图可展示
    if (!form.pic) form.pic = getStorageJson('sitedata', {}).site_logo || ''
  }

  const persist = async (publish: boolean) => {
    if (isSaving.value) return false
    isSaving.value = true
    try {
      const payload = buildApplicationPayload(form, knowledge.value)
      const res = publish
        ? await chatPublishApplicationsApi(appId, payload)
        : await chatPutApplicationsApi(appId, payload)
      if (res.status != 200) return false
      if (!publish) {
        try {
          await fetchDetail()
        } catch (error) {
          // 草稿已经写入，详情刷新失败不把这次保存判成失败
        }
      }
      return true
    } catch (error) {
      return false
    } finally {
      isSaving.value = false
    }
  }

  const save = () => persist(false)
  const publish = () => persist(true)

  const isPluginEnabled = (type: number) => form.source_types.includes(type)

  const togglePlugin = (type: number, enabled: boolean) => {
    if (enabled) {
      if (!form.source_types.includes(type)) form.source_types.push(type)
      return
    }
    form.source_types = form.source_types.filter((item) => item !== type)
  }

  const context: ApplicationSettingContext = {
    appId,
    detail,
    form,
    knowledge,
    mcpServices,
    isSaving,
    fetchDetail,
    save,
    publish,
    isPluginEnabled,
    togglePlugin
  }

  provide(SETTING_KEY, context)
  return context
}

/** 子组件取上下文；页面根组件已 provide，缺失只会出现在误用场景 */
export const useApplicationSetting = () => inject(SETTING_KEY) as ApplicationSettingContext
