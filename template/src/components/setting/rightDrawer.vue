<!-- @FileDescription: 系统-新增菜单动态表单 -->
<template>
  <div>
    <el-drawer
      :title="config.title"
      v-model="drawer"
      :direction="direction"
      size="700px"
      :destroy-on-close="true"
      :before-close="handleClose"
    >
      <div class="p14">
        <form-create v-if="FromData" ref="fc" :option="option" :rule="rules" class="mt14" />
      </div>

      <div class="from-foot-btn fix btn-shadow">
        <el-button @click="drawer = false">取消</el-button>
        <el-button type="primary" @click="onSubmit">保存</el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import formCreate from '@form-create/element-ui'
import request from '@/api/request.ts'
export default {
  emits: ['changge'],
  name: 'RightDrawer',
  components: { formCreate: formCreate.$form() },
  props: {
    config: {
      type: Object,
      default: function () {
        return {}
      }
    },
    modal: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      drawer: false,
      direction: 'rtl',
      rules: [],
      option: {
        form: {
          labelWidth: '125px',
          labelSuffix: '：'
        },
        submitBtn: false,
        global: {
          upload: {
            props: {
              onSuccess(res, file) {
                if (res.status === 200) {
                  file.url = res.data.src
                } else {
                  this.$message.error(res.msg)
                }
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
      },
      FromData: null
    }
  },
  mounted() {},
  methods: {
    handelOpen() {
      this.drawer = true
      this.getFormApi()
    },
    handleClose(done) {
      done()
    },
    getFormApi() {
      const data = {}
      if (this.config.type == 1) {
        data.entid = 1
      }
      request[this.config.method || 'get'](this.config.api, data)
        .then((res) => {
          this.FromData = res.data
          this.rules = res.data.rule
        })
        .catch((err) => {
          this.$message.error(err.message || '获取失败')
        })
    },
    onSubmit() {
      this.$refs.fc.fapi.submit((formData) => {
        request[this.FromData.method.toLowerCase()](this.FromData.action.slice(7), formData).then(() => {
          this.$emit('changge')
          this.drawer = false
        })
      }).catch(() => {}) // 校验不通过时 reject，错误已显示在表单上
    }
  }
}
</script>

<style scoped lang="scss">
.p14 {
  padding-right: 14px;
}

:deep(._fc-frame ._fc-upload-btn svg) {
  display: none;
}

:deep(._fc-frame ._fc-upload-btn .el-icon::before) {
  content: '\e77b';
  font-family: element-icons;
  font-size: 13px;
}
</style>
