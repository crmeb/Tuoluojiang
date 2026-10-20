<template>
  <!--  <div class="sidebar-container" v-if="isShowMenu"></div>-->
    
  <div class="nav-bar" v-if="isShowMenu && currentMenu && currentMenu.children" :style="{ width: !isCollapse ? '204px !important' : '76px !important' }">
    <div v-if="isShow" class="child-bar" :class="!isCollapse ? 'plr12' : ''">
      <!-- <div
        class="over-text1"
        v-if="currentMenu"
        :class="!isCollapse ? 'title-box-open' : 'title-box-close'"
      >
        {{ currentMenu.menu_name }}
      </div> -->
      <!-- <el-scrollbar style="height: 100%;" wrap-class="scrollbar-wrapper"> -->
        <div class="scroll-box">
    
        <el-menu
          :default-active="activeMenu"
          :collapse="isCollapse"
          :background-color="variables.menuBg"
          :text-color="variables.menuText"
          :unique-opened="false"
          :active-text-color="variables.menuActiveText"
          :collapse-transition="false"
          :router="false"
          mode="vertical"
        >
          <template v-if="currentMenu">
            
            <sidebar-item
              v-for="route in currentMenu.children"
              :key="'sidebar' + route.id"
              :item="route"
              :base-path="route.menu_path"
            />
          </template>
        </el-menu>
        </div>
      <!-- </el-scrollbar> -->
    </div>
    <div class="nav-open" @click="clickOutside">
      <i class="el-icon-arrow-left" :class="isCollapse ? 'active' : ''"></i>
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { mapActions, mapState } from 'pinia'
import { useAppStore } from '@/stores/app.ts'
import { useUserStore } from '@/stores/user.ts'
import variables from '@/styles/variables.module.scss'
import { shouldShowSubSidebar } from '@/layout/utils/menu.ts'

// 与 layout/mixin/ResizeHandler 保持一致
const SIDEBAR_RESPONSIVE_WIDTH = 992

export default {
  components: { SidebarItem: defineAsyncComponent(() => import('./SidebarItem')), Logo: defineAsyncComponent(() => import('./Logo')) },
  data() {
    return {
      isShow: false,
      menuName: '',
      windowWidth: document.documentElement.clientWidth
    }
  },
  computed: {
    ...mapState(useAppStore, ['sidebar', 'isClickTab', 'sidebarParentCur']),
    ...mapState(useUserStore, ['menuList']),
    activeMenu() {
      const route = this.$route
      const { meta, path } = route

      if (meta.activeMenu) {
        return meta.activeMenu
      }
      return this.matchVisibleMenuPath(path)
    },
    currentMenuIndex() {
      const index = this.sidebarParentCur
      if (typeof index === 'string' && index.length > 13) {
        return index.slice(13)
      }
      return index
    },
    currentMenu() {
      return this.menuList && this.menuList[this.currentMenuIndex]
    },
    isShowMenu() {
      return shouldShowSubSidebar(this.currentMenu)
    },
    variables() {
      return variables
    },
    isCollapse() {
      // 窗口较小时强制收起二级侧边栏
      if (this.windowWidth < SIDEBAR_RESPONSIVE_WIDTH) {
        return true
      }
      return !this.sidebar.opened
    }
  },
  watch: {
    sidebarParentCur: {
      handler(nVal, oVal) {
        let nVals = nVal
        if (typeof nVal === 'string' && nVal.length > 13) {
          nVals = nVal.slice(13)
        }
        this.isShow = true
        if (this.isClickTab) {
          const currentMenu = this.menuList && this.menuList[nVals]
          const children = Array.isArray(currentMenu && currentMenu.children) ? currentMenu.children : []
          const firstChild = children[0]
          if (
            firstChild &&
            firstChild.children !== undefined &&
            firstChild.children.length
          ) {
            this.setClickTab(false)
            this.$router.push({
              path: firstChild.children[0].menu_path
            })
          } else {
            this.setClickTab(false)
            const menuPath = firstChild ? firstChild.menu_path : ''
            if (menuPath) {
              this.$router.push({
                path: menuPath
              })
            }
          }
        }
      },
      deep: true
    }
  },
  created() {},
  mounted() {
    this.syncSidebarOnResize()
    window.addEventListener('resize', this.syncSidebarOnResize)

    this.$nextTick(() => {
      if (this.sidebarParentCur >= 0) this.isShow = true
    })
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.syncSidebarOnResize)
  },
  methods: {
    ...mapActions(useAppStore, ['setClickTab', 'openSideBar', 'closeSideBar']),
    // 收集当前一级菜单下所有可见的叶子菜单路径
    collectVisiblePaths(menus, result = []) {
      const list = Array.isArray(menus) ? menus : []
      list.forEach((menu) => {
        const children = Array.isArray(menu.children) ? menu.children : []
        if (children.length) {
          this.collectVisiblePaths(children, result)
        } else if (menu.is_show !== 0 && menu.menu_path) {
          result.push(menu.menu_path)
        }
      })
      return result
    },
    // 把当前路由映射成"可见菜单的路径"，用于 el-menu 的 default-active：
    // 1) 新建/编辑等隐藏子路由（如 xxx/form）归到它所属的可见菜单上，菜单保持选中且指向不变；
    // 2) 完全命不中时，回退到同级列表页，避免左侧菜单丢失选中态
    matchVisibleMenuPath(path) {
      const visiblePaths = this.collectVisiblePaths(this.currentMenu && this.currentMenu.children)
      if (!visiblePaths.length) {
        return path
      }
      // 命中多个前缀时取最长的那个，避免 a/index 与 a/index/detail 这类嵌套菜单选错
      const matched = visiblePaths
        .filter((menuPath) => path === menuPath || path.startsWith(menuPath + '/'))
        .sort((a, b) => b.length - a.length)[0]
      if (matched) {
        return matched
      }

      const parentOf = (menuPath) => menuPath.slice(0, menuPath.lastIndexOf('/'))
      let base = path
      while (base.lastIndexOf('/') > 0) {
        base = parentOf(base)
        // 仅回退到同一父路径下的兄弟菜单，避免误高亮其他模块
        const siblings = visiblePaths.filter((menuPath) => parentOf(menuPath) === base)
        if (siblings.length) {
          return siblings.find((menuPath) => menuPath === base + '/index') || siblings[0]
        }
      }
      return path
    },
    // 窗口缩小时自动收起二级侧边栏，同步主内容区布局
    syncSidebarOnResize() {
      this.windowWidth = document.documentElement.clientWidth
      if (this.windowWidth < SIDEBAR_RESPONSIVE_WIDTH && this.sidebar.opened) {
        this.closeSideBar(true)
      }
    },
    clickOutside() {
      if (this.isCollapse) {
        this.openSideBar()
      } else {
        this.closeSideBar(false)
      }
    }
  }
}
</script>
<style lang="scss" scoped>
.nav-bar {
  width: 70px;
  display: flex;
  position: relative;
  height: 100%;
  overflow: hidden;

  &::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0px;
    width: 1px;
    background: #eee;
    pointer-events: none;
  }

  .nav-open {
    width: 10px;
    height: 35px;
    background: #F3F5F9;
    cursor: pointer;
    position: absolute;
    right: 0;
    top: 50%;
    transform: translate(0, -50%);
    z-index: 9;
    border-radius: 4px 0 0 4px;
    display: flex;
    align-items: center;
    i {
      color: #606266;
      font-size: 13px;
      transition: all 0.5s;
      transform-origin: center;
      &.active {
        transform: rotate(-180deg);
      }
    }
  }
}

