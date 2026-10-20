<template>
  <div class="room-content">
    <el-form ref="ruleForm" :model="ruleForm" class="form-box" label-width="140px">
      <el-form-item :label="'头像' + '：'" prop="business_license">
        <el-row class="upload-content">
          <el-col :span="8" class="left">
            <el-upload
              v-if="!ruleForm.avatar"
              :auto-upload="true"
              :headers="myHeaders"
              :http-request="uploadServerLog"
              :show-file-list="false"
              action="##"
              list-type="picture-card"
            >
              <i class="el-icon-plus" />
            </el-upload>

            <div v-else class="el-upload-list el-upload-list--picture-card">
              <img :src="ruleForm.avatar" alt="" class="el-upload-list__item-thumbnail" />
              <span class="el-upload-list__item-actions">
                <span class="el-upload-list__item-delete">
                  <i class="el-icon-delete" @click="handleRemove()"></i>
                </span>
              </span>
            </div>
          </el-col>
          <el-col :span="8" class="right">
            <p>建议图片分辨率80*80且大小不能超过2M,</p>
            <p>支持上传jpg、jpeg、gif、png格式的图片</p></el-col
          >
        </el-row>
      </el-form-item>
      <el-form-item :label="'姓名' + '：'" prop="enterprise_name">
        <el-input v-model="ruleForm.name" placeholder="请填姓名" clearable />
      </el-form-item>
      <el-form-item :label="'用户ID' + '：'" prop="enterprise_name">
        <div class="flex-item">
          <el-input v-model="ruleForm.uid" placeholder="请填写用户ID" disabled />
          <el-button :data-clipboard-text="ruleForm.uid" class="btns text-action copy-data" type="primary" link @click="copy">
            复制
          </el-button>
        </div>
      </el-form-item>
      <el-form-item :label="'手机号码' + '：'" prop="enterprise_name">
        <div v-show="isEditPhone" class="flex-item">
          <el-input v-model="ruleForm.phone" placeholder="请填写手机号码" disabled />
          <el-button class="btns text-action" type="primary" link @click="isEditPhone = false">
            更换手机号
          </el-button>
        </div>
        <div v-show="isEditPhone === false" class="flex-item">
          <el-input v-model="newPhone" placeholder="请填写手机号码" />
          <el-button :disabled="disabled" class="button ml10" type="primary" @click="getCode">{{
            text
          }}</el-button>
        </div>
      </el-form-item>
      <el-form-item v-show="isEditPhone === false" :label="'验证码' + '：'" prop="enterprise_name">
        <el-input v-model="verification_code" placeholder="请输入验证码" type="text" />

        <el-button class="btns text-action" type="primary" link @click="isEditPhone = true">
          取消
        </el-button>
      </el-form-item>
      <el-form-item label="密码：" prop="password">
        <div v-show="isEditPassword" class="flex-item">
          <el-input
            v-model="ruleForm.password"
            placeholder="请输入密码"
            auto-complete="on"
            disabled
            type="password"
          />
          <el-button class="btns text-action" type="primary" link @click="replacePwd">
            更换密码
          </el-button>
        </div>
        <div v-show="isEditPassword === false" class="flex-item" prop="newPassword">
          <el-input
            v-model="newPassword"
            placeholder="请输入密码"
            auto-complete="on"
            show-password
            type="password"
            @blur="regularFn"
          />
        </div>
      </el-form-item>
      <el-form-item v-show="isEditPassword === false" label="确认密码：" prop="enterprise_name">
        <el-input v-model="password_confirm" placeholder="请输入密码" type="password" show-password />
        <el-button class="btns text-action" type="primary" link @click="cancelPwd">
          取消
        </el-button>
      </el-form-item>
      <el-form-item :label="'邮箱' + '：'" prop="enterprise_name">
        <el-input v-model="ruleForm.email" placeholder="请填写邮箱地址" clearable />
      </el-form-item>
      <el-form-item label="企业微信：" prop="enterprise_name">
        <el-input v-model="work_member_id" disabled />

        <template v-if="userInfo.work_member_id != 0">
          <el-button class="btns text-action" type="primary" link @click="bindChangeWork()">更换 </el-button>
          <el-button class="btns text-action" type="primary" link @click="unbindWork()">解绑 </el-button>
        </template>
        <el-button v-else class="btns text-action" type="primary" link @click="bindWork()">绑定 </el-button>
      </el-form-item>
      <el-form-item label="McpKey：" prop="mcpKey">
        <div class="flex-item">
          <el-input v-model="ruleForm.mcpKey" disabled />
          <el-button class="btns text-action" type="primary" link @click="refreshMcpKey">更新</el-button>
          <el-button
            :data-clipboard-text="ruleForm.mcpKey"
            class="btns text-action copy-mcp-data"
            type="primary" link
            @click="copyMcpKey"
          >
            复制
          </el-button>
        </div>
      </el-form-item>
    </el-form>
    <div class="room-footer btn-shadow">
      <el-button @click="cancel">取消</el-button>
      <el-button type="primary" @click="preserve">
        保存
      </el-button>
    </div>
    <!-- 企微登录 -->
    <authDialog ref="authDialog" @workloginOk="getInfo"></authDialog>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public.ts'
