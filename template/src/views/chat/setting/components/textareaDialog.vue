<template>
  <el-dialog
    class="textarea-dialog"
    :title="title"
    :visible.sync="visible"
    :width="DIALOG_SIZE.MD"
    :close-on-click-modal="false"
    top="10vh"
    @close="reset"
  >
    <el-input
      v-model="text"
      type="textarea"
      class="textarea-box"
      :style="{ '--textarea-height': height + 'px' }"
      :maxlength="maxlength"
      show-word-limit
      placeholder="请输入内容"
    />
    <template #footer>
      <span class="dialog-footer">
        <el-button size="small" @click="visible = false">取消</el-button>
        <el-button size="small" type="primary" @click="submit">确定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize'

const emit = defineEmits<{ (e: 'submit', value: string): void }>()

const visible = ref(false)
const title = ref('')
const text = ref('')
const maxlength = ref(200)
const height = ref(307)

interface OpenOptions {
  title: string
  value: string
  maxlength: number
  /** 文本域高度，长提示词用 540，其余用默认值 */
  height?: number
}

const reset = () => {
  text.value = ''
}

const openBox = (options: OpenOptions) => {
  title.value = options.title
  text.value = options.value
  maxlength.value = options.maxlength
  height.value = options.height || 307
  visible.value = true
}

const submit = () => {
  emit('submit', text.value)
  visible.value = false
}

defineExpose({ openBox })
</script>

<style scoped lang="scss">
.textarea-dialog {
  :deep(.el-dialog__header) {
    border: none;
  }

  :deep(.el-dialog__body) {
    padding-top: 0;
  }
}

.textarea-box {
  :deep(.el-textarea__inner) {
    height: var(--textarea-height);
    resize: none;
    background: #f2f5f9;
    border: none;
  }

  :deep(.el-input__count) {
    background: #f2f5f9;
  }
}
</style>
