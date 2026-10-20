<!-- @FileDescription: 公共-全局-新增/编辑表单dialog弹窗页面 -->
<template>
  <div class="oa-dialog">
    <el-dialog
      class="form-common-dialog-form-dialog"
      top="8%"
      v-model="show"
      :width="fromData.width || '500px'"
      :show-close="false"
      :append-to-body="true"
      :close-on-click-modal="false"
    >
      <template #header>
        <div class="header">
          <span class="title">{{ fromData.title }} </span>
          <span class="el-icon-close" @click="handleClose"></span>
        </div>
      </template>
      <div v-if="fromData.type == 'slot'">
        <slot></slot>
      </div>

      <div class="invite" v-else >
        <oaForm
          ref="oaForm"
          :fromData="fromData"
          :formConfig="formConfig"
          :formDataInit="formDataInit"
          :formRules="formRules"
          @submit="submitOk"
        ></oaForm>
      </div>
      <template #footer>
        <span class="dialog-footer" v-if="isFooter">
          <el-button @click="handleClose">取消</el-button>
          <el-button type="primary" @click="submit">{{ fromData.btnText || '确定' }}</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import oaForm from '@/components/form-common/oa-form'
import selectMember from '@/components/form-common/select-member'

export default {
  name: 'CrmebOaEntIndex',
  emits: ['submit', 'handleClose'],
  components: {
    oaForm,
    selectMember
  },
  props: {
    // 弹窗样式
    fromData: {
      type: Object,
      default: () => {}
    },
    isFooter: {
      type: Boolean,
      default: true
    },
    // 表单内容
    formConfig: {
      type: Array,
      default: () => []
    },
    // 表单绑定值
    formDataInit: {
      type: Object,
      default: () => {}
    },
    // 表单规则
    formRules: {
      type: Object,
      default: () => {}
    }
  },

  data() {
    return {
      show: false,
      loading: '',
      form: this.formDataInit,
      pickerOptions: {
        disabledDate(time) {
          return time.getTime() < Date.now()
        }
      }
    }
  },

  methods: {
    openBox() {
      this.show = true
    },

    submitOk(form) {
      this.$emit('submit', form, this.fromData.type)
    },

    submit() {
      if (this.fromData.type == 'slot') {
        this.$emit('submit')
      } else {
        this.$refs.oaForm.submit()
      }
    },

    handleClose() {
      if (this.fromData.type !== 'slot') {
        this.$refs.oaForm.closeFn()
      }
      this.$emit('handleClose')

      this.show = false
    }
  }
}
</script>

<style lang="scss" scoped>

.header {
  display: flex;
  align-items: center;
  justify-content: space-between !important;
  font-size: 14px !important;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: #303133;
}
.oa-dialog {
  .title {
    font-size: 14px !important;
    font-family: PingFangSC-Medium, PingFang SC;
    font-weight: 500;
    color: #303133;
  }
  .el-icon-close {
    color: #c0c4cc;
    font-weight: 500;
    font-size: 14px;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
  }
  :deep(.el-dialog) {
    border-radius: 6px;
  }
}

</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.form-common-dialog-form-dialog .el-dialog__footer {
  padding-top: 0;
}
.el-dialog.form-common-dialog-form-dialog .el-dialog__body {
   max-height: 550px;
  overflow-y: auto;
}
</style>
