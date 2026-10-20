<template>
  <div class="v-height-flag" style="min-height: calc(100vh - 190px)">
    <el-form ref="form" :model="form" class="mt20" label-width="150px">
      <el-row>
        <el-col :span="14">
          <el-form-item label="一号通AppId：" prop="appId">
            <el-input v-model="form.yihaotong_appid" placeholder="请输入APPID（从一号通平台申请）"></el-input>
          </el-form-item>
        </el-col>
      </el-row>
      <el-row>
        <el-col :span="14">
          <el-form-item label="一号通AppSecret：" prop="appKey">
            <el-input
              v-model="form.yihaotong_appsecret"
              placeholder="请输入APPKEY（从一号通平台申请）"
            ></el-input>
          </el-form-item>
        </el-col>
      </el-row>
      <el-form-item>
        <div class="platform-tip">
          <i class="el-icon-info"></i>
          <span>
            一号通提供短信、AI接口等增值服务，请先前往一号通平台注册并申请 APPID 与 APPKEY，填入上方输入框后点击「立即提交」完成配置。
          </span>
        </div>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" plain @click="openPlatform">点击登录一号通</el-button>
        <el-button :loading="loading" type="primary" @click="submitForm()">立即提交</el-button>
      </el-form-item>
    </el-form>
    <el-dialog
      title="一号通平台"
      v-model="platformVisible"
      :width="DIALOG_SIZE.XL"
      top="5vh"
      append-to-body
      @close="handlePlatformClose"
    >
      <iframe
        v-if="platformVisible"
        :src="platformUrl"
        style="width: 100%; height: calc(80vh - 100px); border: none"
      ></iframe>
    </el-dialog>
  </div>
</template>

<script>
import { cloudFileSetupApi } from '@/api/config.ts'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'

// 一号通平台地址与来源（postMessage origin 校验用）；带固定 token 参数平台才进入 iframe 集成模式，登录后回传配置
const PLATFORM_URL = 'https://api.crmeb.com?token=AF37D4579721672220B08CA872586943'
const PLATFORM_ORIGIN = 'https://api.crmeb.com'
// 兼容一号通平台同步配置的常见字段命名（appKey 即 AppSecret，平台实际回传 accessKey/secretKey）
const APPID_KEYS = ['accesskey', 'access_key', 'access_key_id', 'yihaotong_appid', 'appid', 'app_id']
const SECRET_KEYS = ['secretkey', 'secret_key', 'yihaotong_appsecret', 'appsecret', 'app_secret', 'appkey']

export default {
  props: {
    fromData: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      loading: false,
      platformVisible: false,
      platformUrl: PLATFORM_URL,
      DIALOG_SIZE,
      form: { yihaotong_appid: '', yihaotong_appsecret: '' }
    }
  },
  watch: {
    fromData: {
      handler(val) {
        this.fromData.map((item) => {
          if (item.field == 'yihaotong_appid') {
            this.form.yihaotong_appid = item.value
          } else if (item.field == 'yihaotong_appsecret') {
            this.form.yihaotong_appsecret = item.value
          }
        })
      },
      deep: true,
      // 组件挂载时立即回显一次，避免热更新/重挂载后 fromData 初始已带值而不触发回显
      immediate: true
    }
  },
  beforeUnmount() {
    window.removeEventListener('message', this.handlePlatformMessage)
  },

  methods: {
    submitForm() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          this.setup('yiht_config', this.form)
        }
      })
    },
    setup(id, data) {
      this.loading = true
      cloudFileSetupApi(id, data)
        .then((res) => {
          this.loading = false
        })
        .catch((err) => {
          this.loading = false
        })
    },
    openPlatform() {
      this.platformVisible = true
      window.addEventListener('message', this.handlePlatformMessage)
    },
    handlePlatformClose() {
      window.removeEventListener('message', this.handlePlatformMessage)
    },
    handlePlatformMessage(event) {
      if (event.origin !== PLATFORM_ORIGIN) return
      let data = event.data
      if (typeof data === 'string') {
        try {
          data = JSON.parse(data)
        } catch (e) {
          return
        }
      }
      if (!data || typeof data !== 'object') return
      const payload = [data.data, data.config, data.payload, data].find(
        (item) => item && typeof item === 'object'
      )
      const config = this.extractYihaotongConfig(payload)
      if (!config) return
      if (this.form.yihaotong_appid || this.form.yihaotong_appsecret) {
        this.$message.info('当前系统已存在一号通配置，本次同步已忽略')
      } else {
        this.form.yihaotong_appid = config.appid
        this.form.yihaotong_appsecret = config.appsecret
        this.setup('yiht_config', this.form)
        this.$message.success('一号通配置同步成功')
      }
      this.platformVisible = false
      this.handlePlatformClose()
    },
    extractYihaotongConfig(data) {
      const pick = (keys) => {
        const matched = Object.keys(data).find((key) => keys.includes(String(key).toLowerCase()))
        return matched ? String(data[matched]) : ''
      }
      const appid = pick(APPID_KEYS)
      const appsecret = pick(SECRET_KEYS)
      if (!appid || !appsecret) return null
      return { appid, appsecret }
    }
  }
}
</script>

<style lang="scss" scoped>
.platform-tip {
  display: flex;
  align-items: flex-start;
  width: 58.33%; // 与上方 el-col :span="14" 输入框右缘对齐
  padding: 9px 12px;
  background: #ecf5ff;
  border: 1px solid #d9ecff;
  border-radius: 4px;

  i {
    margin-right: 6px;
    color: #409eff;
    font-size: 14px;
    line-height: 1.6;
  }

  span {
    color: #909399;
    font-size: 12px;
    line-height: 1.6;
  }
}
</style>
