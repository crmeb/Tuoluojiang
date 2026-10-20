import { ref } from 'vue'
import { captchaApi } from '@/api/user'

/**
 * 图形验证码
 * 密码登录使用，登录失败后需要重新获取，避免验证码复用
 */
export const useCaptcha = () => {
  const captchaImg = ref('')
  const captchaKey = ref('')

  const refreshCaptcha = async () => {
    try {
      const { data } = await captchaApi()
      captchaImg.value = data.img
      captchaKey.value = data.key
    } catch (error) {
      // 错误提示由 request 统一处理，这里静默
    }
  }

  return {
    captchaImg,
    captchaKey,

    refreshCaptcha
  }
}
