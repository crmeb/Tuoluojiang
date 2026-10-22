<!-- 配置app二维码组件 -->
<template>
  <div class="config-app">
    <el-popover placement="bottom" popper-class="user-poppqaer" trigger="click" @show="entSite">
      <div class="popover-user">
        <div class="drop-head">
          <div class="drop-right align-center">
            <div class="qrcode" ref="qrcode"></div>
            <!-- <div class="qrcode-logo">
              <img :src="imgSrc" />
            </div> -->
            <span class="text">扫描配置通用APP</span>
          </div>
        </div>
        <div class="drop-config">
          <span>服务器地址：</span>
          <span>{{ qrValue }}</span>
        </div>
      </div>
      <template #reference>
        <div class="config-info">
          <el-tooltip content="配置App" effect="dark" placement="bottom">
            <i class="iconfont iconshoujisaoma pointer configapp"></i>
          </el-tooltip>
        </div>
      </template>
    </el-popover>
  </div>

</template>

<script>

import { entSiteApi } from '@/api/public'
import { getStorageJson } from '@/utils/storage'
import QRCode from 'qrcodejs2'

export default {
  name: 'configapp',
  components: {
    QRCode
  },
  data() {
    return {
      url: '',
      qrValue: '',
      imgSrc: getStorageJson('enterprise', {}).logo,
      loaded: false,
      loading: false
    }
  },

  methods: {
    // 获取网站配置接口
    entSite() {
      if (this.loaded || this.loading) {
        return
      }
      this.loading = true
      entSiteApi().then((res) => {
        this.loaded = true
        this.qrValue = res.data.address
        setTimeout(() => {
          if (!this.$refs.qrcode) {
            return
          }
          this.$refs.qrcode.innerHTML = ''
          new QRCode(this.$refs.qrcode, {
          text: this.qrValue,
          width: 170,
          height: 170,
          colorDark: '#000000',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.H
        })
        },300)
      }).finally(() => {
        this.loading = false
      })
    },
  }
}
</script>

<style lang="scss" scoped>
.popover-user {
  z-index: 200;
margin: 8px;
}
.configapp {
  margin-right: 18px;
}
.drop-head {
  padding: 0;
  font-size: 13px;
  color: #999;
  .drop-right {
    display: flex;
    flex-direction: column;
    .text {
        font-family: PingFang SC, PingFang SC;
font-weight: 400;
font-size: 13px;
color: #909399;
margin:10px 0
    }
  
  }
  .align-center {
    align-items: center;
    .qrcode {
      margin-bottom: 6px;
      width: 170px;
      height: 170px;
      position: relative;
    }
    .qrcode-logo {
      position: absolute;
      left: 50%;
      top: 50%;
      // transform: translate(-50%, -50%);
      width: 40px;
      height: 40px;
      border-radius: 2px;
      overflow: hidden;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
    }
  }

}

.drop-config {
  font-family: PingFang SC, PingFang SC;
font-weight: 400;
font-size: 13px;
color: #606266;
}

</style>
