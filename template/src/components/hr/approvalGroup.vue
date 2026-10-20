<!-- @FileDescription: 人事-审批设置-控件组容器（请假、加班、出差、合同等），只负责包裹组内字段 -->
<template>
  <div class="approval-group">
    <slot />
  </div>
</template>

<script setup lang="ts">
// form-create 会传入 modelValue、formCreateInject 等属性，容器不需要，也不透传到 DOM
defineOptions({ name: 'ApprovalGroup', inheritAttrs: false })
</script>

<style scoped>
/* 表单项内容区是 flex 容器，不设宽度会按内容收缩，组内字段随之变窄 */
.approval-group {
  width: 100%;
}
</style>
