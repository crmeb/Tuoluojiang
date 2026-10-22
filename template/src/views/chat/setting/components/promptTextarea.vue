<template>
  <div class="prompt-textarea" :style="{ '--textarea-height': height + 'px' }">
    <el-input
      type="textarea"
      :value="value"
      :placeholder="placeholder"
      :maxlength="maxlength"
      :readonly="readonly"
      @input="emit('input', $event)"
    />
    <div class="append" :class="{ 'is-readonly': readonly }">
      <span class="count">{{ value.length }} / {{ maxlength }}</span>
      <span v-if="clearable" class="iconfont iconshanchujilu" title="清空" @click="handleClear" />
      <span class="iconfont iconzhankai2" title="展开" @click="openDialog" />
    </div>

    <textareaDialog ref="dialogRef" @submit="emit('input', $event)" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import textareaDialog from './textareaDialog.vue'

const props = withDefaults(
  defineProps<{
    value: string
    /** 展开弹窗的标题 */
    title: string
    maxlength: number
    /** 文本域高度 px */
    height?: number
    placeholder?: string
    /** 是否展示清空按钮 */
    clearable?: boolean
    /** 只读：AI 生成过程中禁止编辑，清空与展开编辑一并锁掉 */
    readonly?: boolean
  }>(),
  {
    height: 90,
    placeholder: '请输入内容',
    clearable: false,
    readonly: false
  }
)

const emit = defineEmits<{ (e: 'input', value: string): void }>()

const dialogRef = ref<any>(null)

const handleClear = () => {
  if (props.readonly) return
  emit('input', '')
}

const openDialog = () => {
  // 弹窗里是可编辑的大文本域，只读时不能放行，否则绕过 readonly
  if (props.readonly) return

  dialogRef.value.openBox({
    title: props.title,
    value: props.value,
    maxlength: props.maxlength,
    // 长文本（人设与回复逻辑）展开后给更高的编辑区
    height: props.height > 200 ? 540 : 307
  })
}
</script>

<style scoped lang="scss">
.prompt-textarea {
  position: relative;

  :deep(.el-textarea__inner) {
    height: var(--textarea-height);
    resize: none;
  }
}

.append {
  position: absolute;
  right: 10px;
  bottom: 4px;
  display: flex;
  align-items: center;
  background: #fff;
  font-weight: 400;
  font-size: 12px;
  color: #606266;

  .count {
    color: #909399;
  }

  .iconfont {
    margin-left: 8px;
    font-size: 14px;
    cursor: pointer;
  }

  &.is-readonly .iconfont {
    color: #c0c4cc;
    cursor: not-allowed;
  }
}
</style>
