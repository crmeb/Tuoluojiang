<template>
  <el-dialog
    :title="title"
    :visible.sync="show"
    :width="DIALOG_SIZE.LG"
    :close-on-click-modal="false"
    append-to-body
    custom-class="model-dialog"
    @closed="handleClosed"
  >
    <el-form ref="formRef" v-loading="isDetailLoading" :model="form" :rules="rules" label-position="top">
      <!-- 模型类型：决定下方走「服务商」还是「自定义请求地址」 -->
      <el-form-item label="模型类型" prop="config_type" class="config-type-item">
        <el-radio-group v-model="form.config_type" :disabled="isEdit" @change="handleConfigTypeChange">
          <el-radio
            v-for="item in configTypeOptions"
            :key="item.value"
            :label="item.value"
            :disabled="item.disabled"
          >
            {{ item.label }}
          </el-radio>
        </el-radio-group>
        <div v-if="isYihaotongLocked" class="yihaotong-tip">
          当前一号通不可用，点击
          <span class="link tip-link" @click="goYihaotongConfig">快速配置</span>
        </div>
      </el-form-item>

      <div class="model-form-body" :class="{ 'is-locked': isYihaotongLocked }">
      <el-form-item v-if="isPreset" label="服务商" prop="provider">
        <el-select
          v-model="form.provider"
          size="small"
          filterable
          placeholder="请选择服务商"
          class="full-width"
          :disabled="isYihaotongLocked"
          @change="handleDriverChange"
        >
          <el-option v-for="item in presetOptions" :key="item.value" :label="item.label" :value="item.value" />
        </el-select>
      </el-form-item>

      <el-form-item v-else-if="isCustom" prop="base_url">
        <template #label>
          <div class="label-row">
            <span class="label-text">
              自定义请求地址
              <el-tooltip
                effect="dark"
                placement="top"
                popper-class="base-url-tip"
                content="请填写兼容 OpenAI API 的服务端点地址，不要以斜杠结尾。/chat/completions 将会被补充到你填写的地址末尾。"
              >
                <span class="label-tip iconfont iconshuoming"></span>
              </el-tooltip>
            </span>
            <span class="label-switch">
              完整URL
              <el-switch
                v-model="form.is_full_url"
                :active-value="1"
                :inactive-value="0"
                size="mini"
                class="mini-switch"
                :disabled="isYihaotongLocked"
                @change="handleFullUrlChange"
              />
            </span>
          </div>
        </template>
        <el-input
          v-model="form.base_url"
          size="small"
          autocomplete="off"
          placeholder="请输入请求地址"
          :disabled="isYihaotongLocked"
        />
      </el-form-item>

      <el-form-item v-if="!isYihaotong" label="API密钥" prop="api_key" class="api-key-item">
        <el-input
          v-model="form.api_key"
          :type="isApiKeyVisible ? 'text' : 'password'"
          size="small"
          autocomplete="new-password"
          placeholder="请输入API密钥"
          :disabled="isYihaotongLocked"
          @blur="handleApiKeyBlur"
        >
          <template #suffix>
            <span class="input-suffix">
              <i
                class="iconfont pointer eye-icon"
                :class="isApiKeyVisible ? 'iconchakan' : 'iconyincang'"
                @click="isApiKeyVisible = !isApiKeyVisible"
              />
              <a v-if="apiKeyDocUrl" :href="apiKeyDocUrl" target="_blank" rel="noopener noreferrer" class="link">
                获取API密钥
              </a>
            </span>
          </template>
        </el-input>
      </el-form-item>

      <!-- 模型类别：仅前端使用，不参与提交，只决定下方「模型」下拉展示聊天还是向量选项 -->
      <el-form-item label="模型类别">
        <el-radio-group
          v-model="modelCategory"
          :disabled="isYihaotongLocked"
          @change="handleModelCategoryChange"
        >
          <el-radio :label="MODEL_CATEGORY.CHAT">对话模型</el-radio>
          <el-radio :label="MODEL_CATEGORY.EMBEDDING">向量模型</el-radio>
        </el-radio-group>
      </el-form-item>

      <!-- 模型：统一绑 model_id，按类别切换选项；支持下拉选择与直接输入未在列表中的模型名 -->
      <el-form-item label="模型" prop="model_id">
        <div class="field-with-action">
          <el-select
            v-model="form.model_id"
            size="small"
            filterable
            allow-create
            default-first-option
            :loading="isModelLoading"
            :disabled="isYihaotongLocked"
            placeholder="请选择或输入模型"
            class="full-width"
            @change="handleModelChange"
          >
            <el-option v-for="item in modelOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
          <span
            class="link field-action"
            :class="{ 'is-loading': isModelLoading, 'is-disabled': isYihaotongLocked }"
            @click="handleFetchModels"
          >
            <i v-if="isModelLoading" class="el-icon-loading action-loading" />
            获取模型
          </span>
        </div>
      </el-form-item>

      <el-form-item label="模型名称" prop="name">
        <el-input
          v-model="form.name"
          size="small"
          maxlength="32"
          show-word-limit
          placeholder="请输入模型名称"
          :disabled="isYihaotongLocked"
        />
      </el-form-item>

      <!-- 高级设置：仅对话模型；默认可折叠，未填写时由后端使用调用默认值 -->
      <div v-if="!isEmbeddingCategory" class="advanced-block">
        <div
          class="advanced-header"
          :class="{ 'is-disabled': isYihaotongLocked }"
          @click="!isYihaotongLocked && (advancedExpanded = !advancedExpanded)"
        >
          <i class="el-icon-caret-right advanced-toggle" :class="{ 'is-expanded': advancedExpanded }" />
          <span class="advanced-title">高级设置</span>
        </div>

        <el-collapse-transition>
          <div v-show="advancedExpanded" class="advanced-body">
            <el-form-item>
              <template #label>
                <span class="label-nowrap">上下文窗口（Token）</span>
              </template>
              <div class="token-field">
                <div v-for="row in tokenRows" :key="row.field" class="token-row">
                  <el-input
                    v-model="form.json[row.field]"
                    class="advanced-input token-input"
                    size="small"
                    :placeholder="row.placeholder"
                    @input="handleNullableNumber(row.field, $event)"
                  >
                    <template #prepend>{{ row.label }}</template>
                  </el-input>
                  <div class="shortcut-inline">
                    <span
                      v-for="item in row.shortcuts"
                      :key="item.label"
                      class="shortcut"
                      :class="{ active: Number(form.json[row.field]) === item.value }"
                      @click="form.json[row.field] = item.value"
                    >
                      {{ item.label }}
                    </span>
                  </div>
                </div>
              </div>
            </el-form-item>

            <el-form-item>
              <template #label>
                <span class="label-nowrap">工具调用轮数</span>
              </template>
              <el-input
                v-model="form.json.max_tool_rounds"
                class="advanced-input"
                size="small"
                :placeholder="`留空则默认 ${ADVANCED_DEFAULTS.max_tool_rounds}`"
                @input="handleNullableNumber('max_tool_rounds', $event)"
              />
            </el-form-item>

            <el-form-item>
              <template #label>
                <span class="label-text label-nowrap">
                  思考模式
                  <el-tooltip
                    effect="dark"
                    placement="top"
                    content="跟随默认时不强制开关深度思考；开启/关闭会按供应商能力注入 thinking 参数"
                  >
                    <span class="label-tip iconfont iconshuoming"></span>
                  </el-tooltip>
                </span>
              </template>
              <el-radio-group v-model="form.enable_thinking" class="thinking-radios">
                <el-radio v-for="item in THINKING_MODE_OPTIONS" :key="item.value" :label="item.value">
                  {{ item.label }}
                </el-radio>
              </el-radio-group>
            </el-form-item>

            <el-form-item>
              <template #label>
                <span class="label-text label-nowrap">
                  采样参数
                  <el-tooltip
                    effect="dark"
                    placement="top"
                    content="留空表示使用供应商最佳默认值；填写后会在模型调用时实装"
                  >
                    <span class="label-tip iconfont iconshuoming"></span>
                  </el-tooltip>
                </span>
              </template>
              <div class="sample-fields">
                <el-input
                  v-for="row in sampleRows"
                  :key="row.field"
                  v-model="form.json[row.field]"
                  class="advanced-input"
                  size="small"
                  :placeholder="row.placeholder"
                  @input="handleNullableNumber(row.field, $event)"
                >
                  <template #prepend>{{ row.label }}</template>
                </el-input>
              </div>
            </el-form-item>
          </div>
        </el-collapse-transition>
      </div>
      </div>
    </el-form>

    <!-- 连通性测试未通过时在表单与按钮之间内联展示原因，不弹窗、不关闭，便于对照上方字段修改 -->
    <div v-if="testError" class="test-error">
      <i class="el-icon-warning test-error-icon" />
      <span class="test-error-text">{{ testError }}</span>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <span class="footer-tip">
          <i class="el-icon-info footer-tip-icon" />
          连通性测试会发起一次真实请求，会消耗少量模型Token
        </span>
        <span>
          <el-button size="small" :disabled="isSubmitting" @click="handleClose">取消</el-button>
          <el-button
            type="primary"
            size="small"
            :loading="isSubmitting"
            :disabled="isYihaotongLocked"
            @click="handleSubmit"
          >
            {{ submitText }}
          </el-button>
        </span>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { computed, ref, getCurrentInstance } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize'
