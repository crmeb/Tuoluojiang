<!--
  状态切换开关组件
  封装 el-switch，提供统一的状态切换交互
-->
<template>
  <div class="composite-selector">
    <!-- 显示名称 -->
    <span>{{ name }}：</span>
    <!-- 开关组件 -->
    <el-switch
      v-model="internalValue"
      :active-text="activeText"
      :inactive-text="inactiveText"
    >
    </el-switch>
  </div>
</template>

<script>
/**
 * 状态切换开关组件
 *
 * @component SwitchStatus
 * @description 封装 Element Plus 的 el-switch 组件，支持 Vue 3 v-model 双向绑定
 * @example
 * <switch-status
 *   v-model="isEnabled"
 *   name="启用状态"
 *   active-text="开启"
 *   inactive-text="关闭"
 *   @change="handleChange"
 * />
 */
import { computed } from 'vue'

export default {
  name: 'SwitchStatus',
  emits: ['update:modelValue', 'change'],
  props: {
    /**
     * 开关状态值（支持 v-model）
     * @model
     */
    modelValue: {
      type: Boolean,
      default: true
    },
    /**
     * 开启状态显示文本
     */
    activeText: {
      type: String,
      default: undefined
    },
    /**
     * 关闭状态显示文本
     */
    inactiveText: {
      type: String,
      default: undefined
    },
    /**
     * 开关名称
     * 显示在开关左侧作为标签
     */
    name: {
      type: String,
      default: undefined
    }
  },
  setup(props, { emit }) {
    const internalValue = computed({
      get: () => props.modelValue,
      set: (newVal) => {
        emit('update:modelValue', newVal)
        emit('change', newVal)
      }
    })

    return { internalValue }
  }
}
</script>

<style scoped lang="scss">
/* 复合选择器容器样式 */
.composite-selector {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
