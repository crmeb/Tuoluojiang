<template>
  <div class="field-label">
    <div class="field-label-main">
      <span
        v-if="collapsible"
        class="el-icon-caret-right toggle"
        :class="{ 'is-expanded': expanded }"
        @click.prevent="emit('toggle')"
      />
      <span class="text">{{ label }}</span>
      <popover v-if="tips" :tips="tips" :width="tipsWidth" />
    </div>
    <div v-if="slots.extra" class="field-label-extra" @click.prevent>
      <slot name="extra" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useSlots } from 'vue'
import popover from '../../components/popover.vue'

withDefaults(
  defineProps<{
    label: string
    tips?: string
    tipsWidth?: number
    /** 是否带折叠箭头，折叠状态由父级维护 */
    collapsible?: boolean
    expanded?: boolean
  }>(),
  {
    tips: '',
    tipsWidth: 315,
    collapsible: false,
    expanded: true
  }
)

const emit = defineEmits<{ (e: 'toggle'): void }>()

const slots = useSlots()
</script>

<style scoped lang="scss">
.field-label {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 500;
  font-size: 13px;
  line-height: 13px;
  color: #303133;
}

.field-label-main {
  display: flex;
  align-items: center;
}

.field-label-extra {
  display: flex;
  align-items: center;
  font-weight: 400;
}

.toggle {
  margin-right: 4px;
  font-size: 14px;
  cursor: pointer;
  transition: transform 0.2s;

  &.is-expanded {
    transform: rotate(90deg);
  }
}
</style>
