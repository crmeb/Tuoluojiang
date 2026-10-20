<template>
  <div>
    <el-dialog
      v-model="dialogFormVisible"
      :width="formData.width"
      :title="formData.title"
      :modal="true"
      :close-on-click-modal="false"
      modal-class="workbench-password-overlay"
      class="person"
      :show-close="true"
      :before-close="handleClose"
    >
      <div class="container">
        <el-form ref="form" :model="tableFrom" :rules="rules" label-width="80px" class="mt20">
          <el-form-item class="info">
            <el-alert class="cr-alert" title="" :closable="false" type="info" :show-icon="true">
              <template #title>
                <p>您账号的密码为初始值,为了账号安全,建议您修改密码</p>
              </template>
            </el-alert>
          </el-form-item>
          <el-form-item label="密码" prop="password">
            <el-input
              v-model="tableFrom.password"
              type="password"
              auto-complete="on"
              placeholder="请输入密码"
            />
          </el-form-item>
          <el-form-item label="确认密码" prop="password_confirm">
            <el-input
              v-model="tableFrom.password_confirm"
              type="password"
              auto-complete="on"
              placeholder="请输入确认密码"
            />
          </el-form-item>
        </el-form>
        <div class="text-right">
          <el-button @click="handleClose()">取消</el-button>
          <el-button type="primary" @click="handleConfirm()">确定</el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import { userEntSavePasswordApi } from '@/api/user.ts'
export default {
  name: 'DialogForm',
  components: {},
  props: {
    formData: {
      type: Object,
      default: () => {
        return {
          title: '修改密码',
          width: '540px'
        }
      }
    }
  },
  computed: {
    ...mapStores(useUserStore)
  },
  data() {
    return {
      dialogFormVisible: false,
      loading: false,
      tableFrom: {
        password: '',
        password_confirm: ''
      },
      rules: {
        password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
        password_confirm: [{ required: true, message: '请输入确认密码', trigger: 'blur' }]
      }
    }
  },
  mounted() {
    this.autoOpenOnInitPassword()
  },
  methods: {
    // 初始密码账号：进入页面后自动弹出修改提示，延迟等首屏渲染完成
    autoOpenOnInitPassword() {
      const userInfo = this.userStore.userInfo
      if (!userInfo || userInfo.is_init !== 1) return
      setTimeout(() => {
        this.handleOpen()
      }, 300)
    },
    handleClose() {
      this.dialogFormVisible = false
      let userInfo = this.userStore.userInfo
      userInfo.is_init = 0
      this.userStore.setUserInfo(userInfo)
    },
    handleOpen() {
      this.dialogFormVisible = true
    },
    // 提交
    handleConfirm() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          this.tableFrom.phone = this.userStore.userInfo.phone
          this.savePassword(this.tableFrom)
        }
      })
    },
    savePassword(data) {
      this.loading = true
      userEntSavePasswordApi(data)
        .then((res) => {
          if (res.status == 200) {
            this.loading = false
            this.handleClose()
          }
        })
        .catch((error) => {
          this.loading = false
        })
    }
  }
}
</script>

<style lang="scss" scoped>
.info {
  :deep(.el-form-item__content) {
    margin-left: 12px !important;
  }
  :deep(.el-alert) {
    padding: 6px 20px;
  }
}
:global(.workbench-password-overlay) {
  background-color: rgba(0, 0, 0, 0.6);
}
.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
  text-align: right;
}
</style>
