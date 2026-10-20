import { onMounted, ref } from 'vue'
import { getCorpConfig, site } from '@/api/user'

/**
 * 登录页站点信息与企业微信配置
 * 站点信息用于底部备案栏与扫码登录的 logo，企业微信配置决定是否展示企微登录入口
 */
export const useSiteConfig = () => {
  const siteData = ref<Record<string, any>>({})
  const corpId = ref('')
  const agentId = ref('')

  const fetchSiteData = async () => {
    try {
      const res = await site()
      siteData.value = res.data || {}
      localStorage.setItem('sitedata', JSON.stringify(siteData.value))
    } catch (error) {
      // 错误提示由 request 统一处理，这里静默
    }
  }

  const fetchCorpConfig = async () => {
    try {
      const res = await getCorpConfig()
      agentId.value = res.data.agentid || ''
      corpId.value = res.data.corpid || ''
      localStorage.setItem('wxConfig', JSON.stringify({ agentid: agentId.value, corpid: corpId.value }))
    } catch (error) {
      // 错误提示由 request 统一处理，这里静默
    }
  }

  onMounted(() => {
    fetchSiteData()
    fetchCorpConfig()
  })

  return {
    siteData,
    corpId,
    agentId
  }
}
