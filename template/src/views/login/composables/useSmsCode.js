import { getCurrentInstance, onBeforeUnmount, ref, watch } from 'vue'
import { getCmsApi, getCmsKeyApi } from '@/api/user'
import helper from '@/libs/helper'

// 验证码重发倒计时（秒）
const COUNTDOWN_SECONDS = 60

/**
 * 短信验证码发送与倒计时
 * 短信登录、账号注册、找回密码三处共用
 */
export const useSmsCode = () => {
  const { proxy } = getCurrentInstance()

  const codeDisabled = ref(false)
  const codeText = ref(proxy.$t('login.code'))
  let timer = null

  const clearTimer = () => {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  const startCountdown = () => {
    let seconds = COUNTDOWN_SECONDS
    codeDisabled.value = true
    codeText.value = `${proxy.$t('login.surplus')} ${seconds}s`
    clearTimer()
    timer = setInterval(() => {
      seconds -= 1
      if (seconds <= 0) {
        clearTimer()
        codeDisabled.value = false
        codeText.value = proxy.$t('login.Recapture')
        return
      }
      codeText.value = `${proxy.$t('login.surplus')} ${seconds}s`
    }, 1000)
  }

  /**
   * 发送短信验证码
   * @param {string} phone 手机号
   * @param {number} types 短信类型，与后端约定，0 为通用
   */
  const sendSmsCode = async (phone, types = 0) => {
    if (codeDisabled.value) return
    if (!phone) {
      proxy.$message.error(proxy.$t('login.title1'))
      return
    }
    if (!helper.phoneReg.test(phone)) {
      proxy.$message.error(proxy.$t('login.title9'))
      return
    }

    try {
      const keyRes = await getCmsKeyApi()
      const res = await getCmsApi({ phone, key: keyRes.data.key, types, from: 1 })
      if (res.status === 200) {
        startCountdown()
      }
    } catch (error) {
      // 错误提示由 request 统一处理，这里静默
    }
  }

  // 切换语言时同步按钮文案，倒计时中不覆盖
  watch(
    () => proxy.$store.getters.lang,
    () => {
      if (!codeDisabled.value) {
        codeText.value = proxy.$t('login.code')
      }
    }
  )

  onBeforeUnmount(clearTimer)

  return {
    codeDisabled,
    codeText,

    sendSmsCode
  }
}