import { roterPre } from '@/settings'
import {
  CONFIG_TYPE,
  MODEL_CATEGORY,
  THINKING_MODE_OPTIONS,
  INPUT_TOKEN_SHORTCUTS,
  OUTPUT_TOKEN_SHORTCUTS,
  ADVANCED_DEFAULTS
} from '../utils/model'
import { useModelForm } from '../composables/useModelForm'

export default {
  name: 'ModelDialog',
  props: {
    // 供应商选项，元素形如 { label, value, pic, config_type }
    optionList: {
      type: Array,
      default: () => []
    }
  },
  setup(props, { emit }) {
    const { proxy } = getCurrentInstance()

    // 提交分两步：先连通性测试，通过后再走保存接口，按钮文案随阶段变化
    const SUBMIT_STAGE = {
      IDLE: '',
      TESTING: 'testing',
      SUBMITTING: 'submitting'
    }

    const show = ref(false)
    const submitStage = ref(SUBMIT_STAGE.IDLE)
    const isSubmitting = computed(() => submitStage.value !== SUBMIT_STAGE.IDLE)
    const submitText = computed(() => {
      if (submitStage.value === SUBMIT_STAGE.TESTING) return '连通性测试中'
      if (submitStage.value === SUBMIT_STAGE.SUBMITTING) return '提交中'
      return '确定'
    })
    // 连通性测试未通过的原因，展示在表单与底部按钮之间
    const testError = ref('')
    const isApiKeyVisible = ref(false)
    // 高级配置默认收起；编辑时若已有自定义值再展开
    const advancedExpanded = ref(false)

    const modelForm = useModelForm({ getProviderOptions: () => props.optionList })

    const clearValidate = (fields) => {
      proxy.$refs.formRef && proxy.$refs.formRef.clearValidate(fields)
    }

    // 切换模型类型 / 服务商会程序性清空密钥、地址与模型，而 el-input / el-select 的值变更
    // 同样会被 el-form-item 当成用户输入触发校验，需在清空后再清一次校验态，
    // 否则切换瞬间会闪出「api_key is required」「请选择或输入模型」等必填红字
    const LINKAGE_FIELDS = ['provider', 'base_url', 'api_key', 'model_id']

    const withClearValidate = (handler) => (...args) => {
      handler(...args)
      proxy.$nextTick(() => clearValidate(LINKAGE_FIELDS))
    }

    const handleConfigTypeChange = withClearValidate(modelForm.handleConfigTypeChange)
    const handleDriverChange = withClearValidate(modelForm.handleDriverChange)
    // 切换模型类别会清空 model_id，同样需要清掉联动校验态
    const handleModelCategoryChange = withClearValidate(modelForm.handleModelCategoryChange)

    // 「完整URL」开关渲染在请求地址这个 el-form-item 的 label 插槽里，
    // el-switch 切换会向所在 form-item 派发 change，把尚未填写的请求地址校验成必填，
    // 开关本身与地址是否已填无关，切换后清掉这条校验态
    const handleFullUrlChange = () => {
      proxy.$nextTick(() => clearValidate(['base_url']))
    }

    /**
     * 打开弹窗
     * @param {Object} [options] 传 { id } 为编辑（走详情接口回填），不传为新增
     */
    const openBox = (options) => {
      show.value = true
      testError.value = ''
      advancedExpanded.value = false
      // 详情请求失败时保持空表单打开，不让异常冒泡成 unhandled rejection
      modelForm
        .openForm(options)
        .catch(() => {})
        // 重置 / 回填字段会被 el-form-item 当成用户输入触发校验，
        // 等值写入组件后再清一次，否则编辑过一次再打开新增，空字段会直接带上必填红字
        .then(() => {
          advancedExpanded.value = modelForm.hasCustomAdvanced(
            modelForm.form.value.json,
            modelForm.form.value.enable_thinking
          )
          return proxy.$nextTick(clearValidate)
        })
    }

    const handleClose = () => {
      // 测试 / 提交进行中不允许关闭，避免请求回来时弹窗已销毁
      if (isSubmitting.value) return
      show.value = false
    }

    // 关闭动画结束后再清校验，避免关闭瞬间闪出红色提示
    const handleClosed = () => {
      isApiKeyVisible.value = false
      submitStage.value = SUBMIT_STAGE.IDLE
      testError.value = ''
      advancedExpanded.value = false
      clearValidate()
    }

    // 密钥失焦自动拉取模型：跳过（条件不满足 / 配置未变）时不提示，避免填表过程中反复弹提示
    const handleApiKeyBlur = async () => {
      if (modelForm.isYihaotongLocked.value) return
      const { success, skipped, message } = await modelForm.autoFetchModels()
      if (success || skipped) return
      if (message) proxy.$message.warning(message)
    }

    // 本地校验不通过时在此提示；接口报错由 request 统一弹出
    const handleFetchModels = async () => {
      if (modelForm.isYihaotongLocked.value || modelForm.isModelLoading.value) return
      const { success, message } = await modelForm.fetchModels()
      if (success) return
      if (message) proxy.$message.warning(message)
    }

    /** 跳转一号通配置页；先关弹窗避免遮挡目标页 */
    const goYihaotongConfig = () => {
      if (isSubmitting.value) return
      show.value = false
      proxy.$router.push(`${roterPre}/setting/yihaotong/config`)
    }

    /**
     * 可留空的数值字段：空串写成 null，合法数字再写回 number.
     * @param {'input_tokens'|'output_tokens'|'max_tool_rounds'|'temperature'|'top_p'|'top_k'} key
     * @param {string|number} value
     */
    const handleNullableNumber = (key, value) => {
      if (value === '' || value === null || value === undefined) {
        modelForm.form.value.json[key] = null
        return
      }
      const num = Number(value)
      modelForm.form.value.json[key] = Number.isFinite(num) ? num : null
    }

    // 确定：先做一次真实的连通性测试，通过后再提交保存；
    // 测试不通过时弹窗保持打开，原因内联展示在按钮上方
    const handleSubmit = () => {
      if (modelForm.isYihaotongLocked.value) {
        proxy.$message.warning('请先完成一号通配置')
        return
      }
      proxy.$refs.formRef.validate(async (valid) => {
        if (!valid) return
        testError.value = ''
        submitStage.value = SUBMIT_STAGE.TESTING
        try {
          const { success, message } = await modelForm.testConnection()
          if (!success) {
            testError.value = message
            return
          }

          submitStage.value = SUBMIT_STAGE.SUBMITTING
          const isOk = await modelForm.submitForm()
          if (!isOk) return
          // 成功提示由 request 封装按后端 message 统一弹出，此处不要重复提示
          submitStage.value = SUBMIT_STAGE.IDLE
          handleClose()
          emit('isOk')
        } finally {
          submitStage.value = SUBMIT_STAGE.IDLE
        }
      })
    }

    // 上下文窗口两行：输入 / 输出，前缀 + 输入框 + 右侧快捷值
    const tokenRows = [
      {
        field: 'input_tokens',
        label: '输入',
        placeholder: `请输入数值，留空则使用最佳默认值 ${ADVANCED_DEFAULTS.input_tokens}`,
        shortcuts: INPUT_TOKEN_SHORTCUTS
      },
      {
        field: 'output_tokens',
        label: '输出',
        placeholder: '请输入数值，留空则使用最佳默认值',
        shortcuts: OUTPUT_TOKEN_SHORTCUTS
      }
    ]

    // 采样参数三行：前缀为参数名，取值范围写在占位文案里
    const sampleRows = [
      { field: 'temperature', label: 'Temperature', placeholder: '留空使用最佳配置，或输入 0 ~ 2 之间的数值' },
      { field: 'top_p', label: 'Top P', placeholder: '留空使用最佳配置，或输入 0 ~ 1 之间的数值' },
      { field: 'top_k', label: 'Top K', placeholder: '留空使用最佳配置，或输入 1 ~ 100 之间的数值' }
    ]

    return {
      DIALOG_SIZE,
      CONFIG_TYPE,
      MODEL_CATEGORY,
      THINKING_MODE_OPTIONS,
      ADVANCED_DEFAULTS,
      tokenRows,
      sampleRows,

      show,
      isSubmitting,
      submitText,
      testError,
      isApiKeyVisible,
      advancedExpanded,
      ...modelForm,
      // 覆盖 modelForm 的同名方法：额外清理联动字段的校验态
      handleConfigTypeChange,
      handleDriverChange,
      handleModelCategoryChange,
      handleFullUrlChange,
      handleNullableNumber,
      handleFetchModels,

      openBox,
      handleClose,
      handleClosed,
      handleApiKeyBlur,
      goYihaotongConfig,
      handleSubmit
    }
  }
}
</script>

