<template>
  <el-dialog
    title="创建应用"
    v-model="visible"
    :width="DIALOG_SIZE.MD"
    :close-on-click-modal="false"
    @closed="resetForm"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="100px" label-suffix="：">
      <el-form-item label="应用名称" prop="name">
        <el-input v-model="form.name" maxlength="10" show-word-limit placeholder="请输入应用名称" />
      </el-form-item>

      <el-form-item label="应用简介" prop="info">
        <el-input
          v-model="form.info"
          type="textarea"
          :rows="5"
          maxlength="100"
          show-word-limit
          :placeholder="APP_INFO_PLACEHOLDER"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <div>
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" :loading="isSubmitting" @click="handleSubmit">创建</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { chatQuickSaveApplicationsApi } from '@/api/chatAi.ts'
import { APP_INFO_PLACEHOLDER } from '../constants.ts'
import { APP_TYPE } from '../setting/constants'

const props = withDefaults(defineProps<{ appType?: number }>(), { appType: APP_TYPE.INTERNAL })

const emit = defineEmits<{
  (e: 'created', id: number): void
}>()

const visible = ref(false)
const isSubmitting = ref(false)
const formRef = ref<any>(null)

const generateForm = () => ({ name: '', info: '' })

const form = reactive(generateForm())

const rules = {
  name: [{ required: true, message: '请输入应用名称', trigger: 'blur' }]
}

function resetForm() {
  Object.assign(form, generateForm())
  if (formRef.value) formRef.value.clearValidate()
}

/**
 * 提交创建
 *
 * 走「快速新增」接口：提交名称、简介和模块固定的应用类型，未发布状态、创建人的编辑与使用权限
 * 都由后端补默认值，其余配置进设置页再调。
 */
function handleSubmit() {
  formRef.value.validate(async (valid: boolean) => {
    if (!valid) return
    isSubmitting.value = true
    try {
      const res = await chatQuickSaveApplicationsApi({ name: form.name, info: form.info, app_type: props.appType })
      // request.js 对业务失败（status 400）是 resolve 而非 reject，不判一次会把失败当成功
      if (res.status !== 200) return
      visible.value = false
      emit('created', res.data.id)
    } finally {
      isSubmitting.value = false
    }
  })
}

function openBox() {
  resetForm()
  visible.value = true
}

defineExpose({ openBox })
</script>
