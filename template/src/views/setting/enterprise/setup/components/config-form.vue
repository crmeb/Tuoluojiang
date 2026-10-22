<template>
  <div class="v-height-flag ml20 mr20" style="min-height: calc(100vh - 200px)">
    <el-form ref="form" :model="form" :rules="formRules" label-width="150px" class="mt20">
      <el-row :gutter="24">
        <el-col
          v-for="item in formItems"
          :key="item.field"
          :span="item.span || 24"
        >
          <el-form-item :label="item.title + '：'" :prop="item.field">
            <!-- 文本输入 -->
            <el-input
              v-if="item.type === 'input'"
              v-model="form[item.field]"
              :placeholder="item.placeholder || `请输入${item.title}`"
              :maxlength="item.maxlength"
              :disabled="item.disabled"
              size="small"
            />
            <!-- 密码输入 -->
            <el-input
              v-else-if="item.type === 'password'"
              v-model="form[item.field]"
              :placeholder="item.placeholder || `请输入${item.title}`"
              :disabled="item.disabled"
              type="password"
              show-password
              size="small"
            />
            <!-- 数字输入 -->
            <el-input-number
              v-else-if="item.type === 'number'"
              v-model="form[item.field]"
              :placeholder="item.placeholder || `请输入${item.title}`"
              :min="item.min"
              :max="item.max"
              :precision="item.precision"
              :disabled="item.disabled"
              size="small"
              style="width: 100%"
            />
            <!-- 文本域 -->
            <el-input
              v-else-if="item.type === 'textarea'"
              v-model="form[item.field]"
              :placeholder="item.placeholder || `请输入${item.title}`"
              type="textarea"
              :rows="item.rows || 3"
              :maxlength="item.maxlength"
              :disabled="item.disabled"
              size="small"
            />
            <!-- 开关 -->
            <el-switch
              v-else-if="item.type === 'switch'"
              v-model="form[item.field]"
              :active-text="item.activeText || '开启'"
              :inactive-text="item.inactiveText || '关闭'"
              :active-value="item.activeValue !== undefined ? item.activeValue : 1"
              :inactive-value="item.inactiveValue !== undefined ? item.inactiveValue : 0"
              :disabled="item.disabled"
            />
            <!-- 单选 -->
            <el-radio-group
              v-else-if="item.type === 'radio'"
              v-model="form[item.field]"
              :disabled="item.disabled"
            >
              <el-radio
                v-for="opt in item.options"
                :key="opt.value"
                :label="opt.value"
              >
                {{ opt.label }}
              </el-radio>
            </el-radio-group>
            <!-- 多选 -->
            <el-checkbox-group
              v-else-if="item.type === 'checkbox'"
              v-model="form[item.field]"
              :disabled="item.disabled"
            >
              <el-checkbox
                v-for="opt in item.options"
                :key="opt.value"
                :label="opt.value"
              >
                {{ opt.label }}
              </el-checkbox>
            </el-checkbox-group>
            <!-- 下拉选择 -->
            <el-select
              v-else-if="item.type === 'select'"
              v-model="form[item.field]"
              :placeholder="item.placeholder || `请选择${item.title}`"
              :disabled="item.disabled"
              :multiple="item.multiple"
              filterable
              size="small"
              clearable
              style="width: 100%"
            >
              <el-option
                v-for="opt in item.options"
                :key="opt.value"
                :label="opt.label"
                :value="opt.value"
              />
            </el-select>
            <!-- 日期选择 -->
            <el-date-picker
              v-else-if="item.type === 'date'"
              v-model="form[item.field]"
              :placeholder="item.placeholder || `请选择${item.title}`"
              :disabled="item.disabled"
              type="date"
              size="small"
              style="width: 100%"
              value-format="yyyy-MM-dd"
            />
            <!-- 时间选择 -->
            <el-time-picker
              v-else-if="item.type === 'time'"
              v-model="form[item.field]"
              :placeholder="item.placeholder || `请选择${item.title}`"
              :disabled="item.disabled"
              size="small"
              style="width: 100%"
            />
            <!-- 图片/附件选择 (frame/upload 类型) -->
            <div v-else-if="item.type === 'frame' || item.type === 'upload'" class="picBox">
              <div v-if="form[item.field]" class="pictrue">
                <img :src="form[item.field]" alt="preview" />
                <i class="el-icon-error" @click="form[item.field] = ''"></i>
              </div>
              <div v-else class="upLoad acea-row row-center-wrapper" @click="openSelectDialog(item)">
                <i class="el-icon-picture-outline"></i>
              </div>
            </div>
            <!-- 默认文本输入 -->
            <el-input
              v-else
              v-model="form[item.field]"
              :placeholder="item.placeholder || `请输入${item.title}`"
              :disabled="item.disabled"
              size="small"
            />
            <!-- 说明文字 (info) -->
            <div v-if="item.info" class="form-item-info">
              {{ item.info }}
            </div>
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item>
        <el-button :loading="loading" size="small" type="primary" @click="submitForm">立即提交</el-button>
      </el-form-item>
    </el-form>

    <!-- 图片选择对话框 -->
    <el-dialog
      title="选择图片"
      :visible.sync="imageDialogVisible"
      width="850px"
      append-to-body
    >
      <upload-picture
        v-if="imageDialogVisible"
        ref="uploadPicture"
        :check-button="true"
        @getImage="handleImageSelected"
      />
    </el-dialog>
  </div>
