<template>
  <span class="text-btn" @click="openDialog">
    AI生成

    <generatePromptDialog
      ref="dialogRef"
      :models-id="modelsId"
      :content="content"
      :type="type"
      :title="title"
      :maxlength="maxlength"
      :height="height"
      @confirm="emit('confirm', $event)"
    />
  </span>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import generatePromptDialog from './generatePromptDialog.vue'

const props = defineProps<{
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
  /** 目标字段当前内容，打开弹窗时带入，便于在原文基础上改 */
  value?: string
}>()

const emit = defineEmits<{
  /** 弹窗点击确定，调用方把文本回填到目标字段 */
  (e: 'confirm', value: string): void
}>()

const dialogRef = ref<any>(null)

const openDialog = () => {
  dialogRef.value.openBox(props.value || '')
}
</script>

<style scoped lang="scss">
.text-btn {
  font-weight: 400;
  font-size: 13px;
  color: #1890ff;
  cursor: pointer;
}
</style>