<style scoped lang="scss">
.full-width {
  width: 100%;
}

.yihaotong-tip {
  margin-top: 8px;
  line-height: 20px;
  font-size: 12px;
  color: #909399;
}

.tip-link {
  margin: 0 2px;
}

.model-form-body.is-locked {
  opacity: 0.55;
  pointer-events: none;
  user-select: none;
}

.field-action.is-disabled,
.advanced-header.is-disabled {
  cursor: not-allowed;
  opacity: 0.6;
  pointer-events: none;
}

/* 连通性测试未通过提示：排在表单下方、footer 按钮上方 */
.test-error {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 8px 12px;
  line-height: 20px;
  font-size: 13px;
  color: #f56c6c;
  background: #fef0f0;
  border-radius: 4px;
}

.test-error-icon {
  margin-top: 2px;
  font-size: 14px;
}

.test-error-text {
  flex: 1;
  word-break: break-all;
}

/* footer 左提示、右按钮 */
.dialog-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.footer-tip {
  display: flex;
  align-items: center;
  gap: 4px;
  line-height: 18px;
  font-size: 12px;
  color: #909399;
  text-align: left;
}

.footer-tip-icon {
  font-size: 14px;
  color: #1890ff;
}

:deep(.mini-switch .el-switch__core) {
  min-width: 40px !important;
}

