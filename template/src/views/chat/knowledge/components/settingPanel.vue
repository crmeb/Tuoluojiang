<!-- 知识库详情-设置页签：字段与创建弹窗一致，逻辑复用 useKnowledgeForm -->
<template>
  <div class="setting-panel">
    <div class="setting-body">
      <!-- 分组标题与表单同宽同列，左边缘对齐 -->
      <div class="setting-inner">
        <div class="section-title">
          <span class="section-bar" />
          基本信息
        </div>

        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-width="95px"
          size="small"
          label-suffix="："
          class="setting-form"
        >
          <el-form-item label="知识库图标" prop="icon">
            <iconPicker
              :icon="form.icon"
              :color="form.color"
              @update:icon="handleSelectIcon"
              @update:color="form.color = $event"
            />
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
      </div>
    </div>

    <!-- 设计稿底部只有「保存」，用插槽覆盖组件默认的「取消 + 保存」 -->
    <FormPageFooter>
      <el-button size="small" type="primary" :loading="isSubmitting" @click="handleSave">
        保存
      </el-button>
    </FormPageFooter>
  </div>
</template>

<script setup lang="ts">
import { watch } from 'vue'
import { Message } from 'element-ui'
import FormPageFooter from '@/components/FormPageFooter/index.vue'
import iconPicker from './iconPicker.vue'
import embeddingModelSelect from './embeddingModelSelect.vue'
import { useKnowledgeForm } from '../composables/useKnowledgeForm'

const props = defineProps<{ detail: any }>()

const emit = defineEmits<{ (e: 'saved'): void }>()

const {
  form,
  rules,
  formRef,
  isSubmitting,
  applyDetail,
  handleSelectIcon,
  submit
} = useKnowledgeForm()

// 详情由父级统一请求后下发，避免详情页与设置页各拉一次
watch(
  () => props.detail,
  (detail) => {
    if (!detail || !detail.id) return
    applyDetail(detail)
  },
  { immediate: true }
)

async function handleSave() {
  const isOk = await submit()
  if (!isOk) return
  emit('saved')
}
</script>

<style scoped lang="scss">
.setting-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.setting-body {
  flex: 1;
  min-height: 0;
  padding: 25px 20px 0;
  overflow-y: auto;
}

// 设计稿表单宽 675px（标签 89 + 输入 580），分组标题与表单同处这一列，左边缘对齐
.setting-inner {
  max-width: 675px;
  margin: 0 auto;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 500;
  font-size: 16px;
  line-height: 20px;
  color: #303133;
}

.section-bar {
  width: 3px;
  height: 14px;
  background: #1890ff;
}

.setting-form {
  margin-top: 32px;
}
</style>
