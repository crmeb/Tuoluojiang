<template>
  <div class="divBox">
    <el-card body-style="padding: 0;" class="normal-page">
      <div class="page-header">
        <div class="submission-tab">
          <el-tabs v-model="activeTab" @tab-click="handleTabClick">
            <el-tab-pane :label="`我的待办·${todoCount}`" name="todo" />
            <el-tab-pane label="我的日程" name="calendar" />
          </el-tabs>
        </div>
      </div>

      <my-todo
        v-show="activeTab === 'todo'"
        ref="todoRef"
        default-category="schedule"
        @count-change="todoCount = $event"
      />
      <my-calendar v-show="activeTab === 'calendar'" ref="calendarRef" show-add-button />
    </el-card>
  </div>
</template>
<script>
import MyTodo from '@/views/user/calendar/components/MyTodo.vue'
import MyCalendar from '@/views/user/calendar/components/MyCalendar.vue'

export default {
  components: {
    MyTodo,
    MyCalendar
  },
  data() {
    return {
      activeTab: 'todo',
      todoCount: 0
    }
  },
  methods: {
    handleTabClick(tab) {
      if (tab.paneName !== 'calendar') return

      this.$nextTick(() => {
        this.$refs.calendarRef.refresh()
      })
    }
  }
}
</script>
<style lang="scss" scoped>
.normal-page {
  height: calc(100vh - 80px);
  overflow-x: hidden;
}

.page-header {
  min-height: 60px;
  padding: 20px 20px 0;
  border-bottom: 1px solid #eeeeee;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.submission-tab {
  :deep(.el-tabs__header) {
    margin-bottom: 0;
  }

  :deep(.el-tabs__nav-wrap::after) {
    height: 0;
  }
}

:deep(.my-todo) {
  height: calc(100vh - 140px);
}
</style>