.link {
  font-size: 12px;
  color: #1890ff;
  cursor: pointer;
}

/* label 改为 flex，必填星号与右侧附加控件（完整URL 开关）能排在同一行 */
::v-deep .el-form-item__label {
  display: flex;
  align-items: center;
  width: 100%;
  padding-bottom: 8px;
  line-height: 13px;
  font-size: 13px;
  color: #303133;
}

.label-row {
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: space-between;
}

.label-text {
  display: flex;
  align-items: center;
  gap: 4px;
}

.label-tip {
  font-size: 14px;
  color: #1890ff;
  cursor: pointer;
}

.label-switch {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #606266;
}

/* API 密钥右侧的「显示/隐藏 + 获取密钥」后缀 */
.api-key-item ::v-deep .el-input__inner {
  padding-right: 108px;
}

.input-suffix {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 100%;
}

.eye-icon {
  font-size: 16px;
  color: #909399;
}

/* 「获取模型」浮在下拉框内右侧，箭头相应左移让位 */
.field-with-action {
  position: relative;

  .field-action {
    position: absolute;
    top: 50%;
    right: 12px;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    transform: translateY(-50%);

    &.is-loading {
      cursor: default;
      opacity: 0.7;
    }

    .action-loading {
      margin-right: 4px;
    }
  }

  /* 右侧留白按「loading 图标 + 获取模型」的最大宽度预留，避免加载时布局跳动、图标压住箭头 */
  :deep(.el-input__suffix) {
    right: 78px;
  }
}

