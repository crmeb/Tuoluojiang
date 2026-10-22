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
 * 状态切换开关组件 - 使用 Vue 2.7 Composition API 重构
 *
 * @component SwitchStatus
 * @description 封装 Element UI 的 el-switch 组件，支持 v-model 双向绑定
 * @example
 * <switch-status
 *   v-model="isEnabled"
 *   name="启用状态"
 *   active-text="开启"
 *   inactive-text="关闭"
 *   @change="handleChange"
 * />
 */
import { ref, watch } from 'vue'

export default {
  name: 'SwitchStatus',
  props: {
    /**
     * 开关状态值（支持 v-model）
     * @model
     */
    value: {
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
    /**
     * 内部维护的开关状态
     * 使用 ref 创建响应式引用，初始值为 props.value
     */
    const internalValue = ref(props.value)

    /**
     * 监听内部状态变化，触发事件通知父组件
     * 同时触发 'input' 和 'change' 事件以支持 v-model 和自定义事件处理
     */
    watch(internalValue, (newVal) => {
      // 触发 v-model 更新
      emit('input', newVal)
      // 触发 change 事件
      emit('change', newVal)
    })

    /**
     * 监听外部传入的 value 变化，同步到内部状态
     * 确保父组件修改 value 时能正确同步
     */
    watch(
      () => props.value,
      (newVal) => {
        internalValue.value = newVal
      }
    )

    return { internalValue }
  }
}
</script>

<style scoped lang="scss">
/* 选择器样式 */
.el-select .el-input {
  width: 130px;
}
/* 输入框前置选择器样式 */
.el-input-group__prepend .el-select {
  width: 100px;
}
/* 复合选择器容器样式 */
.composite-selector {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
