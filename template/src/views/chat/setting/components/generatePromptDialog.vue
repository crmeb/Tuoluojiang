<template>
  <el-dialog
    class="generate-prompt-dialog"
    v-model="visible"
    :width="DIALOG_SIZE.LG"
    append-to-body
    :close-on-click-modal="false"
    top="8vh"
    @close="handleClose"
  >
    <template #header>
      <div class="dialog-title">
        <span class="title-icon">
          <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
            <path
              fill="currentColor"
              d="M12 2.6l1.9 5.1a4 4 0 0 0 2.4 2.4l5.1 1.9-5.1 1.9a4 4 0 0 0-2.4 2.4L12 21.4l-1.9-5.1a4 4 0 0 0-2.4-2.4L2.6 12l5.1-1.9a4 4 0 0 0 2.4-2.4L12 2.6zM19.5 2l.8 2.1 2.1.8-2.1.8-.8 2.1-.8-2.1-2.1-.8 2.1-.8.8-2.1z"
            />
          </svg>
        </span>
        <div class="title-text">
          <p class="main">AI生成{{ title }}</p>
          <p class="sub">基于应用名称与简介生成，可补充要求后重新生成</p>
        </div>
      </div>
    </template>

    <div class="demand">
      <div class="block-head">
        <span class="block-title">生成要求</span>
        <span class="block-tag">选填</span>
      </div>
      <el-input
        v-model="demand"
        type="textarea"
        class="demand-box"
        :rows="3"
        :maxlength="DEMAND_MAX"
        show-word-limit
        :readonly="isGenerating"
        placeholder="描述你希望的角色、语气或边界，例如：面向售后客服，回答要简短并附带工单编号"
      />
      <div class="demand-foot">
        <div class="quick">
          <span
            v-for="item in QUICK_DEMANDS"
            :key="item"
            class="quick-item"
            :class="{ 'is-disabled': isGenerating }"
            @click="appendDemand(item)"
          >
            + {{ item }}
          </span>
        </div>
        <el-button class="generate-btn" type="primary" :loading="isGenerating" @click="handleGenerate">
          {{ isGenerating ? '生成中' : text ? '重新生成' : '开始生成' }}
        </el-button>
      </div>
    </div>

    <div class="result">
      <div class="block-head">
        <span class="block-title">生成结果</span>
        <span v-if="isGenerating" class="generating">
          AI 正在生成<i class="dot" /><i class="dot" /><i class="dot" />
        </span>
        <span v-else-if="text" class="block-hint">可直接编辑后点击确定</span>
        <span class="count">{{ text.length }} / {{ maxlength }}</span>
      </div>
      <div class="result-wrap" :class="{ 'is-generating': isGenerating }" :style="{ '--textarea-height': height + 'px' }">
        <el-input
          ref="resultRef"
          v-model="text"
          type="textarea"
          class="result-box oa-input-borderless"
          :maxlength="maxlength"
          :readonly="isGenerating"
          placeholder=""
        />
        <div v-if="!text && !isGenerating" class="empty">
          <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true">
            <path
              fill="currentColor"
              d="M12 2.6l1.9 5.1a4 4 0 0 0 2.4 2.4l5.1 1.9-5.1 1.9a4 4 0 0 0-2.4 2.4L12 21.4l-1.9-5.1a4 4 0 0 0-2.4-2.4L2.6 12l5.1-1.9a4 4 0 0 0 2.4-2.4L12 2.6z"
            />
          </svg>
          <p>填写要求后点击「开始生成」，结果会展示在这里</p>
        </div>
      </div>
    </div>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" :disabled="isGenerating || !text.trim()" @click="submit">
          确定并填入
        </el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { nextTick, ref } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { useGeneratePrompt } from '../composables/useGeneratePrompt.ts'

/** 生成要求的字数上限 */
const DEMAND_MAX = 200

/** 常用生成要求，点击追加到输入框，省去反复手打 */
const QUICK_DEMANDS = ['语气更专业', '回答更简短', '分步骤说明', '明确拒答边界', '结尾追问引导']

const props = withDefaults(
  defineProps<{
    /** AI模型配置ID，未选模型时接口无法执行 */
    modelsId: number | ''
    /** 生成上下文，人设场景传「应用名称 + 应用简介」 */
    content: string
    /** 生成类型，见 constants 的 PROMPT_TYPE */
    type: string
    /** 弹窗标题里的字段名，如「人设与回复逻辑」 */
    title: string
    /** 结果文本域字数上限，与目标输入框保持一致 */
    maxlength: number
    /** 结果文本域高度 px */
    height?: number
  }>(),
  { height: 360 }
)

const emit = defineEmits<{
  /** 点击确定，携带弹窗里的最终文本，由调用方回填目标字段 */
  (e: 'confirm', value: string): void
}>()

const { isGenerating, generate, stop } = useGeneratePrompt()

const visible = ref(false)
const demand = ref('')
const text = ref('')
const resultRef = ref<any>(null)

/** 流式追加时把结果区滚到底，否则新内容看不见 */
const scrollToBottom = () => {
  nextTick(() => {
    const inner = resultRef.value?.$el?.querySelector('.el-textarea__inner')
    if (inner) inner.scrollTop = inner.scrollHeight
  })
}

/** 追加常用要求，已写过的不重复加，超长则忽略 */
const appendDemand = (item: string) => {
  if (isGenerating.value || demand.value.includes(item)) return
  const next = demand.value.trim() ? `${demand.value.trim()}、${item}` : item
  if (next.length > DEMAND_MAX) return
  demand.value = next
}