.advanced-block {
  margin-top: 4px;
}

.advanced-header {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 12px;
  cursor: pointer;
  user-select: none;
}

.advanced-toggle {
  font-size: 12px;
  color: #303133;
  transition: transform 0.2s;

  &.is-expanded {
    transform: rotate(90deg);
  }
}

.advanced-title {
  font-size: 13px;
  color: #303133;
  white-space: nowrap;
}

.advanced-body {
  /* 与上方基础表单项保持同一套 label / content 间距节奏 */
  :deep(.el-form-item) {
    margin-bottom: 20px;
  }

  :deep(.el-form-item__label) {
    display: flex;
    align-items: center;
    width: 100%;
    height: auto;
    padding: 0 0 12px;
    margin-bottom: 0;
    line-height: 13px;
    font-size: 13px;
    color: #303133;
  }

  :deep(.el-form-item__content) {
    line-height: 32px;
  }

  /* 参数名做输入框前缀：设计稿为 96px 定宽、浅灰底，与输入框共用一条边框 */
  /* table-cell 的 width 只是最小宽度，padding 要留够，否则 Temperature 这类长文案会把前缀撑宽 */
  :deep(.el-input-group__prepend) {
    box-sizing: border-box;
    width: 96px;
    padding: 0 9px;
    white-space: nowrap;
    background: #f5f5f5;
    border-color: #dcdee2;
    font-size: 13px;
    color: #303133;
    text-align: left;
  }
}

