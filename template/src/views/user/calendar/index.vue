<!-- 办公-我的日程/我的待办 Tab 容器 -->
<template>
  <div class="divBox">
    <el-card body-style="padding:0;" class="normal-page">
      <div class="page-header">
        <div class="title-16">我的日程</div>
        <!-- <div class="tab-bar">
          <div class="tab-item" :class="{ active: activeTab === 'calendar' }" @click="activeTab = 'calendar'">
            我的日程
          </div> -->
        <!-- <div class="tab-item" :class="{ active: activeTab === 'todo' }" @click="activeTab = 'todo'">
            我的待办<span v-if="todoCount" class="tab-count">·{{ todoCount }}</span>
          </div> -->
        <!-- </div> -->
        <el-button
          v-if="activeTab === 'calendar'"
          type="primary"
          icon="el-icon-plus"
          @click="handleAddSchedule"
        >
          新建日程
        </el-button>
      </div>
      <my-calendar v-show="activeTab === 'calendar'" ref="calendarRef" />
      <!-- <my-todo v-show="activeTab === 'todo'" ref="todoRef" /> -->
    </el-card>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import MyCalendar from './components/MyCalendar.vue'
// import MyTodo from './components/MyTodo.vue'

export default {
  name: 'WorkDealt',
  components: { MyCalendar },
  setup() {
    const activeTab = ref('calendar')
    const calendarRef = ref(null)
    const todoRef = ref(null)
    const todoCount = computed(() => todoRef.value?.totalCount ?? 0)

    function handleAddSchedule() {
      calendarRef.value?.addSchedule()
    }

    watch(activeTab, (val) => {
      if (val === 'calendar') {
        calendarRef.value?.refresh()
      }
    })

    return {
      activeTab,
      calendarRef,
      todoRef,
      todoCount,
      handleAddSchedule
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 20px;
  border-bottom: 1px solid #eeeeee;
  min-height: 60px;
}

.tab-bar {
  display: flex;
  gap: 40px;
}

.tab-item {
  position: relative;
  padding: 6px 2px 14px;
  font-size: 15px;
  font-weight: 500;
  color: #606266;
  line-height: 20px;
  cursor: pointer;
  transition: color 0.2s;

  &:hover {
    color: #1890ff;
  }

  &.active {
    color: #1890ff;
    &::after {
      content: '';
      position: absolute;
      width: 59px;
      left: 50%;
      transform: translateX(-50%);
      bottom: -3px;
      height: 2px;
      background: #1890ff;
      border-radius: 1px;
    }
  }
}

.tab-count {
  margin-left: 2px;
  font-weight: 500;
}
</style>
