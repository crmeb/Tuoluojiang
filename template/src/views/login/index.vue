<template>
  <div class="page-account">
    <div class="container" :class="isWideScreen ? 'containerSamll' : 'containerBig'">
      <div class="index_from page-account-container">
        <account-login-panel
          v-if="mode === LOGIN_MODE.LOGIN"
          :corp-id="corpId"
          :site-logo="siteData.site_logo"
          @forget-password="switchMode(LOGIN_MODE.FORGET_PASSWORD)"
        />
        <phone-password-panel
          v-else
          :mode="mode === LOGIN_MODE.REGISTER ? 'register' : 'reset'"
          @back-login="switchMode(LOGIN_MODE.LOGIN)"
        />
      </div>
    </div>

    <div class="foot-bar">
      <p>
        {{ $t('login.contactnumber') }}：{{ siteData.site_tel }} {{ $t('login.addresss') }}：{{ siteData.site_address }}
      </p>
      <a href="https://beian.miit.gov.cn" target="_blank">{{ siteData.site_record_number }}</a>
    </div>
  </div>
</template>

<script>
import { computed, getCurrentInstance, onBeforeUnmount, onMounted, ref } from 'vue'
import accountLoginPanel from './components/accountLoginPanel.vue'
import phonePasswordPanel from './components/phonePasswordPanel.vue'
import { useSiteConfig } from './composables/useSiteConfig'

// 页面模式，与路由 query.loginType 对应
const LOGIN_MODE = {
  LOGIN: 1,
  REGISTER: 2,
  FORGET_PASSWORD: 3
}

// 窄屏断点，低于该宽度时容器改为无卡片样式
const SCREEN_MD = 768

export default {
  name: 'Login',
  components: { accountLoginPanel, phonePasswordPanel },
  setup() {
    const { proxy } = getCurrentInstance()
    const { siteData, corpId } = useSiteConfig()

    const mode = ref(Number(proxy.$route.query.loginType) || LOGIN_MODE.LOGIN)
    const fullWidth = ref(document.body.clientWidth)
    const isWideScreen = computed(() => fullWidth.value > SCREEN_MD)

    const switchMode = (target) => {
      mode.value = target
    }

    const handleResize = () => {
      fullWidth.value = document.body.clientWidth
    }

    onMounted(() => window.addEventListener('resize', handleResize))
    onBeforeUnmount(() => window.removeEventListener('resize', handleResize))

    return {
      LOGIN_MODE,

      siteData,
      corpId,
      mode,
      isWideScreen,

      switchMode
    }
  }
}
</script>

<style lang="scss" scoped>
$screen-md: 768px;

.page-account {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100vh;
  overflow: auto;
}

.container {
  padding: 0 !important;
  border-radius: 12px;
  z-index: 1;
  display: flex;
}

.containerSamll {
  width: 424px;
  background: #fff !important;
  box-shadow: 0px 2px 32px 0px rgba(0, 18, 107, 0.08);
}

.containerBig {
  width: auto !important;
  background: #f7f7f7 !important;
}

.index_from.page-account-container {
  flex: 1;
  width: 384px;
  height: 460px;
  margin: 0 auto;
  padding: 0 40px 32px 40px;
  box-sizing: border-box;
  text-align: center;
  border-radius: 0px 6px 6px 0px;
}

.foot-bar {
  position: fixed;
  left: 0;
  bottom: 17px;
  width: 100%;
  text-align: center;
  font-size: 13px;
  color: #333;

  a {
    color: #333 !important;
    text-decoration: none;
  }
}

@media (min-width: 1920px) {
  .containerSamll {
    margin-top: 150px;
  }
}

@media (max-width: 1920px) and (min-width: 1200px) {
  .containerSamll {
    margin-top: 6%;
  }

  .index_from.page-account-container {
    height: auto;
  }
}

@media (min-width: $screen-md) {
  .page-account {
    background-image: url('../../assets/images/bg.png');
    background-repeat: no-repeat;
    background-position: top center;
    background-size: 100% auto;
  }

  // 面板内的扫码入口按钮以此为定位基准
  .index_from.page-account-container {
    position: relative;
  }
}
</style>
