<template>
  <div class="account-login-panel">
    <div class="page-account-top page-code-top">
      <el-tabs v-if="!showQrCode" v-model="activeTab" @tab-click="handleTabChange">
        <el-tab-pane :label="$t('login.passwordlogin')" name="passwordLogin" />
        <el-tab-pane :label="$t('login.smslogin')" name="cmsLogin" />
      </el-tabs>
    </div>

    <el-form
      v-if="!showQrCode"
      ref="loginFormRef"
      :model="loginForm"
      :rules="loginRules"
      autocomplete="on"
      class="login-form"
      label-position="left"
      @keydown.enter.native="onFormEnter"
    >
      <template v-if="activeTab === 'passwordLogin'">
        <el-form-item prop="account">
          <el-input
            v-model="loginForm.account"
            :placeholder="$t('login.phone')"
            autocomplete="on"
            name="username"
            prefix-icon="el-icon-user"
            tabindex="1"
            type="text"
          />
        </el-form-item>
        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            :placeholder="$t('login.password')"
            autocomplete="on"
            name="password"
            prefix-icon="el-icon-lock"
            tabindex="2"
            type="password"
          />
          <span class="pwd" @click="$emit('forget-password')">
            <el-link :underline="false" type="primary">{{ $t('login.forgetpassword') }}</el-link>
          </span>
        </el-form-item>
        <el-form-item class="captcha" prop="captcha">
          <div class="captcha">
            <el-input
              v-model="loginForm.captcha"
              :placeholder="$t('login.code')"
              autocomplete="off"
              prefix-icon="el-icon-message"
              style="width: 199px"
              tabindex="3"
              type="text"
            />
            <div class="imgs" @click="refreshCaptcha">
              <img :src="captchaImg" />
            </div>
          </div>
        </el-form-item>
      </template>

      <template v-else>
        <el-form-item prop="phone">
          <el-input
            v-model="loginForm.phone"
            :placeholder="$t('login.phone')"
            autocomplete="on"
            name="phone"
            prefix-icon="el-icon-mobile-phone"
            tabindex="1"
            type="text"
          />
        </el-form-item>
        <el-form-item prop="verification_code">
          <div class="item-box">
            <div>
              <el-input
                v-model="loginForm.verification_code"
                :placeholder="$t('login.codetitle')"
                autocomplete="off"
                prefix-icon="el-icon-lock"
                tabindex="2"
                type="text"
              />
            </div>
            <el-button :disabled="codeDisabled" class="button" type="primary" @click="sendSmsCode(loginForm.phone)">
              {{ codeText }}
            </el-button>
          </div>
        </el-form-item>
      </template>

      <el-button
        :loading="loading"
        style="width: 100%; margin-bottom: 30px"
        type="primary"
        @click.native.prevent="handleLogin"
      >
        {{ $t('login.logIn') }}
      </el-button>
    </el-form>

    <qr-code-panel v-if="showQrCode" :site-logo="siteLogo" @scan-success="handleScanSuccess" />

    <template v-if="!showQrCode">
      <div class="text">未注册用户请联系管理员创建</div>
      <div v-if="corpId" class="wwlogin" @click="openWorkAuth">
        <img :src="qiweiLogoImg" alt="" style="width: 16px" />企业微信登录
      </div>
    </template>

    <el-button class="btn-login-code" size="small" @click="toggleQrCode">
      {{ showQrCode ? '账号登录' : '扫码登录' }}
    </el-button>
    <img :src="showQrCode ? loginAccountImg : loginCodeImg" alt="" class="image-login-code" @click="toggleQrCode" />

    <!-- 企业微信扫码登录 -->
    <auth-dialog ref="authDialogRef" @workloginOk="handleWorkLoginOk" />
  </div>
</template>