.scroll-box {
  // 容器（.aside-wrapper）已按视口高度减去顶栏，这里跟随容器即可，不再用 100vh 另起基准
  height: 100%;
  overflow-y: auto;
  padding-bottom: 20px;
   scrollbar-width: none; /* firefox */
  -ms-overflow-style: none; /* IE 10+ */
}

.scroll-box::-webkit-scrollbar {
  height: 0;
  width: 0;
}

.parent-bar {
  width: 100%;
  height: 100%;
  background: #001529;
  .logo {
    padding: 33px 0;
    text-align: center;
    img {
      width: 41px;
      height: 41px;
      border-radius: 50%;
    }
  }
  .parent-nav-item {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    width: 100%;
    margin: 10px 0;
    padding: 10px 0;
    color: #fff;
    font-size: 14px;
    cursor: pointer;
    .icon {
      margin-bottom: 5px;
      font-size: 18px;
    }
    // &.on {
    //   background: #1890ff;
    // }
  }
}
:deep(.el-menu--popup) {
  min-width: 114px;
}
.child-bar {
  width: 100%;
  height: 100%;
  padding: 0 10px;
  

  :deep(.el-menu) {
    border-right: none;
  }
  :deep(.el-menu-item) {
    font-size: 13px !important;
    display: flex;
    align-items: center;
    color: #303133 !important;
  }
  // 选中态统一由 el-menu 的 default-active 驱动（新建页等隐藏路由下不存在 router-link-exact-active），
  // 这里补齐选中态文字色，底色见 styles/sidebar.scss
  :deep(.el-menu-item.is-active) {
    color: #1890ff !important;
  }
  :deep(.el-scrollbar__wrap) {
    overflow-x: hidden;
  }
  :deep(.router-link-exact-active) {
    margin-bottom: 1px;
  }

  :deep(.el-sub-menu__title) {
    font-size: 13px;
  }
  :deep(.el-menu .el-sub-menu .el-sub-menu__title > i) {
    width: auto !important;
  }



  .title-box-open {
    height: 64px;
    line-height: 64px;
    padding-left: 10px;
    // padding: 15px 15px 15px 23px;
  font-family: PingFang SC, PingFang SC;
font-weight: 600;
font-size: 18px;
color: #303133;
  }
  .title-box-close {
    padding: 15px 15px 15px 10px;
    font-size: 13px;
    font-weight: 800;
  }
  .child-nav-item {
    .txt {
      position: relative;
      display: flex;
      align-items: center;
      padding-left: 42px;
      height: 36px;
      font-size: 13px;
      cursor: pointer;
      &.on {
        background: #f0f2f5;
      }
    }
    .link {
      position: relative;
      height: 36px;
      line-height: 36px;
      color: #000 !important;
      font-size: 14px;
      padding-left: 42px;
      // &:hover {
      //   color: #2d8cf0 !important;
      // }
      &.router-link-active {
        background: #f0f2f5;
      }
    }
    .icon {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      margin-right: 10px;
      font-size: 16px;
    }
  }
}
.icon {
  font-size: 18px;
}
</style>