.label-nowrap {
  white-space: nowrap;
}

.token-field,
.sample-fields {
  display: flex;
  flex-direction: column;
  gap: 20px;
  width: 100%;
}

.token-row {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-width: 0;
}

.advanced-input {
  width: 100%;
  min-width: 0;
}

/* 输入框占满剩余宽度；快捷区定宽，避免输入/输出两行因快捷文案长短导致框宽不一致 */
.token-input {
  flex: 1;
  min-width: 0;
}

.shortcut-inline {
  display: flex;
  flex: 0 0 148px;
  align-items: center;
  gap: 10px;
  width: 148px;
  white-space: nowrap;
}

.shortcut {
  font-size: 13px;
  line-height: 20px;
  color: #909399;
  text-decoration: underline;
  cursor: pointer;
  white-space: nowrap;

  &:hover,
  &.active {
    color: #1890ff;
  }
}

.thinking-radios {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  min-height: 32px;
  white-space: nowrap;

  :deep(.el-radio) {
    margin-right: 16px;
    line-height: 32px;
    white-space: nowrap;
  }

  :deep(.el-radio__label) {
    white-space: nowrap;
  }
}
</style>

<style lang="scss">
/* 弹窗与 tooltip 都挂载到 body，样式需写在非 scoped 块内 */

/* 高级设置展开后弹窗只在内部滚动，不把整页撑出滚动条；
   不覆盖 el-dialog 默认的 15vh 上边距，弹窗位置与其它弹窗保持一致 */
.el-dialog.model-dialog {
  display: flex;
  flex-direction: column;
  max-height: calc(85vh - 40px);
  margin-bottom: 40px;

  .el-dialog__body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
  }
}

.el-tooltip__popper.base-url-tip {
  max-width: 320px;
  line-height: 20px;
}
</style>
