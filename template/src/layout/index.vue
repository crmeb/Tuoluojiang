<template>
  <div>
    <div :class="classObj" style="box-sizing: border-box;">
   
      <!-- 左侧菜单容器：一级 + 二级共用同一个 fixed 容器，背景由容器统一绘制 -->
      <div class="aside-wrapper">
        <!-- 左侧一级菜单 -->
        <navbar :type="type" />
        <!-- 左侧二级菜单 -->
        <sidebar class="sidebar-container" v-show="!sideShow" />
      </div>
      <!-- 中间区域 -->
      <div :class="['main-container', helpShow ? 'left' : '']">
        <!-- 顶部工具栏 -->
        <headerToolbar :helpIsShow="helpShow" ref="header" :searchShow="searchShow" @handleSearchConfirm="handleConfirm"  @openOrCloseHelp="openHelp" ></headerToolbar>

        <!-- 中间内容区域 -->
        <app-main />
      </div>
      <!-- 帮助侧边栏 -->
      <help v-if="helpShow" :helpShow="helpShow" @closeHelp="closeHelp"></help>
    </div>
    <!-- 消息组建 -->
   <message-handle-popup ref="messageHandlePopup" :detail="detail"></message-handle-popup>
    <!-- 演示站悬浮客服（构建时由 VITE_DEMO_SUPPORT 环境变量控制开关） -->
    <demo-support v-if="isDemoSupport" />
  </div>
</template>
<script>
import { AppMain, Navbar, Sidebar } from './components'
import ResizeMixin from './mixin/ResizeHandler'
import { roterPre } from '@/settings'
import { mapGetters, mapState } from 'vuex'
import { EventBus } from '@/libs/bus'
import { DEMO_SUPPORT } from '@/config/env'
import { shouldShowSubSidebar } from './utils/menu'
function normalizeSidebarIndex(index) {
  if (typeof index === 'string' && index.length > 13) {
    return index.slice(13)
  }
  return index
}
export default {
  name: 'Layout',
  components: {
    AppMain,
    Navbar,
    Sidebar,
    headerToolbar: () => import('./components/Header/Toolbar'),
    // breadcrumb: () => import('./components/Header/breadcrumb'),
    help: () => import('./components/Document/help'),
    messageHandlePopup: () => import('@/components/common/messageHandlePopup'),
    demoSupport: () => import('@/components/demoSupport')
  },
  mixins: [ResizeMixin],
  computed: {
    ...mapState({
      sidebar: (state) => state.app.sidebar,
      word: (state) => state.app.keyWord,
      device: (state) => state.app.device,
      fixedHeader: (state) => state.settings.fixedHeader,
      sidebarType: (state) => state.app.sidebarType
    }),
    ...mapGetters(['menuList', 'sidebarParentCur']),
    // 与 Sidebar 的 isShowMenu 共用同一套判定，避免菜单隐藏了但内容区没跟着左移
    isSidebarShow() {
      const sidebarData = this.menuList && this.menuList[normalizeSidebarIndex(this.sidebarParentCur)]
      return shouldShowSubSidebar(sidebarData)
    },
    classObj() {
      return {
        hideSidebar: !this.sidebar.opened && this.isSidebarShow,
        openSidebar: this.sidebar.opened && this.isSidebarShow,
        showSidebar: !this.isSidebarShow
      }
    }
  },

  watch: {
    $route(to, from) {
      let data = {
        list: [],
        type: '',
        additional_search_boolean: '1'
      }
      this.$store.commit('uadatefieldOptions', data)
      this.$store.state.business.conditionDialog = false
      if (to.path == `${roterPre}/user/resume` || to.path == `${roterPre}/user/news/subscribe`) {
        this.sideShow = true
        this.helpShow = false
      } else {
        this.sideShow = false
      }
    },

    sidebarType: {
      handler(nVal, oVal) {
        if (nVal) {
          this.widthNum = 'calc(100% - 270px)'
        } else {
          this.widthNum = 'calc(100% - 70px)'
        }
      },
      deep: true
    },
    word: {
      handler(nVal, oVal) {
        if (nVal) {
          this.keyword = nVal
        }
      },
      deep: true
    }
  },
  data() {
    return {
      widthNum: 'calc(100% - 70px)',
      helpShow: false,
      searchShow: false,
      type: 2,
      keyword: '',
      detail: {},
      sideShow: false,
      isDemoSupport: DEMO_SUPPORT
    }
  },
  created() {
    EventBus.$on('messageSuccess', this.handleMessageSuccess)
    if (
      this.$route.fullPath == `${roterPre}/user/resume` ||
      this.$route.fullPath == `${roterPre}/user/news/subscribe`
    ) {
      this.sideShow = true
    } else {
      this.sideShow = false
    }
  },
  mounted() {
    this.widthNum = this.sidebarType ? 'calc(100% - 152px)' : 'calc(100% - 70px)'
  },
  beforeDestroy() {
    EventBus.$off('messageSuccess', this.handleMessageSuccess)
  },
  methods: {
    async handleMessageSuccess(e) {
      this.detail = e.detail
      await this.$nextTick()
      this.$refs.messageHandlePopup.openMessage(e.item)
    },
    handleClickOutside() {
      this.$store.dispatch('app/closeSideBar', { withoutAnimation: false })
    },
    openHelp(isshow) {
      this.helpShow = isshow
    },
    closeHelp() {
      this.helpShow = false
      this.$refs.header.helpShow = false
    },
    handleConfirm(keyword) {
      this.$router.push({
        path: `${roterPre}/search`,
        query: { keyword: keyword }
      })

      this.searchShow = false
    }
  }
}
</script>

<style lang="scss" scoped>
@import '@/styles/mixin.scss';
@import '@/styles/variables.scss';

.flex {
  display: flex;
  align-items: center;
}

.app-wrapper {
  @include clearfix;
  position: relative;
  height: 100%;
  width: 100%;

  &.mobile.openSidebar {
    position: fixed;
    top: 0;
  }
}

//二级菜单展开
.openSidebar {
  .breadcrumb {
    padding-left: 0px !important;
  }
  .main-container {
    // 菜单容器左侧留了 10px 间距，内容区同步右移，避免被菜单压住
    margin-left: 290px !important;
  }
}
//二级菜单缩进
.hideSidebar {
  .breadcrumb {
    // padding-left: 170px !important;
  }
  .main-container {
    margin-left: 162px !important;
  }
}
//二级菜单隐藏
.showSidebar {
  // .breadcrumb {
  //   padding-left: 100px !important;
  // }
  .main-container {
    margin-left: 86px !important;
  }
}

@keyframes fadenum {
  0% {
    margin-right: 0px;
  }
  100% {
    margin-right: 360px;
  }
}
.left {
  animation: fadenum 0.5s;
  animation-fill-mode: forwards;
}
.el-icon-search {
  color: #909399;
}
.top {
  ::v-deep .el-button--text {
    color: #909399;
  }
}
</style>
