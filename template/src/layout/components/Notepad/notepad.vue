<template>
  <div class="notepad">
    <el-tooltip content="记事本" effect="dark" placement="bottom">
      <i class="iconfont iconjishiben3 pointer notepad" @click="handleNotepad"></i>
    </el-tooltip>
    <el-drawer
      class="notepad-drawer"
      title="记事本"
      :with-header="false"
      v-model="drawer"
      direction="rtl"
      :before-close="handleBeforeClose"
      :size="drawerSize"
      :append-to-body="true"
      :modal="true"
    >
      <memorandum
        ref="memorandum"
        :style="style1"
        :fullscreen="fullscreen"
        @toggleFullscreen="toggleFullscreen"
        @handleClose="handleClose"
      ></memorandum>
      <!-- 抽屉左缘拖宽手柄：按住左右拖动调整抽屉宽度，松手记忆 -->
      <span
        v-show="drawer"
        class="drawer-resizer"
        :class="{ 'is-resizing': resizing }"
        title="拖动调整宽度"
        @mousedown="startResize"
      />
    </el-drawer>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
// 本地缓存 key：抽屉拖拽后的宽度（px），下次打开自动恢复
const NOTEPAD_DRAWER_SIZE_KEY = 'notepad_drawer_width'

// 抽屉宽度下限（px）：低于此值左栏与正文都放不下；上限为视口宽
const DRAWER_MIN_WIDTH = 600

export default {
  name: 'Notepad',
  components: {
    memorandum: defineAsyncComponent(() => import('@/views/user/memorandum/index'))
  },
  data() {
    return {
      drawer: false,
      style1: '1',
      fullscreen: false, // 抽屉是否全屏展示（宽度铺满视口）
      sizePx: null, // 拖拽后的抽屉宽度（px）；null 表示未拖过，走默认 70%
      resizing: false, // 是否正在拖拽调宽
      resizeStartX: 0,
      resizeStartWidth: 0
    }
  },
  computed: {
    // 抽屉宽度：拖拽过用记忆像素，否则默认 70%；全屏时铺满视口
    drawerSize() {
      // 记事本是可调宽编辑工具，保留 develop 的 70% 默认值与全屏模式。
      if (this.fullscreen) return '100%'
      return this.sizePx ? `${this.sizePx}px` : '70%'
    }
  },
  beforeUnmount() {
    document.removeEventListener('mousemove', this.onResizing)
    document.removeEventListener('mouseup', this.stopResize)
  },
  methods: {
    // 去记事本
    handleNotepad() {
      if (this.$route.name === 'user-memorandum') return
      this.openBox()
    },
    // 全屏 / 退出全屏切换：只切抽屉宽度，图标由子组件按状态渲染
    toggleFullscreen() {
      this.fullscreen = !this.fullscreen
    },
    // 打开前恢复记忆宽度：视口变小后超出的收敛到视口宽，避免打开放不下
    restoreDrawerSize() {
      const saved = Number(localStorage.getItem(NOTEPAD_DRAWER_SIZE_KEY)) || 0
      if (!saved) return
      this.sizePx = Math.round(Math.min(window.innerWidth, Math.max(DRAWER_MIN_WIDTH, saved)))
    },
    // 抽屉左缘拖宽：按下后监听全局 mousemove/mouseup，向左拖变宽
    startResize(event) {
      if (this.fullscreen) return
      event.preventDefault()
      this.resizing = true
      this.resizeStartX = event.clientX
      // 首次拖动把默认 70% 换算成像素作为拖拽基准
      this.resizeStartWidth = this.sizePx || window.innerWidth * 0.7
      document.addEventListener('mousemove', this.onResizing)
      document.addEventListener('mouseup', this.stopResize)
    },
    onResizing(event) {
      if (!this.resizing) return
      event.preventDefault()
      const width = this.resizeStartWidth + (this.resizeStartX - event.clientX)
      this.sizePx = Math.round(Math.min(window.innerWidth, Math.max(DRAWER_MIN_WIDTH, width)))
    },
    stopResize() {
      if (!this.resizing) return
      this.resizing = false
      document.removeEventListener('mousemove', this.onResizing)
      document.removeEventListener('mouseup', this.stopResize)
      // 松手才落缓存：拖动过程不反复写 localStorage
      localStorage.setItem(NOTEPAD_DRAWER_SIZE_KEY, String(this.sizePx))
    },
    openBox() {
      this.restoreDrawerSize()
      this.drawer = true
      setTimeout(() => {
        const child = this.$refs.memorandum
        if (!child) return
        // 只恢复快捷键监听；自动保存倒计时由子组件在「首次改动」时懒启动
        child.createdEvent()
      }, 300)
    },
    // 遮罩/ESC 关闭：交给子组件判断未保存修改，确认后由子组件 emit handleClose 真正关闭
    handleBeforeClose() {
      const child = this.$refs.memorandum
      if (!child) {
        this.drawer = false
        return
      }
      child.handleClose()
    },
    handleClose() {
      this.stopResize()
      this.drawer = false
      // 关闭后回到默认宽度：下次打开是常规抽屉，不会残留全屏态
      this.fullscreen = false
      const child = this.$refs.memorandum
      if (!child) return
      child.handleRemove()
      // 抽屉只是 v-show 隐藏、组件不销毁：必须显式停掉自动保存，否则关了还在后台写库
      child.stopAutoSave()
    }
  }
}
</script>

<style lang="scss" scoped>

// 抽屉左缘拖宽手柄：hover / 拖拽中高亮，样式与记事本内部左栏分隔条一致
.drawer-resizer {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 10;
  width: 6px;
  cursor: col-resize;
  transition: background-color 0.2s ease;

  &:hover,
  &.is-resizing {
    background-color: rgba(24, 144, 255, 0.25);
  }
}
.divBox {
  margin: 0px;
  padding-top: 0;
}
.tab-box {
  display: flex;
  align-items: center;
  .tab-item {
    margin-right: 30px;
    font-size: 14px;
    color: #000000;
    cursor: pointer;
    &.on {
      color: #1890ff;
    }
  }
}
.form-wrapper {
  padding-right: 63px;

  .form-box {
    display: flex;
    flex-wrap: wrap;
    margin-top: 40px;
    .form-item {
      width: 50%;
    }
  }
}
.tips-txt {
  font-size: 13px;
  color: #999999;
}
.table-box {
  padding: 0 13px;
  .table-item {
    padding-bottom: 29px;
    border-bottom: 1px dashed rgba(102, 102, 102, 0.3);
    .title {
      margin: 20px 0 14px;
      padding-left: 10px;
      border-left: 2px solid #1890ff;
      font-size: 14px;
    }
  }
}
.add-btn {
  margin-top: 15px;
}
.item-box {
  display: flex;
  flex-wrap: wrap;
  .item {
    margin-left: 0 !important;
    margin-right: 10px;
    margin-bottom: 10px;
  }
}
:deep(.el-popover) {
  min-width: 166px;
}
.prop-txt {
  width: 166px;
  margin-right: 10px;
  height: 30px;
  line-height: 30px;
  font-size: 13px;
  cursor: pointer;
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.notepad-drawer .el-drawer__header {
  border-bottom: 1px solid #eeeeee;
  padding: 0 24px;
  height: 50px;
  line-height: 20px;
  padding-right: 14px;
}
.notepad-drawer .el-drawer__body {
  position: relative;
  padding-bottom: 0;
  overflow-y: hidden !important;
}
</style>