<script>
import { getCurrentInstance, onMounted, reactive, ref } from 'vue'
import { workAuthLogin } from '@/api/user'
import authDialog from '@/components/common/authDialog'
import helper from '@/libs/helper'
import loginAccountImg from '@/assets/images/login-account.png'
import loginCodeImg from '@/assets/images/login-code.png'
import qiweiLogoImg from '@/assets/images/qiweiLogo.png'
import qrCodePanel from './qrCodePanel.vue'
import { useCaptcha } from '../composables/useCaptcha'
import { useLoginSession } from '../composables/useLoginSession'
import { useSmsCode } from '../composables/useSmsCode'
import { DEMO_SUPPORT, DEMO_ACCOUNT, DEMO_PASSWORD } from '@/config/env'

// 演示站构建（VITE_DEMO_SUPPORT）下登录页默认填充演示账号密码，省去访客手动输入。
// 账号密码由 build:demo 命令行注入，正式包对应环境变量为空串，源码不落任何凭据。
const isDemoSupport = DEMO_SUPPORT

export default {
  name: 'AccountLoginPanel',
  components: { authDialog, qrCodePanel },
  props: {
    // 企业微信 corpid，未配置时不展示企微登录入口
    corpId: {
      type: String,
      default: ''
    },
    // 站点 logo，扫码登录二维码中间展示
    siteLogo: {
      type: String,
      default: ''
    }
  },
  setup(props) {
    const { proxy } = getCurrentInstance()

    const { captchaImg, captchaKey, refreshCaptcha } = useCaptcha()
    const { codeDisabled, codeText, sendSmsCode } = useSmsCode()
    const { finishLogin } = useLoginSession()

    const loginFormRef = ref(null)
    const authDialogRef = ref(null)
    const loading = ref(false)
    const activeTab = ref('passwordLogin')
    const showQrCode = ref(false)

    const loginForm = reactive({
      // 演示站默认填充演示账号密码，未注入演示账号时回退为回显上次登录账号
      account: (isDemoSupport && DEMO_ACCOUNT) || localStorage.getItem('lastLoginAccount') || '',
      password: isDemoSupport ? DEMO_PASSWORD : '',
      captcha: '',
      phone: '',
      verification_code: ''
    })

    const validatePhone = (rule, value, callback) => {
      if (!value) {
        return callback(new Error(proxy.$t('customer.placeholder09')))
      }
      if (!helper.phoneReg.test(value)) {
        return callback(new Error(proxy.$t('customer.placeholder71')))
      }
      callback()
    }

    const loginRules = {
      account: [{ required: true, message: proxy.$t('login.phone'), trigger: 'blur' }],
      password: [{ required: true, message: proxy.$t('login.title2'), trigger: 'blur' }],
      captcha: [{ required: true, message: proxy.$t('login.title5'), trigger: 'blur' }],
      verification_code: [{ required: true, message: proxy.$t('login.title6'), trigger: 'blur' }],
      phone: [{ required: true, validator: validatePhone, trigger: 'change' }]
    }

    // 两种登录方式共用一个输入值，切换时同步已填内容
    const handleTabChange = () => {
      if (activeTab.value === 'passwordLogin') {
        loginForm.account = loginForm.phone
      } else {
        loginForm.phone = loginForm.account
      }
    }

    const resetCaptcha = async () => {
      loginForm.captcha = ''
      await refreshCaptcha()
    }

    /**
     * 登录后置流程，loading 需覆盖到菜单、用户信息拉取与跳转完成
     * @param {string} account 用于下次登录回显的账号
     */
    const enterSystem = async (account) => {
      const entered = await finishLogin({ account, corpId: props.corpId })
      // 未能进入系统（如账号无任何菜单权限）时留在登录页，验证码需重新获取
      if (!entered) {
        await resetCaptcha()
      }
      return entered
    }

    /**
     * 表单内回车提交登录；IME 组字（拼音候选确认）期间的回车仅用于选字，
     * isComposing 为 true 或 keyCode 为 229 时必须忽略，否则验证码未输完就触发提交。
     * @param {KeyboardEvent} event
     */
    const onFormEnter = (event) => {
      if (event.isComposing || event.keyCode === 229) return
      handleLogin()
    }

    const handleLogin = async () => {
      if (loading.value || showQrCode.value) return

      const valid = await loginFormRef.value.validate().catch(() => false)
      if (!valid) return

      loading.value = true
      try {
        await proxy.$store.dispatch('user/login', {
          userInfo: { ...loginForm, key: captchaKey.value },
          activeName: activeTab.value
        })
        const account = activeTab.value === 'cmsLogin' ? loginForm.phone : loginForm.account
        await enterSystem(account)
      } catch (error) {
        // 登录失败提示由 request 统一处理，此处只需重置验证码
        await resetCaptcha()
      } finally {
        loading.value = false
      }
    }

    const handleScanSuccess = async () => {
      loading.value = true
      try {
        await enterSystem('')
      } finally {
        loading.value = false
      }
    }

    const toggleQrCode = () => {
      showQrCode.value = !showQrCode.value
    }

    const openWorkAuth = () => {
      authDialogRef.value.open('login')
    }

    // 企业微信授权登录成功，token 由授权弹窗回传
    const handleWorkLoginOk = async (data) => {
      proxy.$store.commit('user/SET_TOKEN', data.token)
      proxy.$store.commit('user/SET_REFRESH_TOKEN', data.refresh_token || '')
      proxy.$store.commit('user/SET_ISLOGIN', true)
      await enterSystem('')
    }

    // 企微授权页回跳时地址里带 code，直接完成登录
    const loginByWorkRedirectCode = async () => {
      const redirect = proxy.$route.query.redirect
      if (!redirect) return
      const code = new URL(redirect, window.location.origin).searchParams.get('code')
      if (!code) return
      try {
        const res = await workAuthLogin({ code })
        await handleWorkLoginOk(res.data)
      } catch (error) {
        // 错误提示由 request 统一处理，这里静默
      }
    }

    onMounted(() => {
      refreshCaptcha()
      loginByWorkRedirectCode()
    })

    return {
      loginAccountImg,
      loginCodeImg,
      qiweiLogoImg,

      loginFormRef,
      authDialogRef,
      loginForm,
      loginRules,
      loading,
      activeTab,
      showQrCode,
      captchaImg,
      codeDisabled,
      codeText,

      refreshCaptcha,
      sendSmsCode,
      handleTabChange,
      onFormEnter,
      handleLogin,
      handleScanSuccess,
      toggleQrCode,
      openWorkAuth,
      handleWorkLoginOk
    }
  }
}
</script>

