<template>
  <el-card body-style="padding:0">
    <div class="week-calendar" v-loading="timeTabLoading">
      <div class="calendar-header">
        <div class="calendar-title">
          <span>待办事项</span>
        </div>
        <div class="calendar-actions">
          <span class="today-btn" :class="{ active: isCurrentWeek }" @click="goToToday">今天</span>
        </div>
      </div>
      <div class="week-nav">
        <div class="nav-btn prev" @click="prevWeek">
          <i class="el-icon-arrow-left"></i>
        </div>
        <div class="week-days">
          <div
            v-for="(day, index) in weekDays"
            :key="index"
            class="day-item"
            :class="{ today: day.isToday, selected: day.isSelected }"
            @click="selectDay(day)"
          >
            <span class="week-name">{{ day.weekName }}</span>
            <span class="day-num">{{ day.dayNum }}</span>
          </div>
        </div>
        <div class="nav-btn next" @click="nextWeek">
          <i class="el-icon-arrow-right"></i>
        </div>
      </div>

      <!-- 日程 -->
      <div style="height: 294px; overflow: auto; padding-top: 20px">
        <el-timeline v-if="needList.length > 0">
          <el-timeline-item v-for="(activity, index) in needList" size="normal" :key="index">
            <div class="need-item">
              <span class="text"> {{ activity.title }}</span>
            </div>
          </el-timeline-item>
        </el-timeline>
        <div v-else>
          <default-page :min-height="234" :index="21" :imgWidth="`120px`" />
        </div>
      </div>
    </div>
  </el-card>
</template>

<script>
import moment from 'moment'
import { ref, computed, onMounted } from 'vue'
import { todoListApi, scheduleTypesApi } from '@/api/user'

export default {
  name: 'TodoCalendarCard',
  components: {
    defaultPage: () => import('@/components/common/defaultPage')
  },
  setup() {
    const currentDate = new Date()

    const time = ref(moment(currentDate).format('YYYY-MM-DD'))
    const weekDays = ref([])
    const weekTitle = ref('')
    const currentWeekStart = ref(null)
    const needList = ref([])
    const timeTabLoading = ref(false)
    const scheduleTypes = ref([])
    const types = ref([])

    const isCurrentWeek = computed(() => {
      const today = moment().format('YYYY-MM-DD')
      return weekDays.value.some((day) => day.dateStr === today)
    })

    // 初始化周视图
    function initWeekView(date = new Date()) {
      const startOfWeek = moment(date).startOf('isoWeek')
      currentWeekStart.value = startOfWeek.toDate()
      updateWeekDays()
    }
    // 更新周视图数据
    function updateWeekDays() {
      const today = moment().format('YYYY-MM-DD')
      const selectedDate = moment(time.value).format('YYYY-MM-DD')
      weekDays.value = []
      const weekNames = ['一', '二', '三', '四', '五', '六', '日']
      for (let i = 0; i < 7; i++) {
        const dayDate = moment(currentWeekStart.value).add(i, 'days')
        weekDays.value.push({
          weekName: weekNames[i],
          dayNum: dayDate.format('D'),
          dateStr: dayDate.format('YYYY-MM-DD'),
          isToday: dayDate.format('YYYY-MM-DD') === today,
          isSelected: dayDate.format('YYYY-MM-DD') === selectedDate
        })
      }

      // 更新标题
      const startDate = moment(currentWeekStart.value)
      const endDate = moment(currentWeekStart.value).add(6, 'days')
      weekTitle.value = `${startDate.format('MM月DD日')}-${endDate.format('MM月DD日')}`
    }
    // 上一周
    function prevWeek() {
      currentWeekStart.value = moment(currentWeekStart.value).subtract(7, 'days').toDate()
      updateWeekDays()
    }
    // 下一周
    function nextWeek() {
      currentWeekStart.value = moment(currentWeekStart.value).add(7, 'days').toDate()
      updateWeekDays()
    }
    // 跳转到今天
    function goToToday() {
      time.value = moment().format('YYYY-MM-DD')
      initWeekView()
      getDailyTodoInfo(time.value, true)
    }
    // 选择日期
    function selectDay(day) {
      time.value = day.dateStr
      updateWeekDays()
      getDailyTodoInfo(day.dateStr, true)
    }
    function getDailyTodoInfo(time, load = false) {
      if (load) {
        timeTabLoading.value = true
      }
      const data = {
        time,
        page: 1,
        limit: 4
      }
      todoListApi(data)
        .then((res) => {
          needList.value = res.data.list
        })
        .finally(() => {
          if (load) {
            timeTabLoading.value = false
          }
        })
    }
    async function getTypes() {
      const result = await scheduleTypesApi()
      scheduleTypes.value = result.data
      result.data.forEach((value) => {
        types.value.push(value.id)
      })
      await getDailyTodoInfo(moment(new Date()).format('YYYY-MM-DD'))
    }

    onMounted(() => {
      // 初始化周视图
      initWeekView()
      getTypes()
    })

    return {
      time,
      weekDays,
      weekTitle,
      currentWeekStart,
      needList,
      timeTabLoading,
      scheduleTypes,
      types,
      isCurrentWeek,
      initWeekView,
      updateWeekDays,
      prevWeek,
      nextWeek,
      goToToday,
      selectDay,
      getDailyTodoInfo,
      getTypes
    }
  }
}
</script>

