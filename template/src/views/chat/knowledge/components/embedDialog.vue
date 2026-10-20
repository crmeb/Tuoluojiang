<!-- 文档向量化二次确认：选择向量化范围
     接口 reprocess 不接收范围参数，选项仅按设计稿保留交互，实际按整篇文档重新处理 -->
<template>
  <el-dialog
    title="选择向量化内容"
    v-model="visible"
    :width="DIALOG_SIZE.MD"
    :close-on-click-modal="false"
    append-to-body
  >
    <el-radio-group v-model="scope" class="embed-scope">
      <el-radio v-for="item in EMBED_SCOPES" :key="item.value" :label="item.value" :value="item.value">
        {{ item.label }}
      </el-radio>
    </el-radio-group>

    <template #footer>
      <div>
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" @click="handleConfirm">确定</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { EMBED_SCOPES } from '../constants.ts'

const emit = defineEmits<{ (e: 'confirm', scope: string): void }>()

const visible = ref(false)
const scope = ref(EMBED_SCOPES[0].value)

function handleConfirm() {
  visible.value = false
  emit('confirm', scope.value)
}

function openBox() {
  scope.value = EMBED_SCOPES[0].value
  visible.value = true
}

defineExpose({ openBox })
</script>

<style scoped lang="scss">
.embed-scope {
  display: flex;
  flex-direction: column;
  gap: 19px;

  :deep(.el-radio__label) {
    font-size: 13px;
    color: #303133;
  }
}
</style>