import ClipboardJS from 'clipboard'
import { mapActions, mapState } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import { useAppStore } from '@/stores/app.ts'
import { loginRegex } from '@/utils/format.ts'
import sendVerifyCode from '@/mixins/SendVerifyCode.ts'
import { uploader } from '@/utils/uploadCloud.ts'
import { getToken } from '@/utils/auth.ts'
import authDialog from '@/components/common/authDialog'
import {
  userInfo,
  getCmsKeyApi,
  getCmsApi,
  editUserInfo,
  updateMcpKeyApi,
  checkPasswordApi,
  site,
  workUnbinding
} from '@/api/user.ts'
import { getStorageJson } from '@/utils/storage.ts'
import helper from '@/libs/helper.ts'
export default {
  emits: ['isOk'],
  name: 'Index',
  mixins: [sendVerifyCode],
  components: { authDialog },
  data() {
    return {
      ruleForm: {
        name: '',
        uid: '',
        phone: '',
        avatar: '',
        email: '',
        mcpKey: '',
        password: ''
      },
      uPattern: '',
      textVal: '',
      password_confirm: '',
      verification_code: '',
      work_member_id: '未绑定',
      newPhone: '',
      newPassword: '',
      isEditPhone: true,
      isEditPassword: true,
      myHeaders: {
        authorization: 'Bearer ' + getToken()
      },
      sitedata: {},
      dsad: false,
      uploadSize: 2
    }
  },
  computed: {
    ...mapState(useUserStore, ['userInfo'])
  },
  mounted() {
    this.sitedata = getStorageJson('sitedata', {})
    var { val, text } = loginRegex(this.sitedata.password_type ?? 0, Number(this.sitedata.password_length || 6))
    this.textVal = text
    this.uPattern = new RegExp(val)
    this.userInfoList()
    this.getInfo()
    // this.work_member_id = this.userInfo.work_member_id == 0 ? '未绑定' : '已绑定'
  },
  methods: {
    ...mapActions(useUserStore, ['fetchUserInfo', 'setUserInfo']),
    ...mapActions(useAppStore, ['setClickTab']),
    regularFn() {
      if (!this.uPattern.test(this.newPassword)) return this.$message.error(this.textVal)
    },
    bindWork() {
      this.$refs.authDialog.open()
    },
    bindChangeWork() {
      this.$refs.authDialog.open('replace')
    },
    async getInfo() {
      const data = await this.fetchUserInfo()
      this.work_member_id =
        this.userInfo.work_member_id == 0
          ? '未绑定'
          : `${this.userInfo && this.userInfo.work ? this.userInfo.work.name : '--'}@${
              data.enterprise.enterprise_name
            }`
    },

    unbindWork() {
      modalSure('您确定要解绑企业微信吗？').then(() => {
        workUnbinding().then(() => {
          this.getInfo()
        })
      })
    },

    replacePwd() {
      this.newPassword = ''
      this.password_confirm = ''
      this.isEditPassword = false
    },
    copy() {
      this.$nextTick(() => {
        const clipboard = new ClipboardJS('.copy-data')
        clipboard.on('success', () => {
          this.$message.success('复制成功')
          clipboard.destroy()
        })
        this.setClickTab(false)
      })
    },
    copyMcpKey() {
      this.$nextTick(() => {
        const clipboard = new ClipboardJS('.copy-mcp-data')
        clipboard.on('success', () => {
          this.$message.success('复制成功')
          clipboard.destroy()
        })
        this.setClickTab(false)
      })
    },
    refreshMcpKey() {
      modalSure('更新后旧McpKey将失效，确定更新吗？').then(() => {
        updateMcpKeyApi().then((res) => {
          this.ruleForm.mcpKey = res.data.mcpKey
        })
      })
    },
    cancelPwd() {
      this.ruleForm.password = '......'
      this.password_confirm = ''
      this.isEditPassword = true
    },
    defaultPwd(data) {
      if (!data) {
        this.ruleForm.password = '......'
      }
    },
    preserve() {
      // if (!this.isEditPassword) {
      //   if (this.confirmPwd() === false) return
      // }
      // if (!this.isEditPhone) {
      //   if (this.confirmBnt() === false) return
      // }
      // if (!this.password_confirm) {
      //   delete this.ruleForm.password
      // }
      this.ruleForm.password_confirm = this.password_confirm
      this.ruleForm.password = this.newPassword
      this.ruleForm.verification_code = this.verification_code
      editUserInfo(this.ruleForm).then((res) => {
        if (res.status != '200') {
          return false
        }
        let userInfo = JSON.parse(JSON.stringify(this.userInfo))
        userInfo.name = res.data.name
        userInfo.avatar = res.data.avatar
        userInfo.email = res.data.email
        if (res.data.phone) {
          userInfo.phone = res.data.phone
        }
        this.setUserInfo(userInfo)
        this.defaultPwd(this.ruleForm.password)
        if (res.status == '200') {
          this.$emit('isOk')
        }
      })
    },
    cancel() {
      this.isEditPhone = true
      this.isEditPassword = true
      this.userInfoList()
      this.$emit('isOk')
    },
    confirmPwd() {
      if (!this.uPattern.test(this.newPassword)) {
        this.$message.error(this.textVal)
        return false
      }

      if (!this.newPassword) {
        this.$message.error('请输入密码')
        return false
      }
      const data = { password: this.newPassword, password_confirm: this.password_confirm }
      checkPasswordApi(data).then((res) => {
        this.ruleForm.password = this.newPassword
        this.isEditPassword = true
      })
    },
    getCode() {
      this.getCmsKey()
    },
    getCmsKey() {
      getCmsKeyApi().then((res) => {
        const cmsKey = res.data.key
        const exp = helper.phoneReg
        if (!this.newPhone) return this.$message.error('请输入手机号码')
        if (!exp.test(this.newPhone)) {
          return this.$message.error('请输入正确手机号码')
        }
        getCmsApi({
          phone: this.newPhone,
          key: cmsKey,
          types: 2
        })
          .then((res) => {
            this.sendCode()
          })
          .catch(() => {
            this.getCmsKey()
          })
      })
    },
    confirmBnt() {
      const exp = helper.phoneReg
      if (!this.verification_code || !this.newPhone) {
        this.$message.error('请填写手机号或验证码')
        return false
      }
      if (!exp.test(this.newPhone)) {
        this.$message.error('请输入正确手机号码')
        return false
      }
      if (!/^\d{6}$/.test(this.verification_code)) {
        this.$message.error('请输入验证码')
        return false
      }
      this.ruleForm.phone = this.newPhone
      this.isEditPhone = true
    },
    userInfoList() {
      site().then((res) => {
        this.sitedata = res.data
        localStorage.setItem('sitedata', JSON.stringify(this.sitedata))
      })
      userInfo().then((res) => {
        this.ruleForm = res.data

        this.defaultPwd(res.data.password)
      })
    },
    // 上传文件方法
    uploadServerLog(params) {
      const file = params.file
      let options = {
        way: 1,
        relation_type: '',
        relation_id: 0,
        eid: 0
      }
      uploader(file, 1, options).then((res) => {
        // 获取上传文件渲染页面
        if (res.data && res.data.name) {
          this.ruleForm.avatar = res.data.url
          this.userInfo.card.avatar = res.data.url
        }
      })
    },

    handleRemove() {
      this.ruleForm.avatar = ''
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-form-item__label) {
  /*width: 100px !important;*/
}
:deep(.el-form-item__error) {
  margin-left: 40px;
}
.codeItem {
  width: 300px;
  :deep(.el-input) {
    width: auto;
  }
}
:deep(.el-upload--picture-card) {
  width: 70px;
  height: 70px;
  line-height: 80px;
}
.el-upload-list__item-thumbnail {
  border-radius: 4px;
}
.el-upload-list--picture-card {
  position: relative;
  width: 70px;
  height: 70px;
  display: inline-block;
}
.flex-item {
  display: flex;
  align-items: center;
}
.el-input {
  width: 300px;
}
.upload-content {
  .left {
    width: 80px;
  }
  .right {
    width: calc(100% - 130px);
    color: #909399;
    font-size: 13px;
    p {
      margin: 0;
      padding: 0;
      line-height: 1.5;
    }
  }
}
.btns {
  margin-left: 11px;
}
.text-action {
  padding: 0;
  color: #1890ff;
  font-size: 14px;
  font-weight: 500;
  line-height: 32px;
  min-height: 32px;

  &:hover,
  &:focus {
    color: #40a9ff;
  }

  :deep(span) {
    font-weight: 500;
  }
}
.btn-box {
  padding-left: 140px;
  button {
    width: 100px;
    height: 32px;
  }
}
.room-content {
  margin-top: 40px;
  height: calc(100% - 40px);
  padding-bottom: 60px;
  position: relative;
  .room-footer {
    padding: 14px 0;
    text-align: center;
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
  }
}
</style>