<style lang="scss" scoped>
@import './card-common';

.need-item {
  display: flex;
  flex-direction: column;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  padding-right: 4px;

  .text {
    line-height: 1.5;
    font-size: 13px;
    color: #303133;
    margin-top: -6px;
  }
}

::v-deep .el-timeline {
  padding: 0;
  .el-timeline-item__node--normal {
    position: absolute;
    width: 6px;
    height: 6px;
    background-color: #dcdfe6;
    left: 0;
  }
  .el-timeline-item__tail {
    position: absolute;
    left: 2px;
    border-left: 1px dashed #dfe4ed;
  }
  .el-timeline-item__wrapper {
    position: relative;
    top: -1px;
    padding-left: 18px !important;
  }
}

// 周视图日历样式
.week-calendar {
  padding: 16px;
  height: 414px;
  .calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;

    .calendar-title {
      display: flex;
      align-items: center;
      font-family: PingFang SC, PingFang SC;
      font-weight: 500;
      font-size: 16px;
      color: #303133;

      .iconfont {
        margin-right: 6px;
        color: #1890ff;
      }
    }

    .calendar-actions {
      .today-btn {
        font-size: 12px;
        color: #909399;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 4px;
        transition: all 0.3s;

        &:hover {
          color: #1890ff;
          background: #f0f7ff;
        }

        &.active {
          color: #1890ff;
          font-weight: 500;
        }
      }
    }
  }

  .week-nav {
    display: flex;
    align-items: center;

    .nav-btn {
      width: 20px;
      height: 20px;
      background: #f5f5f5;
      border-radius: 4px 4px 4px 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #909399;
      border-radius: 4px;
      transition: all 0.3s;
      flex-shrink: 0;

      &:hover {
        color: #1890ff;
        background: #f0f7ff;
      }

      &.disabled {
        color: #c0c4cc;
        cursor: not-allowed;

        &:hover {
          background: none;
        }
      }
    }

    .week-days {
      flex: 1;
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 4px;

      .day-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 8px 4px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;

        .week-name {
          font-family: PingFang SC, PingFang SC;
          font-weight: 400;
          font-size: 13px;
          color: #9e9e9e;
          margin-bottom: 4px;
        }

        .day-num {
          width: 22px;
          height: 22px;
          border-radius: 4px 4px 4px 4px;
          font-family: D-DIN-PRO, D-DIN-PRO;
          font-weight: 600;
          font-size: 12px;
          color: #303133;
          text-align: center;
          line-height: 22px;
          margin-top: 6px;
        }

        &:hover {
          background: #f5f7fa;
        }
        &.selected {
          .day-num {
            color: #1890ff;
          }
        }

        &.today {
          .day-num {
            background: #1890ff;
            color: #fff !important;
          }
        }
      }
    }
  }
}
</style>
