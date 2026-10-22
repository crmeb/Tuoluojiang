<template>
  <div class="divBox">
    <el-card class="card-box mt20">
      <div class="title-16">企微设置</div>
      <el-form class="main" label-width="auto">
        <div class="from-item-title mb15">
          <span>企业微信基础配置</span>
        </div>

        <el-form-item label="企业ID：">
          <el-input v-model="form.wechat_work_corpid" size="small" placeholder="请输入企业id" />
          <span class="tips">企业微信ID，前往企业微信后台【我的企业】企业信息中可查看</span>
        </el-form-item>
        <el-form-item label="强制绑定企微：">
          <el-switch
            v-model="form.wechat_work_forced_build"
            :active-value="1"
            :inactive-value="0"
            active-text="开启"
            inactive-text="关闭"
          >
          </el-switch>
          <div class="tips">开启后，客户在登录时，必须绑定企微账号，否则无法登录</div>
        </el-form-item>
        <div class="dashed-divider" />

        <div class="from-item-title mb15">
          <span>企业微信通讯录配置</span>
        </div>
        <el-form-item label="通讯录同步：">
          <el-switch
            v-model="form.wechat_work_user_switch"
            :active-value="1"
            :inactive-value="0"
            active-text="开启"
            inactive-text="关闭"
          >
          </el-switch>
        </el-form-item>
        <template v-if="form.wechat_work_user_switch">
          <el-form-item label="服务器地址：">
            <div class="flex">
              <el-input v-model="form.work_server_url" size="small" disabled placeholder="请输入token" />
              <el-button size="small" type="primary" class="ml10" @click="copyFn(form.work_server_url)">复制</el-button>
            </div>
            <span class="tips">服务器地址</span>
          </el-form-item>
          <el-form-item label="Secret：">
            <el-input v-model="form.wechat_work_address_secret" size="small" placeholder="请输入secret" />
            <span class="tips">通讯录Secret，可前往企业微信【管理工具】->【通讯录同步】查看Secret</span>
          </el-form-item>
          <el-form-item label="Token：">
            <div class="flex">
              <el-input v-model="form.wechat_work_token" size="small" placeholder="请输入token" />
              <el-button size="small" type="primary" class="ml10" @click="copyFn(form.wechat_work_token)"
                >复制</el-button
              >
              <el-button size="small" @click="token()">随机生成</el-button>
            </div>
            <span class="tips">通讯录事件接收服务器Token</span>
          </el-form-item>
          <el-form-item label="EncodingAESKey：">
            <div class="flex">
              <el-input v-model="form.wechat_work_aes_key" size="small" placeholder="请输入encodingAESKey" />
              <el-button size="small" type="primary" class="ml10" @click="copyFn(form.wechat_work_aes_key)"
                >复制</el-button
              >
              <el-button size="small" @click="encodingAESKeyGen">随机生成</el-button>
            </div>
            <span class="tips">企业微信通讯录EncodingAESKey</span>
          </el-form-item>
        </template>

        <div class="dashed-divider" />
        <div class="from-item-title mb15">
          <span>企业微信客户设置</span>
        </div>
        <el-form-item label="客户同步：">
          <el-switch
            v-model="form.wechat_work_client_switch"
            :active-value="1"
            :inactive-value="0"
            active-text="开启"
            inactive-text="关闭"
          >
          </el-switch>
        </el-form-item>
        <template v-if="form.wechat_work_client_switch">
          <el-form-item label="数据同步：">
            <el-radio-group v-model="form.wechat_work_client_radio">
              <el-radio label="clue">线索</el-radio>
              <el-radio label="customer">客户及联系人</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="Secret：">
            <el-input v-model="form.wechat_work_user_secret" size="small" placeholder="请输入Secret" />
            <!-- <span class="tips">企业微信客户secret，可前往【客户与上下游】查看secret</span> -->
          </el-form-item>
        </template>
        <div class="dashed-divider" />
        <div class="from-item-title mb15">
          <span>企业微信自建应用设置</span>
        </div>

        <el-form-item label="应用AgentId：">
          <el-input v-model="form.wechat_work_build_agent_id" size="small" placeholder="请输入应用AgentId" />
          <span class="tips">企业微信自建应用AgentId，可前往【应用管理】进行自建应用</span>
        </el-form-item>
        <el-form-item label="应用Secret：">
          <el-input v-model="form.wechat_work_build_secret" size="small" placeholder="请输入应用Secret" />
          <span class="tips">企业微信自建应用secret，可前往【应用管理】自建应用应用内查看secret</span>
        </el-form-item>
        <div class="dashed-divider" />
        <div class="from-item-title mb15">
          <span>企业微信会话存档</span>
        </div>
        <el-form-item label="企业微信会话存档开关：">
          <el-switch
            v-model="form.wechat_work_session_switch"
            :active-value="1"
            :inactive-value="0"
            active-text="开启"
            inactive-text="关闭"
          >
          </el-switch>
        </el-form-item>
        <template v-if="form.wechat_work_session_switch">
          <el-form-item label="会话存档Secret：">
            <el-input v-model="form.wechat_work_session_secret" size="small" placeholder="请输入会话Secret" />
            <span class="tips">企业微信自建应用secret，可前往【应用管理】自建应用应用内查看secret</span>
          </el-form-item>
          <el-form-item label="版本号：">
            <el-input v-model="form.wechat_work_session_public_key_version" size="small" placeholder="请输入版本号" />
            <span class="tips">企业微信会话存档密钥版本号</span>
          </el-form-item>
          <el-form-item label="密钥：">
            <el-input
              v-model="form.wechat_work_session_private_key"
              type="textarea"
              rows="8"
              size="small"
              placeholder="请输入密钥"
            />
            <span class="tips">企业微信会话存档密钥</span>
          </el-form-item>
          <el-form-item label="公钥：">
            <div class="flex">
              <el-input
                v-model="form.wechat_work_session_public_key"
                size="small"
                placeholder="请输入公钥"
                type="textarea"
                rows="8"
              />
              <el-button
                style="height: 32px"
                size="small"
                type="primary"
                class="ml10"
                @click="copyFn(form.wechat_work_session_public_key_version)"
                >复制</el-button
              >
              <el-button size="small" style="height: 32px" @click="generatePublicKey">生成</el-button>
            </div>
          </el-form-item>
        </template>
      </el-form>
    </el-card>
    <div class="cr-bottom-button btn-shadow">
      <el-button size="small" :loading="loading" type="primary" @click="handleConfirm()">保存</el-button>
    </div>
  </div>
