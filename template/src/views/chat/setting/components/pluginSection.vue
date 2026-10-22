<template>
  <div class="plugin-section">
    <div class="plugin-switch">
      <span class="plugin-title">{{ label }}：</span>
      <el-switch
        :value="enabled"
        active-text="开启"
        inactive-text="关闭"
        :active-value="true"
        :inactive-value="false"
        @change="emit('change', $event)"
      />
    </div>

    <el-collapse-transition>
      <div v-show="enabled" class="plugin-body">
        <slot />
      </div>
    </el-collapse-transition>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  label: string
  enabled: boolean
}>()

const emit = defineEmits<{ (e: 'change', enabled: boolean): void }>()
</script>

<style scoped lang="scss">
.plugin-section {
  & + & {
    margin-top: 20px;
  }
}

.plugin-switch {
  display: flex;
  align-items: center;
  margin-bottom: 14px;
}

.plugin-title {
  margin-right: 6px;
  font-weight: 400;
  text-align: right;
  min-width: 4em;
  font-size: 13px;
  color: #303133;
}

.plugin-body {
  padding: 14px;
  border-radius: 4px;
  background: #f7f7f7;
}
</style>
