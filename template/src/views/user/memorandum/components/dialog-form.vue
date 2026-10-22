<!-- @FileDescription: 公共-后端表单展示弹窗页面 -->
<template>
  <div>
    <el-dialog
      :title="formData.title"
      :visible.sync="dialogFormVisible"
      :width="formData.width"
      :close-on-click-modal="false"
      custom-class="person"
      :before-close="handleClose"
      :append-to-body="true"
    >
      <formCreate v-if="rules.length > 0 && dialogFormVisible" :key="formKey" ref="fc" :option="option" :rule="rules" />
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="handleClose">{{ $t('public.cancel') }}</el-button>
          <el-button type="primary" @click="handleConfirm('ruleForm')">{{ $t('public.ok') }}</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import formCreate from '@form-create/element-ui'
import request from '@/api/request'
import ueditorFrom from '@/components/form-common/oa-wangeditor'
formCreate.component('ueditorFrom', ueditorFrom)
export default {
  name: 'DialogForm',
  components: { formCreate: formCreate.$form() },
  props: {
    title: {
      type: String,
      default: ''
    },
    rolesConfig: {
      type: Array,
      default: () => {
        return []
      }
    },
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    },
    isRequest: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      dialogFormVisible: false,
      formKey: 0,
      option: {
        form: {
          labelWidth: '100px',
          labelSuffix: '：'
        },
        submitBtn: false,
        global: {
          frame: {
            props: {
              closeBtn: false,
              okBtn: false,
              onLoad: (e) => {
                e.fApi = this.$refs.fc.$f
              }
            }
          }
        }
      }, // 表单配置
      rules: []
    }
  },
  watch: {
    rolesConfig: {
      handler(nVal) {
        this.rules = nVal
        this.formKey += 1
      },
      deep: true
    }
  },
  methods: {
    handleClose() {
      this.dialogFormVisible = false
    },
    open() {
      this.rules = this.rolesConfig
      this.formKey += 1
      this.dialogFormVisible = true
    },
    // 提交
    handleConfirm() {
      this.$refs.fc.$f.submit((formDatas, fApi) => {
        if (this.formData.hasOwnProperty('options')) {
          formDatas = Object.assign(formDatas, this.formData.options)
        }
        if (this.isRequest) {
          request[this.formData.method.toLowerCase()](this.formData.action, formDatas).then((res) => {
            this.$emit('isOk')
            this.handleClose()
          })
        } else {
          this.$emit('changData', formDatas)
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
::v-deep .tips {
  font-size: 13px;
  color: #999;
  margin-bottom: 20px;
  margin-left: 75px;
}
.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
  text-align: right;
}
::v-deep .el-cascader {
  width: 100%;
}
</style>
