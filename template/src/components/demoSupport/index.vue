<template>
  <div class="demo-support">
    <!-- 右下角长期悬浮的客服图标，支持垂直拖动避免遮挡数据 -->
    <div
      class="demo-support__float"
      :style="{ bottom: floatBottom + 'px' }"
      title="联系客服"
      @mousedown="onFloatMouseDown"
      @click="onFloatClick"
    >
      <img :src="floatIcon" alt="联系客服" draggable="false" />
    </div>
    <!-- 添加客服企微引导图，要求在整页上下左右居中 -->
    <transition name="demo-support-fade">
      <div v-if="visible" class="demo-support__mask" @click.self="close">
        <div class="demo-support__body">
          <img class="demo-support__img" :src="supportBg" alt="扫码联系官方客服" />
          <!-- 引导图右上角自带关闭按钮视觉，此处仅叠加透明点击热区 -->
          <span class="demo-support__close" @click="close"></span>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import floatIcon from '@/assets/images/float-support-icon.png'
import supportBg from '@/assets/images/example-support-bg.png'

// 记录引导图是否已自动弹出过，仅首次打开页面时默认展示
const GUIDE_SHOWN_KEY = 'demoSupportGuideShown'
// 悬浮图标尺寸与拖动时距视口上下的最小留白
const FLOAT_SIZE = 56
const FLOAT_GAP = 8

// 演示站专用悬浮客服组件，构建时 VITE_DEMO_SUPPORT === 'true' 才会被 Layout 挂载。
// 引导图为透明背景整图（800*300、自带圆角与关闭按钮视觉）且要求全页严格居中，
// 不属于标准表单/详情弹窗，故按弹窗规范例外条款使用局部自定义浮层而非 el-dialog。
export default {
  name: 'DemoSupport',
  setup() {
    // 首次进入页面默认展示引导图并落地标记，此后进入默认关闭
    const firstVisit = !localStorage.getItem(GUIDE_SHOWN_KEY)
    if (firstVisit) {
      localStorage.setItem(GUIDE_SHOWN_KEY, '1')
    }
    const visible = ref(firstVisit)

    function openBox() {
      visible.value = true
    }
    function close() {
      visible.value = false
    }

    // 悬浮图标垂直拖动：按下后跟随鼠标上下移动，避免遮挡页面数据
    const floatBottom = ref(40)
    let dragMoved = false
    let dragStartY = 0
    let dragStartBottom = 0

    // 将 bottom 夹取在视口内，避免图标被拖出或因窗口缩小后跑出可视区域
    function clampBottom(value) {
      const maxBottom = Math.max(window.innerHeight - FLOAT_SIZE - FLOAT_GAP, FLOAT_GAP)
      return Math.min(Math.max(value, FLOAT_GAP), maxBottom)
    }
    function onWindowResize() {
      floatBottom.value = clampBottom(floatBottom.value)
    }

    function onFloatMouseMove(e) {
      const delta = dragStartY - e.clientY
      if (Math.abs(delta) > 3) {
        dragMoved = true
      }
      floatBottom.value = clampBottom(dragStartBottom + delta)
    }
    function onFloatMouseUp() {
      document.removeEventListener('mousemove', onFloatMouseMove)
      document.removeEventListener('mouseup', onFloatMouseUp)
    }
    function onFloatMouseDown(e) {
      // 阻止默认行为，避免拖动过程中选中页面文本
      e.preventDefault()
      dragMoved = false
      dragStartY = e.clientY
      dragStartBottom = floatBottom.value
      document.addEventListener('mousemove', onFloatMouseMove)
      document.addEventListener('mouseup', onFloatMouseUp)
    }
    function onFloatClick() {
      // 拖动结束后触发的 click 不应打开弹窗
      if (dragMoved) {
        dragMoved = false
        return
      }
      openBox()
    }

    onMounted(() => {
      window.addEventListener('resize', onWindowResize)
    })

    onBeforeUnmount(() => {
      onFloatMouseUp()
      window.removeEventListener('resize', onWindowResize)
    })

    return { visible, floatIcon, supportBg, openBox, close, floatBottom, onFloatMouseDown, onFloatClick }
  }
}
</script>

<style lang="scss" scoped>
.demo-support__float {
  position: fixed;
  right: 24px;
  // bottom 由拖动逻辑通过行内样式控制，初始 40px
  z-index: 1999;
  width: 56px;
  height: 56px;
  cursor: pointer;
  user-select: none;
  transition: transform 0.2s;

  &:hover {
    transform: scale(1.08);
  }

  img {
    display: block;
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 4px 12px rgba(20, 98, 255, 0.35));
  }
}

.demo-support__mask {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
}

.demo-support__body {
  position: relative;
  width: 800px;
  max-width: 90vw;
}

.demo-support__img {
  display: block;
  width: 100%;
}

.demo-support__close {
  position: absolute;
  top: 0;
  right: 0;
  width: 56px;
  height: 56px;
  cursor: pointer;
}

.demo-support-fade-enter-active,
.demo-support-fade-leave-active {
  transition: opacity 0.25s;
}

.demo-support-fade-enter,
.demo-support-fade-leave-to {
  opacity: 0;
}
</style>
