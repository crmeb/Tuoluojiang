<!-- 订单-添加备注弹窗组件 -->
<template>
  <el-dialog
    :title="config.title"
    v-model="dialogVisible"
    :width="config.width"
    :append-to-body="true"
    :before-close="handleClose"
  >
    <el-form ref="form" :model="rules" :rules="rule" class="mt15">
      <el-form-item prop="remarks">
        <el-input
          type="textarea"
          maxlength="255"
          show-word-limit
          :rows="4"
          v-model="rules.remarks"
          placeholder="请填写备注信息"
        />
      </el-form-item>
    </el-form>
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button :loading="loading" type="primary" @click="handleConfirm">确定</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { clientInvoiceMarkApi, clientMarkApi, clientRemindMarkApi } from '@/api/client.ts'

export default {
  emits: ['isMark'],
  name: 'MarkDialog',
  props: {
    config: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      rules: {
        remarks: ''
      },
      rule: {
        remarks: [{ required: true, message: '请填写备注信息', trigger: 'blur' }]
      },
      dialogVisible: false,
      loading: false
    }
  },
  watch: {
    config: {
      handler(nVal) {
        this.rules.remarks = nVal.mark
      },
      deep: true
    }
  },
  methods: {
    handleOpen() {
      this.dialogVisible = true
    },
    handleClose() {
      this.dialogVisible = false
      this.$refs.form.resetFields()
      this.remarks = ''
    },
    handleConfirm() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          if (this.config.type === 1) {
            this.clientMark(this.config.id, { mark: this.rules.remarks })
          } else if (this.config.type === 2) {
            this.invoiceMark(this.config.id, { mark: this.rules.remarks })
          } else if (this.config.type === 3) {
            this.clientRemindMark(this.config.id, { mark: this.rules.remarks })
          }
        }
      })
    },
    // 订单列表--付款记录--填写备注
    clientMark(id, data) {
      this.loading = true
      clientMarkApi(id, data)
        .then((res) => {
          this.$emit('isMark')
          this.handleClose()
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    },
    // 付款提醒
    clientRemindMark(id, data) {
      this.loading = true
      clientRemindMarkApi(id, data)
        .then((res) => {
          this.$emit('isMark')
          this.handleClose()
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    },
    // 发票申请列表填写备注
    invoiceMark(id, data) {
      this.loading = true
      clientInvoiceMarkApi(id, data)
        .then((res) => {
          this.$emit('isMark')
          this.handleClose()
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    }
  }
}
</script>

<style scoped lang="scss">
:deep(.el-date-editor) {
  width: 100%;
}
:deep(.el-textarea__inner) {
  resize: none;
}
:deep(.el-input-number) {
  width: 100%;
}
:deep(.el-select) {
  width: 100%;
}
:deep(.el-form-item:last-of-type) {
  margin-bottom: 0;
}
.dialog-footer {
  padding-top: 20px;
  // border-top: 1px solid #e6ebf5;
}
</style>
