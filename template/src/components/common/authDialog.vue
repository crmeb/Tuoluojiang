<template>
  <el-dialog :visible.sync="visible" width="330px" :append-to-body="true" custom-class="no-header-dialog">
    <div class="box" v-loading="loading">
      <div ref="weChatCodeLogin" style="width: 330px; height: 430px">
        <span class="el-icon-close" v-if="!forced_build" @click="handleClosed"></span>
      </div>
      <div class="btn" v-if="!forced_build" @click="handleClosed">下次绑定</div>
    </div>
  </el-dialog>
</template>

<script>
import * as ww from '@wecom/jssdk'
import { workBinding, workAuthLogin } from '@/api/user'
import { getWorkCorpConfigApi } from '@/api/setting'
import { getStorageJson } from '@/utils/storage'
export default {
  name: 'AuthDialog',
  data() {
    return {
      visible: false,
      wwLogin: null,
      loading: false,
      forced_build: 0
    }
  },
  methods: {
    async open(type) {
      this.loading = true
      this.visible = true
      let sitedata = getStorageJson('sitedata', {})
      this.forced_build = sitedata.forced_build || 0

      const config = (await this.getData()) || getStorageJson('wxConfig', {})
      if (!config.corpid || !config.agentid) {
        this.loading = false
        this.$message.error('企业微信配置获取失败，请稍后重试')
        return
      }
      let that = this

      if (this.wwLogin) {
        this.wwLogin.unmount()
      }

      that.wwLogin = ww.createWWLoginPanel({
        el: '#ww_login',
        params: {
          login_type: 'CorpApp',
          appid: config.corpid,
          agentid: config.agentid,
          redirect_uri: `${location.origin}/admin/user/work`,
          state: 'loginState',
          redirect_type: 'callback',
          panel_size: 'small',
          href: 'impowerBox'
        },
        onCheckWeComLogin({ isWeComLogin }) {},
        onLoginSuccess({ code }) {
          // 绑定企业微信
          if (type === 'login') {
            that.login(code)
          } else if (type === 'replace') {
            that.onworkBind(code, 1)
          } else {
            that.onworkBind(code)
          }
        },
        onLoginFail(err) {
          console.error('企业微信登录失败:', err)
        }
      })
      setTimeout(() => {
        this.$refs.weChatCodeLogin.appendChild(that.wwLogin.el)
        this.loading = false
        const qrcodeHead = this.$refs.weChatCodeLogin.querySelector('.wwLogin_qrcode_head')
        if (qrcodeHead) {
          qrcodeHead.style.padding = '30px 0'
        }
      }, 500)
    },
    async getData() {
      const res = await getWorkCorpConfigApi()
      const config = res?.data || {}
      localStorage.setItem('wxConfig', JSON.stringify(config))
      return config
    },
    // 绑定企业微信管理员
    onworkBind(code, val) {
      if (val === 1) {
        // 更换绑定
        workBinding({ code: code, replace: 1 }).then((res) => {
          this.handleClosed()
        })
      } else {
        workBinding({ code: code }).then((res) => {
          this.handleClosed()
        })
      }
    },
    login(code) {
      workAuthLogin({ code: code }).then((res) => {
        this.$emit('workloginOk', res.data)
        this.visible = false
        if (this.wwLogin) {
          this.wwLogin.unmount()
        }
      })
    },
    handleClosed() {
      this.visible = false
      if (this.wwLogin) {
        this.wwLogin.unmount()
      }
      this.$emit('workloginOk')
    }
  }
}
</script>

<style scoped lang="scss">
::v-deep .el-dialog__header {
  display: none;
}
// ::v-deep .wwLogin_qrcode_head {
//   padding: 30px 0 !important;
//   z-index: 9999 !important;
//   /* 增加更具体的父级选择器 */
//   .box & {
//     padding: 30px 0 !important;
//   }
// }
::v-deep .el-dialog__body {
  width: 330px;
  padding: 0 !important;
  margin: 0;
}

.box {
  width: 100%;
  height: 100%;
  position: relative;
  .el-icon-close {
    position: absolute;
    font-size: 14px;
    top: 10px;
    right: 10px;
    cursor: pointer;
  }
  .btn {
    position: absolute;
    bottom: 30px;
    left: 40%;
    cursor: pointer;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 12px;
    color: #909199;
  }
}
</style>
