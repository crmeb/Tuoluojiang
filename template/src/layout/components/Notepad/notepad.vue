<template>
  <div class="notepad">
    <el-tooltip content="记事本" effect="dark" placement="bottom">
      <i class="iconfont iconjishiben3 pointer notepad" @click="handleNotepad"></i>
    </el-tooltip>
    <el-drawer
      title="记事本"
      :with-header="false"
      :visible.sync="drawer"
      direction="rtl"
      :before-close="handleBeforeClose"
      :size="drawerSize"
      :append-to-body="true"
      :modal="true"
      :wrapper-closable="true"
    >
      <memorandum
        ref="memorandum"
        :style="style1"
        :fullscreen="fullscreen"
        @toggleFullscreen="toggleFullscreen"
        @handleClose="handleClose"
      ></memorandum>
    </el-drawer>
  </div>
</template>
<script>
export default {
  name: 'Notepad',
  components: {
    memorandum: () => import('@/views/user/memorandum/index')
  },
  data() {
    return {
      drawer: false,
      style1: '1',
      fullscreen: false // 抽屉是否全屏展示（宽度铺满视口）
    }
  },
  computed: {
    // 抽屉宽度：全屏时铺满视口，否则保留两侧留白
    drawerSize() {
      return this.fullscreen ? '100%' : '90%'
    }
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
    openBox() {
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
::v-deep .el-drawer__header {
  border-bottom: 1px solid #eeeeee;
  padding: 0 24px;
  height: 50px;
  line-height: 20px;
  padding-right: 14px;
}

::v-deep .el-drawer__body {
  padding-bottom: 0;
  overflow-y: hidden !important;
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
::v-deep .el-popover {
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
