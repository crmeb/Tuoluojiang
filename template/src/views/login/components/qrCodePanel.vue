<template>
  <div class="code-content">
    <div v-if="!scanned">
      <div v-loading="qrLoading" class="code-header">
        <div ref="qrCodeRef" class="QRCode_header"></div>
        <img :src="siteLogo || codeLogoImg" alt="" class="image-code-logo" />
        <div v-if="expired" class="code-model">
          <div class="text">二维码失效</div>
          <el-button round type="primary" @click="refreshQrCode">刷新二维码</el-button>
        </div>
      </div>
      <div class="code-tip">请使用陀螺匠App扫码</div>
    </div>

    <div v-else class="success-code">
      <i class="el-icon-circle-check icon"></i>
      <div class="success-text">扫码成功</div>
      <div class="success-text1 code-tip">请在手机上根据提示确认登录</div>
    </div>
  </div>
</template>

<script>
import { getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import QRCode from 'qrcodejs2'
import { userScanKeyApi } from '@/api/user.ts'
import { useUserStore } from '@/stores/user.ts'
import codeLogoImg from '@/assets/images/code-logo.png'

// 扫码状态轮询间隔
const POLL_INTERVAL = 2000
// 二维码尺寸
const QRCODE_SIZE = 190

export default {
  name: 'QrCodePanel',
  props: {
    // 站点 logo，未配置时回落到默认图
    siteLogo: {
      type: String,
      default: ''
    }
  },
  setup(props, { emit }) {
    const { proxy } = getCurrentInstance()
    const userStore = useUserStore()

    const qrCodeRef = ref(null)
    const qrLoading = ref(false)
    const expired = ref(false)
    const scanned = ref(false)

    let timer = null
    let qrCodeKey = ''

    const stopPolling = () => {
      if (timer) {
        clearInterval(timer)
        timer = null
      }
    }

    // 轮询扫码状态：-1 二维码失效，1 已扫码待确认，无 status 表示确认登录且 token 已写入
    const checkScanStatus = async () => {
      try {
        const data = await userStore.scanLogin({ key: qrCodeKey })
        if (data.status === -1) {
          stopPolling()
          expired.value = true
          scanned.value = false
          return
        }
        if (data.status === 1) {
          scanned.value = true
          return
        }
        stopPolling()
        emit('scan-success')
      } catch (error) {
        stopPolling()
        proxy.$message.error(error.message)
      }
    }

    const refreshQrCode = async () => {
      stopPolling()
      qrLoading.value = true
      scanned.value = false
      try {
        const res = await userScanKeyApi()
        qrCodeKey = res.data.key
        await nextTick()
        if (!qrCodeRef.value) return
        qrCodeRef.value.innerHTML = ''
        const qrCode = new QRCode(qrCodeRef.value, {
          text: qrCodeKey,
          width: QRCODE_SIZE,
          height: QRCODE_SIZE,
          colorDark: '#000000',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M
        })
        qrCode._el.title = ''
        expired.value = false
        timer = setInterval(checkScanStatus, POLL_INTERVAL)
      } catch (error) {
        // 错误提示由 request 统一处理，这里静默
      } finally {
        qrLoading.value = false
      }
    }

    onMounted(refreshQrCode)
    onBeforeUnmount(stopPolling)

    return {
      codeLogoImg,

      qrCodeRef,
      qrLoading,
      expired,
      scanned,

      refreshQrCode
    }
  }
}
</script>

<style lang="scss" scoped>
.code-content {
  width: 100%;
  height: auto;

  .code-header {
    width: 220px;
    height: 220px;
    background: linear-gradient(#cbcbcb, #cbcbcb) left top, linear-gradient(#cbcbcb, #cbcbcb) left top,
      linear-gradient(#cbcbcb, #cbcbcb) right top, linear-gradient(#cbcbcb, #cbcbcb) right top,
      linear-gradient(#cbcbcb, #cbcbcb) right bottom, linear-gradient(#cbcbcb, #cbcbcb) right bottom,
      linear-gradient(#cbcbcb, #cbcbcb) left bottom, linear-gradient(#cbcbcb, #cbcbcb) left bottom;
    background-repeat: no-repeat;
    background-size: 2px 20px, 20px 2px;
    margin: 0 auto 20px auto;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;

    .QRCode_header {
      width: 190px;
      height: 190px;
    }

    .image-code-logo {
      width: 50px;
      height: 50px;
      position: absolute;
      border-radius: 8px;
      border: 3px solid #fff;
    }

    .code-model {
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      right: 0;
      background-color: rgba(0, 0, 0, 0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;

      .text {
        color: #fff;
        font-size: 15px;
      }
    }
  }

  .code-tip {
    margin-bottom: 14px;
  }

  .success-code {
    margin-top: 40px;

    .icon {
      font-size: 40px;
      color: #1890ff;
    }

    > div {
      padding-top: 15px;
      font-size: 15px;
    }

    .success-text {
      color: #909399;
    }

    .success-text1 {
      color: #303133;
      font-weight: 800;
    }
  }
}
</style>
