import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { appConfigApi } from '@/api/public'
import { CUSTOMER_MODULE_KEYS } from '@/constants/customerModules'
import { processResourceData } from '@/utils/resourceUtil'
import { getStorageJson, setStorageJson } from '@/utils/storage'
import type { ApiResult } from '@/types/api'

/** appConfigApi (common/config) 返回的配置，只列出前端直接读取的字段 */
export interface AppConfig {
  customer_switch?: Record<string, unknown>
  global_watermark_status?: string | number
  enterprise_name?: string
  ai_status?: string | number | boolean
  ai_image?: string
  [key: string]: unknown
}

type CustomerModuleKey = (typeof CUSTOMER_MODULE_KEYS)[keyof typeof CUSTOMER_MODULE_KEYS]

const CACHE_KEY = 'webConfigCache'
const CACHE_TTL = 10 * 60 * 1000

// API 返回的 key 映射到 CUSTOMER_MODULE_KEYS
const API_KEY_TO_MODULE_KEY: Record<string, CustomerModuleKey> = {
  lead_module_switch: CUSTOMER_MODULE_KEYS.LEADS,
  customer_module_switch: CUSTOMER_MODULE_KEYS.CUSTOMER,
  opportunity_module_switch: CUSTOMER_MODULE_KEYS.OPPORTUNITY,
  contract_module_switch: CUSTOMER_MODULE_KEYS.CONTRACT,
  order_module_switch: CUSTOMER_MODULE_KEYS.ORDER,
  invoice_module_switch: CUSTOMER_MODULE_KEYS.INVOICE,
  liaison_module_switch: CUSTOMER_MODULE_KEYS.LIAISON
}

// 同步写入 localStorage，兼容现有直接读取 localStorage 的逻辑（水印等）
function persistConfig(data: AppConfig) {
  localStorage.setItem('webConfig', JSON.stringify(data))
  localStorage.setItem('isWebConfig', String(data.global_watermark_status))
}

/**
 * 全局应用配置：统一管理 common/config 的数据，避免多处重复请求；
 * 同时派生客户模块开关。
 */
export const useAppConfigStore = defineStore('appConfig', () => {
  const configData = ref<AppConfig | null>(getStorageJson('webConfig', null))
  // 进行中的请求，用于并发去重
  let fetchTask: Promise<AppConfig> | null = null

  /** 客户相关模块启用信息 map */
  const customerModuleEnableMap = computed(() => {
    const customerSwitch = configData.value?.customer_switch || {}
    return Object.entries(API_KEY_TO_MODULE_KEY).reduce<Record<string, boolean>>((map, [apiKey, mappedKey]) => {
      map[mappedKey] = Boolean(customerSwitch[apiKey])
      return map
    }, {})
  })

  /** 判断客户模块是否启用；未知模块视为关闭 */
  function isCustomerModuleEnabled(moduleKey: string) {
    return customerModuleEnableMap.value[moduleKey] ?? false
  }

  function setConfigData(data: AppConfig) {
    configData.value = processResourceData(data)
  }

  /**
   * 获取应用配置（带缓存 & 请求去重）
   * @param force 是否强制刷新（忽略缓存，且不复用进行中的旧请求）
   */
  function fetchConfig(force = false): Promise<AppConfig> {
    if (!force && configData.value) {
      return Promise.resolve(configData.value)
    }

    const cache = getStorageJson(CACHE_KEY) as { data?: AppConfig; cachedAt?: number } | null
    if (!force && cache?.cachedAt && Date.now() - cache.cachedAt <= CACHE_TTL && cache.data) {
      const data: AppConfig = processResourceData(cache.data)
      setConfigData(data)
      persistConfig(data)
      return Promise.resolve(data)
    }

    if (!force && fetchTask) {
      return fetchTask
    }

    const task: Promise<AppConfig> = appConfigApi({})
      .then((res: ApiResult<AppConfig>) => {
        const data: AppConfig = processResourceData(res.data || {})
        setConfigData(data)
        persistConfig(data)
        setStorageJson(CACHE_KEY, { data, cachedAt: Date.now() })
        return data
      })
      .finally(() => {
        if (fetchTask === task) fetchTask = null
      })

    fetchTask = task
    return task
  }

  function resetConfig() {
    fetchTask = null
    configData.value = null
  }

  return {
    configData,
    customerModuleEnableMap,
    isCustomerModuleEnabled,
    fetchConfig,
    resetConfig
  }
})