<style lang="scss" scoped>
.page-account-top {
  padding: 20px 0 !important;
  box-sizing: border-box !important;
}

.page-code-top {
  margin-top: 45px;
}

.login-form {
  position: relative;
  max-width: 100%;
  margin: 0 auto;
  overflow: hidden;
}

.captcha {
  display: flex;
  align-items: flex-start;
}

.imgs {
  margin-left: 26px;

  img {
    height: 36px;
  }
}

.pwd {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
}

.item-box {
  display: flex;

  div {
    position: relative;
    flex: 1;
  }

  .button {
    margin-left: 8px;
  }
}

.text {
  font-size: 13px;
  color: #999;
  margin-bottom: 10px;
}

.wwlogin {
  display: flex;
  justify-content: center;
  cursor: pointer;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 14px;
  color: #333333;

  img {
    margin-right: 5px;
  }
}

::v-deep .el-tabs__nav-wrap::after {
  display: none;
}

::v-deep .el-tabs__item {
  font-size: 18px;
  font-weight: bold;
}

@media (min-width: 768px) {
  .image-login-code {
    width: 69px;
    height: 69px;
    position: absolute;
    right: 0;
    top: 0;
    cursor: pointer;
  }

  .btn-login-code {
    position: absolute;
    right: 75px;
    top: 12px;
    background: rgba(231, 244, 255, 1);
    border: 1px solid rgba(231, 244, 255, 1);
    color: #1890ff;
  }
}
</style>
