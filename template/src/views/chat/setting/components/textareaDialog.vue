<template>
  <el-dialog
    class="textarea-dialog"
    :title="title"
    v-model="visible"
    :width="DIALOG_SIZE.MD"
    :close-on-click-modal="false"
    top="10vh"
    @close="reset"
  >
    <el-input
      v-model="text"
      type="textarea"
      class="textarea-box oa-input-borderless"
      :style="{ '--textarea-height': height + 'px' }"
      :maxlength="maxlength"
      show-word-limit
      placeholder="请输入内容"
    />
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" @click="submit">确定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'

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

.textarea-box {
  :deep(.el-textarea__inner) {
    height: var(--textarea-height);
    resize: none;
    background: #f2f5f9;
  }

  :deep(.el-input__count) {
    background: #f2f5f9;
  }
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.textarea-dialog {
  .el-dialog__header {
    border: none;
  }

  .el-dialog__body {
    padding-top: 0;
  }
}
</style>