</template>

<script>
import { getWorkConfigApi, saveWorkConfigApi, getWorkRsaApi } from '@/api/setting'
export default {
  data() {
    return {
      name: 'World',
      form: {
        wechat_work_corpid: '',
        wechat_work_user_switch: 1,
        wechat_work_forced_build: 0,
        wechat_work_address_secret: '',
        wechat_work_token: '',
        wechat_work_aes_key: '',
        work_server_url: '',
        wechat_work_client_switch: 1,
        wechat_work_client_radio: 'clue',
        wechat_work_user_secret: '',
        wechat_work_build_agent_id: '',
        wechat_work_build_secret: '',
        wechat_work_session_switch: 1,
        wechat_work_session_secret: '',
        wechat_work_session_private_key: '',
        wechat_work_session_public_key_version: '',
        wechat_work_session_public_key: '',
        rsa_public_key: ''
      },
      loading: false
    }
  },
  mounted() {
    this.getData()
  },
  methods: {
    getData() {
      getWorkConfigApi().then((res) => {
        for (let key in this.form) {
          this.form[key] = res.data[key]
        }
      })
    },
    encodingAESKeyGen() {
      let letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
      let token = ''
      for (let i = 0; i < 43; i++) {
        const j = parseInt(Math.random() * 61 + 1)
        token += letters[j]
      }
      this.form.wechat_work_aes_key = token
    },
    token() {
      let letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
      let token = ''
      for (let i = 0; i < 32; i++) {
        const j = parseInt(Math.random() * 61 + 1)
        token += letters[j]
      }
      this.form.wechat_work_token = token
    },
    handleConfirm() {
      this.loading = true
      saveWorkConfigApi(this.form)
        .then((res) => {
          this.loading = false
        })
        .catch((err) => {
          this.loading = false
        })
    },
    async generatePublicKey() {
      const res = await getWorkRsaApi()
      this.form.wechat_work_session_public_key_version = res.data.rsa_public_key
    },
    copyFn(val) {
      if (!val) return this.$message.error('请输入需要复制的内容')
      clipboard.writeText(val)
      this.$message.success('复制成功')
    }
  }
}
</script>

<style lang="scss" scoped>
.divBox {
  position: relative;
}
.card-box {
  height: calc(100vh - 77px);
  padding-bottom: 80px;
  overflow-y: auto;
}
.main {
  width: 800px;
  margin: 0 auto;

  .tips {
    font-size: 12px;
    color: #909399;
  }

  .from-item-title {
    border-left: 3px solid #1890ff;

    span {
      padding-left: 10px;
      font-weight: bold;
      font-size: 14px;
    }
  }

  .dashed-divider {
    width: 100%;
    border-top: 1px dashed #ccc;
    margin-bottom: 25px;
  }
}
.cr-bottom-button {
  position: absolute;
  left: 0px;
  right: 0;
  bottom: 0;
  width: 100%;
}
::v-deep .el-card__body {
  padding: 20px;
  padding-top: 0;
}
</style>
