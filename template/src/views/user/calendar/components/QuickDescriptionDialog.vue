<template>
  <el-dialog
    :visible.sync="visible"
    title="描述"
    :width="DIALOG_SIZE.MD"
    :close-on-click-modal="false"
    class="quick-description-dialog"
    @close="reset"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="70px">
      <el-form-item label="描述：" prop="content">
        <el-input
          v-model="form.content"
          type="textarea"
          :rows="5"
          maxlength="2000"
          show-word-limit
          placeholder="请输入..."
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <span>
        <el-button size="small" @click="visible = false">取消</el-button>
        <el-button size="small" type="primary" @click="submit">确定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { nextTick, reactive, ref } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize'

interface DescriptionForm {
  content: string
}

interface FormInstance {
  clearValidate: () => void
  validate: (callback: (valid: boolean) => void) => void
}

const emit = defineEmits<{
  (event: 'confirm', value: string): void
}>()

const visible = ref(false)
const formRef = ref<FormInstance | null>(null)
const form = reactive<DescriptionForm>({
  content: ''
})
const rules = {
  content: [{ required: true, message: '请输入描述', trigger: 'blur' }]
}

function reset() {
  form.content = ''
  nextTick(() => formRef.value?.clearValidate())
}

function openBox(value = '') {
  reset()
  form.content = value
  visible.value = true
}

function submit() {
  formRef.value?.validate((valid) => {
    if (!valid) return
    emit('confirm', form.content.trim())
    visible.value = false
  })
}

defineExpose({ openBox })
</script>

<style lang="scss" scoped>
.quick-description-dialog {
  :deep(.el-dialog__body) {
    padding: 24px 20px 16px;
  }

  :deep(.el-form-item) {
    margin-bottom: 0;
  }

  :deep(.el-textarea__inner) {
    min-height: 108px !important;
    resize: none;
  }
}
</style>
