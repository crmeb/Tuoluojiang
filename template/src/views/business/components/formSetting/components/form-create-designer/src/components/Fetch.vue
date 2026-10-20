<template>
  <div class="_fc_fetch">
 
    <form-create v-model:api="api" :value="formValue" :rule="rule" :option="option" @change="input" />
  </div>
</template>

<script>
import debounce from '@form-create/utils/lib/debounce'
import is from '@form-create/utils/lib/type'
import { designerForm } from '../config/viewForm'

export default {
  emits: ['update:modelValue'],
  name: 'Fetch',
  components: { formCreate: designerForm.$form() },
  props: {
    modelValue: [Object, String],
    to: String
  },
  computed: {
    // Vue 3 的 v-model 走 modelValue，保留 value 供组件内部沿用
    value() {
      return this.modelValue
    },
    formValue() {
      const val = this.value
      if (!val) return {}
      if (is.String(val)) {
        return {
          action: val
        }
      }
      if (!val._parse && val.parse) {
        return { ...val, _parse: '' + val.parse }
      } else if (is.Function(val._parse)) {
        return { ...val, _parse: '' + val._parse }
      }
      return val
    }
  },
  data() {
    return {
      api: {},
      fetch: {},
      option: {
        form: {
          labelPosition: 'right',
          size: 'small',
          labelWidth: '90px'
        },
        submitBtn: false
      },
      rule: [
        {
          type: 'input',
          field: 'action',
          title: '接口: ',
          validate: [{ required: true, message: '请数据接口' }]
        },
        {
          type: 'select',
          field: 'method',
          title: '请求方式: ',
          value: 'GET',
          options: [
            { label: 'GET', value: 'GET' },
            { label: 'POST', value: 'POST' }
          ]
        },
        {
          type: 'Struct',
          field: 'data',
          title: '附带数据: ',
          value: {},
          props: {
            defaultValue: {}
          }
        },
        {
          type: 'Struct',
          field: 'headers',
          title: 'header信息: ',
          value: {},
          props: {
            defaultValue: {}
          }
        },
        {
          type: 'input',
          field: '_parse',
          title: '解析函数',
          info: '解析接口数据，返回组件所需的数据结构',
          value: 'function (res){\n   return res.data;\n}',
          props: {
            type: 'textarea',
            rows: 8
          },
          validate: [
            {
              validator: (_, v, cb) => {
                if (!v) return cb()
                try {
                  this.parseFn(v)
                } catch (e) {
                  return cb(false)
                }
                cb()
              },
              message: '请输入正确的解析函数'
            }
          ]
        }
      ]
    }
  },
  methods: {
    parseFn(v) {
      // 用 new Function 代替 eval：语义一致（都是执行用户填写的函数字面量），
      // 但不会让 rollup 报 "Use of eval is strongly discouraged" 并放弃该模块的压缩优化。
      return new Function(`return ${v}`)()
    },
    _input() {
      this.api.submit((formData) => {
        formData.to = this.to || 'options'
        if (formData._parse) formData.parse = this.parseFn(formData._parse)
        this.$emit('update:modelValue', formData)
      }).catch(() => {}) // 校验不通过时 reject，错误已显示在表单上
    },
    input: debounce(function () {
      this._input()
    }, 1500)
  },
  mounted() {
    this._input()
  }
}
</script>
<style>
._fc_fetch .el-form-item__label {
  display: inline-flex;
  justify-content: flex-end;
  padding-right: 5px;
}

._fc_fetch {
  background-color: #bfdaf7;
  padding: 10px;
}
</style>
