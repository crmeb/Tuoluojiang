<template>
  <div class="breadcrumb-content flex lh-center">
    <div v-if="hasBrand" class="logo-wrapper">
      <img v-if="brandLogo" :src="brandLogo" alt="" />
      <span v-if="enterpriseName" class="enterprise-name" v-show="sidebar.opened">{{ enterpriseName }}</span>
    </div>
    <el-breadcrumb class="breadcrumb-list" separator-icon="el-icon-arrow-right">
      <el-breadcrumb-item v-for="(item, index) in levaList" :key="index"> {{ item }}</el-breadcrumb-item>
    </el-breadcrumb>
  </div>
</template>

<script>
import { roterPre } from '@/settings.ts'
import { mapState } from 'pinia'
import { useAppStore } from '@/stores/app.ts'
import { useUserStore } from '@/stores/user.ts'
import { useAppConfigStore } from '@/stores/appConfig.ts'
export default {
  name: 'Breadcrumb',
  data() {
    return {
      levaList: [],
      path: ''
    }
  },
   computed: {
    ...mapState(useAppStore, ['sidebar']),
    ...mapState(useUserStore, ['menuList', 'enterprise']),
    ...mapState(useAppConfigStore, { webConfig: 'configData' }),
    brandLogo() {
      return this.enterprise?.logo || this.webConfig?.site_logo || '';
    },
    enterpriseName() {
      const name = this.enterprise?.enterprise_name || this.webConfig?.enterprise_name || '';
      return name.length > 14 ? name.slice(0, 13) + '…' : name;
    },
    hasBrand() {
      return Boolean(this.brandLogo || this.enterpriseName);
    },
  },
  watch: {
    $route(to, from) {
      this.getBreadcrumb()
    }
  },
  mounted() {
    this.getBreadcrumb()
  },
  methods: {
    getBreadcrumb() {
      let newPath = this.$route.fullPath
      newPath = newPath.split('?')[0]
      // 获取缓存菜单
      let routerInfo = this.menuList
      if (newPath == `${roterPre}/search`) {
        this.levaList = ['搜索']
        return
      }
      if (!routerInfo) return

      let stack = this.find(routerInfo, newPath)
      // 新增 / 编辑 / 详情等二级页不在后台菜单里，直接查会得到空面包屑；
      // 此时回退到 meta.activeMenu 指定的所属菜单，拼出父级路径后再追加本页标题
      if (!stack.length) {
        const activeMenu = this.$route.meta && this.$route.meta.activeMenu
        if (activeMenu) {
          stack = this.find(routerInfo, activeMenu.split('?')[0])
        }
        const title = this.getMetaTitle()
        if (title) stack.push(title)
      }

      this.levaList = stack
      localStorage.setItem('navTitle', JSON.stringify(stack))
    },
    // meta.title 支持字符串，也支持函数形式（如新增 / 编辑共用一条路由时按 query 区分）
    getMetaTitle() {
      const title = this.$route.meta && this.$route.meta.title
      return typeof title === 'function' ? title(this.$route) : title || ''
    },
    find(array, path) {
      let stack = []
      let going = true
      let walker = (array, path) => {
        array.forEach((item) => {
          if (!going) return
          stack.push(item['menu_name'])
          if (item['menu_path'] === path) {
            going = false
          } else if (item['children']) {
            walker(item['children'], path)
          } else {
            stack.pop()
          }
        })
        if (going) stack.pop()
      }
      walker(array, path)

      return stack
    }
  }
}
</script>

<style lang="scss" scoped>
.breadcrumb-content {
  flex: 1;
  min-width: 0;
  padding-left: 16px;
  overflow: hidden;
}

:deep(.el-breadcrumb__item:last-child .el-breadcrumb__inner) {
  color: #303133;
  font-family: PingFang SC-Regular, PingFang SC;
  font-weight: 400;
  font-size: 13px;
}
:deep(.el-breadcrumb__inner) {
  font-size: 13px;
  font-family: PingFang SC-Regular, PingFang SC;
  font-weight: 400;
  color:#909399;
}

.breadcrumb-list {
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
}

.logo-wrapper {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  margin-right: 24px;
  .enterprise-name {
    max-width: 190px;
    margin-left: 10px;
    overflow: hidden;
    color: #000919;
    font-family: PingFang SC-Regular, PingFang SC;
    font-size: 15px;
    font-weight: 500;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  img {
    flex-shrink: 0;
    width: 43px;
    height: 40px;
    object-fit: cover;
  }
}
</style>
