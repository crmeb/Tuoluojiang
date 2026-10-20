<template>
  <div v-if="!item.hidden">
    <template
      v-if="
        hasOneShowingChild(item.children, item) &&
        (!onlyOneChild.children || onlyOneChild.noShowingChildren) &&
        !item.alwaysShow
      "
    >
      <app-link v-if="onlyOneChild" :to="resolvePath(onlyOneChild.menu_path, 0)">
        <el-menu-item
          :index="resolvePath(onlyOneChild.menu_path, 0)"
          :class="{ 'submenu-title-noDropdown': !isNest }"
          v-show="item.is_show"
        >

          <i v-if="onlyOneChild.icon" :class="getIconCss(onlyOneChild.icon)"></i>
          <template #title>
            <span class="overText" >{{ item.menu_name }}</span>
          </template>
        </el-menu-item>
      </app-link>
    </template>

    <el-sub-menu
      v-else-if="item.is_show && hasVisibleChild(item)"
      ref="subMenu"
      :index="resolvePath(item.menu_path, 1)"
      teleported
      popper-class="sidebar-menu-popper"
      :popper-offset="isNest ? 6 : 22"
    >
      <template #title>
        <i v-if="item.icon" :class="getIconCss(item.icon,item)"></i>
        <span >{{ item.menu_name }}</span>
      </template>

      <sidebar-item
        v-for="child in item.children"
        :key="'sidebars' + child.id"
        :is-nest="true"
        :item="child"
        :base-path="child.menu_path"
        v-show="child.is_show"
        class="nest-menu"
      />
    </el-sub-menu>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { resolve } from '@/utils/path.ts'
import { isExternal } from '@/utils/validate.ts'
import FixiOSBug from './FixiOSBug.ts'

export default {
  name: 'SidebarItem',
  components: { AppLink: defineAsyncComponent(() => import('./Link')) },
  mixins: [FixiOSBug],
  props: {
    // route object
    item: {
      type: Object,
      required: true
    },
    isNest: {
      type: Boolean,
      default: false
    },
    basePath: {
      type: String,
      default: ''
    }
  },
  data() {
    // To fix https://github.com/PanJiaChen/vue-admin-template/issues/237
    // TODO: refactor with render function
    this.onlyOneChild = null
    return {}
  },
  methods: {
    // 递归判断子菜单下是否存在可见(is_show)的菜单项，避免子项全部隐藏时仍渲染空的父级菜单
    hasVisibleChild(item) {
      const children = item.children || []
      if (children.length === 0) {
        return !!item.is_show
      }
      return children.some((child) => this.hasVisibleChild(child))
    },
    hasOneShowingChild(children = [], parent) {
      let len = 0
      const showingChildren = children.filter((item) => {
        if (item.position === 1) {
          len++
        }
        if (item.hidden) {
          return false
        } else {
          // 测试
          // Temp set(will be used if only has one showing child)
          this.onlyOneChild = item
          return true
        }
      })

      if (showingChildren.length === 0) {
        this.onlyOneChild = { ...parent, path: '', noShowingChildren: true }
        return true
      }

      return false
    },
    keyClass(item) {
      if (item.top_position && item.top_position.length > 0) {
        if (this.$route.path.indexOf(item.menu_path)) {
          return 'router-link-exact-active'
        }
      }
    },
    
    getIconCss(cssName,item) {
      if (/^el-icon-/.test(cssName)) {
        return [cssName]
      } else {
        return ['iconfont', cssName]
      }
    },

    resolvePath(routePath, index) {
      if (isExternal(routePath)) {
        return routePath
      }
      if (isExternal(this.basePath)) {
        return this.basePath
      }
      if (index == 1) {
        return resolve(this.basePath, routePath)
      } else {
        return this.basePath
      }
    }
  }
}
</script>
<style lang="scss" scoped>
:deep(.el-sub-menu__icon-arrow) {
  position: absolute;
  right: 15px;
  top: 55%;
  color: #909399;
}
.iconfont {
  font-size: 20px;
  color: #606266;
}
:deep(.el-menu) {
background-color: transparent;
.el-sub-menu__title {
  background-attachment: scroll;
}
}

:deep(.el-menu-item) {
  padding: 0 10px !important;
}
:deep(.el-sub-menu__title:hover)  {
background: #F3F5F9 !important;
border-radius: 8px 8px 8px 8px;

}
  :deep(.router-link-exact-active.router-link-active li) {
    background-color: #EDF6FF !important;
    color: #1890ff !important;
    border-radius: 8px;
   
  }
.overText {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

// 菜单缩进样式已移至全局 styles/sidebar.scss
</style>