const handleGenerate = () => {
  if (isGenerating.value) return

  generate({
    // 补充要求拼在上下文后面，为空时与原来的行为一致
    content: [props.content, demand.value.trim() && `生成要求：${demand.value.trim()}`].filter(Boolean).join('\n'),
    modelsId: props.modelsId,
    type: props.type,
    // 首个分片到达才清空，校验/鉴权失败时保留已有文案
    onStart: () => (text.value = ''),
    onChunk: (chunk) => {
      text.value += chunk
      scrollToBottom()
    }
  })
}

const handleClose = () => {
  // 生成过程中关闭要中断请求，否则分片仍会写进已废弃的文本
  stop()
  demand.value = ''
  text.value = ''
}

const submit = () => {
  emit('confirm', text.value)
  visible.value = false
}

/** 打开弹窗，带入目标字段当前内容，便于在原文基础上修改 */
const openBox = (value = '') => {
  text.value = value
  visible.value = true
}

defineExpose({ openBox })
</script>

<style scoped lang="scss">
$primary: #1890ff;

.dialog-title {
  display: flex;
  align-items: center;

  .title-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    margin-right: 10px;
    border-radius: 8px;
    background: linear-gradient(135deg, #4b9dff 0%, #7b6cff 100%);
    color: #fff;
  }

  .title-text .main {
    margin: 0;
    font-weight: 500;
    font-size: 16px;
    line-height: 22px;
    color: #303133;
  }

  .title-text .sub {
    margin: 2px 0 0;
    font-weight: 400;
    font-size: 12px;
    line-height: 16px;
    color: #909399;
  }
}

.block-head {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  font-size: 13px;
  line-height: 18px;

  .block-title {
    font-weight: 500;
    color: #303133;
  }

  .block-tag {
    margin-left: 6px;
    padding: 0 6px;
    border-radius: 4px;
    background: #f0f2f5;
    font-size: 11px;
    line-height: 16px;
    color: #909399;
  }

  .block-hint {
    margin-left: 8px;
    font-size: 12px;
    color: #c0c4cc;
  }

  .count {
    margin-left: auto;
    font-size: 12px;
    color: #909399;
  }
}

.generating {
  display: flex;
  align-items: center;
  margin-left: 8px;
  font-size: 12px;
  color: $primary;

  .dot {
    width: 3px;
    height: 3px;
    margin-left: 2px;
    border-radius: 50%;
    background: currentColor;
    animation: blink 1.2s infinite ease-in-out;

    &:nth-child(2) {
      animation-delay: 0.2s;
    }

    &:nth-child(3) {
      animation-delay: 0.4s;
    }
  }
}

@keyframes blink {
  0%,
  60%,
  100% {
    opacity: 0.2;
  }
  30% {
    opacity: 1;
  }
}

.demand {
  margin-bottom: 18px;
}

.demand-box {
  :deep(.el-textarea__inner) {
    padding: 10px 12px;
    resize: none;
    border: 1px solid #e4e7ed;
    border-radius: 8px;
    background: #fafbfc;
    font-size: 13px;
    line-height: 20px;
    transition: border-color 0.2s, background 0.2s;

    &:focus {
      border-color: $primary;
      background: #fff;
    }
  }

  :deep(.el-input__count) {
    background: transparent;
    font-size: 12px;
  }
}

.demand-foot {
  display: flex;
  align-items: center;
  margin-top: 10px;

  .quick {
    display: flex;
    flex-wrap: wrap;
    flex: 1;
    min-width: 0;
  }

  .quick-item {
    margin: 4px 8px 0 0;
    padding: 3px 10px;
    border: 1px solid #e4e7ed;
    border-radius: 12px;
    background: #fff;
    font-size: 12px;
    line-height: 16px;
    color: #606266;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
      border-color: $primary;
      background: #ecf5ff;
      color: $primary;
    }

    &.is-disabled {
      color: #c0c4cc;
      cursor: not-allowed;

      &:hover {
        border-color: #e4e7ed;
        background: #fff;
        color: #c0c4cc;
      }
    }
  }

  .generate-btn {
    flex: none;
    margin-left: 12px;
    padding: 8px 18px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #4b9dff 0%, #7b6cff 100%);

    &:hover {
      opacity: 0.88;
    }

    &.is-loading {
      opacity: 0.7;
    }
  }
}

.result-wrap {
  position: relative;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  background: #fafbfc;
  transition: border-color 0.2s;

  &:focus-within {
    border-color: $primary;
    background: #fff;
  }

  &.is-generating {
    border-color: $primary;
    background: #fff;
  }
}

.result-box {
  :deep(.el-textarea__inner) {
    // 视口矮时优先保证弹窗不超屏
    height: min(var(--textarea-height), 40vh);
    padding: 12px 14px;
    resize: none;
    border-radius: 8px;
    background: transparent;
    font-size: 13px;
    line-height: 22px;
    color: #303133;
  }
}

.empty {
  position: absolute;
  top: 0;
  left: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  pointer-events: none;
  color: #c9cdd4;

  p {
    margin: 10px 0 0;
    font-size: 13px;
  }
}

.dialog-footer .el-button {
  padding: 9px 20px;
  border-radius: 8px;
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.generate-prompt-dialog {
  & {
    border-radius: 12px;
    overflow: hidden;
  }

  .el-dialog__header {
    padding: 20px 24px 16px;
    border: none;
    background: linear-gradient(180deg, #f3f8ff 0%, #fff 100%);
  }

  .el-dialog__headerbtn {
    top: 22px;
    right: 22px;
  }

  .el-dialog__body {
    padding: 4px 24px 0;
  }

  .el-dialog__footer {
    padding: 16px 24px 20px;
  }
}
</style>