</template>

<script>
import { cloudFileSetupApi } from '@/api/config'
import uploadPicture from '@/components/uploadPicture/index'
import request from '@/api/request'

export default {
  name: 'ConfigForm',
  components: {
    uploadPicture
  },
  props: {
    // 配置类型：system_config / upload_config
    configType: {
      type: String,
      default: 'system_config'
    },
    // 表单规则数据
    rule: {
      type: Array,
      default: () => []
    },
    // 自定义提交配置
    fromData: {
      type: Object,
      default: () => null
    }
  },
  data() {
    return {
      loading: false,
      imageDialogVisible: false,
      currentField: '',
      form: {},
      formRules: {},
      formItems: []
    }
  },
  watch: {
    rule: {
      handler(val) {
        if (val && val.length) {
          this.initForm(val)
        }
      },
      immediate: true,
      deep: true
    }
  },
  methods: {
    /**
     * 初始化表单
     */
    initForm(rules) {
      const form = {}
      const formItems = []
      const formRules = {}

      rules.forEach((item) => {
        // 获取值
        const value = item.value !== undefined ? item.value : (item.props && item.props.value)

        // 设置表单值
        form[item.field] = value

        // 获取字段类型
        const fieldType = this.getFieldType(item)

        // 获取选项
        const options = this.getOptions(item)

        // 构建表单项配置
        formItems.push({
          field: item.field,
          title: item.title || item.label,
          type: fieldType,
          span: item.col?.span || item.span || 24,
          // 从 item 或 item.props 中获取属性
          placeholder: item.placeholder || (item.props && item.props.placeholder),
          maxlength: item.maxlength || (item.props && item.props.maxlength),
          min: item.min || (item.props && item.props.min),
          max: item.max || (item.props && item.props.max),
          precision: item.precision || (item.props && item.props.precision),
          rows: item.rows || (item.props && item.props.rows) || 3,
          disabled: item.disabled || (item.props && item.props.disabled),
          options: options,
          // 下拉框是否多选
          multiple: item.multiple || (item.props && item.props.multiple) || false,
          // 开关配置
          activeText: item.activeText || (item.props && item.props.activeText) || '开启',
          inactiveText: item.inactiveText || (item.props && item.props.inactiveText) || '关闭',
          activeValue: item.activeValue !== undefined ? item.activeValue : (item.props && item.props.activeValue) !== undefined ? item.props.activeValue : 1,
          inactiveValue: item.inactiveValue !== undefined ? item.inactiveValue : (item.props && item.props.inactiveValue) !== undefined ? item.props.inactiveValue : 0,
          // 说明文字
          info: item.info || (item.props && item.props.info)
        })

        // 构建验证规则
        if (item.validate || item.required) {
          formRules[item.field] = []
          if (item.validate && Array.isArray(item.validate)) {
            formRules[item.field] = item.validate
          }
          if (item.required) {
            formRules[item.field].push({
              required: true,
              message: item.message || `请输入${item.title || item.label}`,
              trigger: ['blur', 'change']
            })
          }
        }
      })

      this.form = form
      this.formItems = formItems
      this.formRules = formRules
    },

    /**
     * 获取字段类型 - 映射 form-create 类型
     */
    getFieldType(item) {
      const typeMap = {
        input: 'input',
        text: 'input',
        password: 'password',
        textarea: 'textarea',
        number: 'number',
        inputNumber: 'number',
        radio: 'radio',
        checkbox: 'checkbox',
        select: 'select',
        switch: 'switch',
        upload: 'upload',
        frame: 'frame',
        datePicker: 'date',
        timePicker: 'time'
      }

      // 优先使用 rule 中的 type
      if (item.type && typeMap[item.type]) {
        return typeMap[item.type]
      }

      // 兼容处理：根据字段名推断
      if (item.field.includes('password')) return 'password'
      if (item.field.includes('status')) return 'switch'
      if (item.field.includes('image') || item.field.includes('logo') || item.field.includes('icon')) return 'frame'

      return 'input'
    },

    /**
     * 获取选项数据
     */
    getOptions(item) {
      if (item.options && Array.isArray(item.options)) {
        return item.options
      }
      if (item.props && item.props.options) {
        return item.props.options
      }
      return []
    },

    /**
     * 打开选择对话框
     */
    openSelectDialog(item) {
      this.currentField = item.field
      this.imageDialogVisible = true
    },

    /**
     * 图片选择回调
     */
    handleImageSelected(data) {
      if (this.currentField) {
        const url = data.att_dir || (Array.isArray(data) && data[0] && data[0].att_dir) || data
        this.$set(this.form, this.currentField, url)
        this.currentField = ''
      }
      this.imageDialogVisible = false
      if (this.$refs.uploadPicture) {
        this.$refs.uploadPicture.checkPicList = []
        this.$refs.uploadPicture.selectItem = []
      }
    },

    /**
     * 提交表单
     */
    submitForm() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          this.saveConfig()
        }
      })
    },

    /**
     * 保存配置
     */
    async saveConfig() {
      this.loading = true
      try {
        if (this.fromData) {
          await request[this.fromData.method.toLowerCase()](this.fromData.action, this.form)
          this.$emit('submit', this.form)
        } else {
          await cloudFileSetupApi(this.configType, this.form)
          await this.getConfig()
        }
      } catch (error) {
        console.error('保存配置失败:', error)
      } finally {
        this.loading = false
      }
    },

    /**
     * 重置表单
     */
    resetForm() {
      this.$refs.form.resetFields()
      if (this.rule && this.rule.length) {
        this.initForm(this.rule)
      }
    },

    /**
     * 获取配置
     */
    async getConfig() {
      try {
        await this.$store.dispatch('appConfig/fetchConfig', true)
      } catch (error) {
        console.error('获取配置失败:', error)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.picBox {
  display: inline-block;
  cursor: pointer;

  .pictrue {
    width: 60px;
    height: 60px;
    border: 1px dotted rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    position: relative;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    i {
      position: absolute;
      top: -6px;
      right: -6px;
      font-size: 18px;
      color: #f56c6c;
      cursor: pointer;
      background: #fff;
      border-radius: 50%;
    }
  }

  .upLoad {
    width: 58px;
    height: 58px;
    line-height: 58px;
    border: 1px dotted rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    background: rgba(0, 0, 0, 0.02);
    text-align: center;

    i {
      font-size: 24px;
      color: #999;
    }
  }
}

.form-item-info {
  font-size: 12px;
  color: #999;
  line-height: 1.5;
  margin-top: 4px;
}
</style>
