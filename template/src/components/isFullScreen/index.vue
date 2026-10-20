<!--
  全屏遮罩组件
  用于创建可全屏显示的弹层容器，支持 z-index 自动管理
-->
<template>
  <div :class="{ 'my-full-screen': isFullScreen }" :style="{ zIndex: currentZIndex }">
    <div class="mask" @click="handlePopoverHide"></div>
    <slot></slot>
  </div>
</template>

<script>
/**
 * 全屏遮罩组件 - 使用 Vue 2.7 Composition API 重构
 *
 * @component IsFullScreen
 * @description 提供全屏弹层能力，自动管理 z-index 确保层级正确
 * @example
 * <is-full-screen ref="fullScreenRef" @call-parent-method="handleClose">
 *   <div>弹层内容</div>
 * </is-full-screen>
 *
 * // 调用全屏显示
 * this.$refs.fullScreenRef.request()
 */
import { ref } from 'vue'
import { useZIndex } from 'element-plus'

export default {
  name: 'IsFullScreen',
  emits: ['call-parent-method'],
  setup(props, { emit }) {
    /**
     * 全屏状态标志
     * @type {Ref<boolean>}
     * @description 控制是否显示为全屏模式
     */
    const isFullScreen = ref(false)
    const { nextZIndex } = useZIndex()

    /**
     * 当前 z-index 层级
     * @type {Ref<number|null>}
     * @description 由 Element Plus 的 useZIndex 自动分配，确保弹层在最上层
     */
    const currentZIndex = ref(null)

    /**
     * 请求进入全屏模式
     * @description 设置全屏状态并获取新的 z-index
     * @example
     * // 父组件调用
     * this.$refs.fullScreen.request()
     */
    const request = () => {
      isFullScreen.value = true
      // 从 Element Plus 的 z-index 管理器获取下一个可用的 z-index
      currentZIndex.value = nextZIndex()
    }

    /**
     * 处理遮罩层点击事件
     * @description 触发父组件的 call-parent-method 事件，通常用于关闭弹层
     */
    const handlePopoverHide = () => {
      emit('call-parent-method')
    }

    // setup 返回值同时供模板渲染与父组件通过 ref 调用（request / isFullScreen）
    return {
      isFullScreen,
      currentZIndex,
      request,
      handlePopoverHide
    }
  }
}
</script>

<style scoped>
/* 遮罩层样式 - 透明背景覆盖整个视口 */
.mask {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0);
  z-index: 1;
}

/* 全屏模式样式 - 固定定位覆盖整个屏幕 */
.my-full-screen {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100% !important;
  height: 100% !important;
}
</style>
