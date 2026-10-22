<template>
  <div class="box">
    <div class="box-height workbench-page">
      <!-- 第一行：顶部问候 + 欢迎卡片 -->
      <el-row :gutter="14" type="flex">
        <el-col :span="16">
          <header-need />
        </el-col>
        <el-col :span="8">
          <header-pic />
        </el-col>
      </el-row>

      <!-- 第二行：业绩统计 + 常用功能 / 待办事项 -->
      <el-row class="mt14" type="flex" :gutter="14">
        <el-col :span="16">
          <achievement />
          <quick />
        </el-col>
        <el-col :span="8">
          <todo-calendar />
        </el-col>
      </el-row>

      <!-- 第三行：当前考核 / 系统通知 / 企业动态 -->
      <el-row :gutter="14" class="mt14">
        <el-col :span="8" v-show="assessVisible">
          <assess @assess-ready="assessVisible = $event" />
        </el-col>
        <el-col :span="systemNoticeSpan">
          <system-notice />
        </el-col>
        <el-col :span="8" v-show="enterpriseNoticeVisible !== false">
          <enterprise-notice @news-ready="enterpriseNoticeVisible = $event" />
        </el-col>
      </el-row>

      <password />

      <copyright />
    </div>
  </div>
</template>
<script>
import { ref, computed } from 'vue'
import { useWorkbenchTasks } from './composables/useWorkbenchTasks'

export default {
  name: 'Workbench',
  components: {
    headerNeed: () => import('./components/cards/headerNeed'),
    headerPic: () => import('./components/cards/headerPic'),
    achievement: () => import('./components/cards/achievement'),
    quick: () => import('./components/cards/quick'),
    todoCalendar: () => import('./components/cards/todoCalendar'),
    assess: () => import('./components/cards/assess'),
    systemNotice: () => import('./components/cards/systemNotice'),
    enterpriseNotice: () => import('./components/cards/enterpriseNotice'),
    password: () => import('./components/password'),
    copyright: () => import('@/layout/components/copyright.vue')
  },
  setup() {
    // 第三行动态布局状态（由对应卡片上报）
    const assessVisible = ref(false)
    // null 表示企业动态尚在加载：此时按「会展示」占位，让骨架屏撑住那一列
    const enterpriseNoticeVisible = ref(null)

    // 系统通知在第三行的 span：按「当前考核 / 企业动态」是否展示动态分配
    const systemNoticeSpan = computed(() => {
      if (enterpriseNoticeVisible.value === false) {
        return assessVisible.value ? 16 : 24
      }
      return assessVisible.value ? 8 : 16
    })

    // 授权提醒 / 审批配置 / 邀请链接等页面级一次性任务
    useWorkbenchTasks()

    return {
      assessVisible,
      enterpriseNoticeVisible,
      systemNoticeSpan
    }
  }
}
</script>
<style lang="scss" scoped>
.box {
  margin: 14px;
  margin-top: 1px;
  box-sizing: border-box;
  ::v-deep .divBox {
    padding: 0;
  }
}

.workbench-page {
  min-height: calc(100vh - 92px);
}
</style>
