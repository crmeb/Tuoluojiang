<template>
  <el-dialog
    :title="isEdit ? '知识库设置' : '创建知识库'"
    v-model="visible"
    :width="DIALOG_SIZE.MD"
    :close-on-click-modal="false"
    append-to-body
    @closed="handleClosed"
  >
    <el-form
      ref="formRef"
      v-loading="isDetailLoading"
      :model="form"
      :rules="rules"
      label-width="100px"
      label-suffix="："
    >
      <el-form-item label="知识库图标" prop="icon">
        <iconPicker :icon="form.icon" :color="form.color" @update:icon="handleSelectIcon" @update:color="form.color = $event" />
      </el-form-item>

      <el-form-item label="知识库名称" prop="name">
        <el-input v-model="form.name" maxlength="50" show-word-limit placeholder="请输入知识库名称" />
      </el-form-item>

      <el-form-item label="知识库描述" prop="description">
        <el-input
          v-model="form.description"
          type="textarea"
          :rows="3"
          maxlength="500"
          show-word-limit
          placeholder="描述知识库的内容，详尽的描述讲帮助AI能深入理解该知识库的内容，能更准确的检索到内容，提高该知识库的命中率"
        />
      </el-form-item>

      <el-form-item label="向量模型" prop="embedding_model_id">
        <embeddingModelSelect v-model="form.embedding_model_id" />
      </el-form-item>
    </el-form>

    <template #footer>
      <div>
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" :loading="isSubmitting" @click="handleSubmit">
          {{ isEdit ? '确定' : '创建' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { knowledgeBaseDetailApi } from '@/api/knowledge.ts'
import iconPicker from './iconPicker.vue'
import embeddingModelSelect from './embeddingModelSelect.vue'
import { useKnowledgeForm } from '../composables/useKnowledgeForm.ts'

const emit = defineEmits<{ (e: 'isOk'): void }>()

const visible = ref(false)
const isDetailLoading = ref(false)

const {
  form,
  rules,
  formRef,
  isEdit,
  isSubmitting,
  resetForm,
  applyDetail,
  handleSelectIcon,
  submit
} = useKnowledgeForm()

async function fetchDetail(id: number) {
  isDetailLoading.value = true
  try {
    const res = await knowledgeBaseDetailApi(id)
    applyDetail(res.data)
  } finally {
    isDetailLoading.value = false
  }
}

function handleClosed() {
  resetForm()
}

async function handleSubmit() {
  const isOk = await submit()
  if (!isOk) return
  visible.value = false
  emit('isOk')
}

/** 不传 row 为新建；传 { id } 走详情接口取最新配置 */
function openBox(row?: { id: number }) {
  resetForm()
  visible.value = true
  if (row && row.id) fetchDetail(row.id)
}

defineExpose({ openBox })
</script>
