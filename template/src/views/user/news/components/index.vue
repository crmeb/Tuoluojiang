<template>
  <div class="station">
    <el-drawer
      :title="formData.title"
      v-model="drawer"
      :direction="direction"
      :modal="true"
      :before-close="handleClose"
      size="550px"
    >
      <formCreate v-if="rules.length > 0" ref="fc" :option="option" :rule="rules" />
      <div class="button from-foot-btn fix btn-shadow">
        <el-button @click="drawer = false">取消</el-button>
        <el-button type="primary" @click="handleConfirm('ruleForm')">确定</el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import formCreate from '@form-create/element-ui'
import request from '@/api/request.ts'
import ueditorFrom from '@/components/form-common/oa-wangeditor'
formCreate.component('ueditorFrom', ueditorFrom)
export default {
  emits: ['isOk', 'changData'],
  name: 'Index',
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
      drawer: false,
      direction: 'rtl',
      option: {
        form: {
          labelWidth: '90px',
          labelSuffix: '：'
        },
        submitBtn: false,
        global: {
          upload: {
            props: {
              onSuccess(res, file) {
                file.url = res.data.src
              }
            }
          },
          frame: {
            props: {
              closeBtn: false,
              okBtn: false,
              onLoad: (e) => {
                e.fApi = this.$refs.fc.fapi
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
      handler(nVal, oVal) {
        this.rules = this.rolesConfig
      },
      deep: true
    }
  },
  methods: {
    handleClose() {
      this.drawer = false
    },
    openBox() {
      this.drawer = true
    },
    // 提交
    handleConfirm() {
      this.$refs.fc.fapi.submit((formDatas, fApi) => {
        if (this.formData.hasOwnProperty('options')) {
          formDatas = Object.assign(formDatas, this.formData.options)
        }
        if (this.isRequest) {
          request[this.formData.method.toLowerCase()](this.formData.action, formDatas).then((res) => {
            this.$emit('isOk')
            this.drawer = false
            // this.handleClose()
          })
        } else {
          this.$emit('changData', formDatas)
        }
      }).catch(() => {}) // 校验不通过时 reject，错误已显示在表单上
    }
  }
}
</script>

<style lang="scss" scoped>
.station :deep(.edui-editor-iframeholder) {
  height: 300px !important;
}
.station :deep(.el-drawer__body) {
  padding: 20px 20px 50px 20px;
}
.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
  text-align: right;
}
:deep(.el-cascader) {
  width: 100%;
}
</style>
