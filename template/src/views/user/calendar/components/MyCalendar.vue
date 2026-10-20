<!-- 办公-我的日程 Tab 内容 -->
<template>
  <div class="my-calendar">
    <div class="calendar-layout">
      <div class="calendar-sidebar">
        <calendar-bar ref="calendarBar" @handleDate="handleDate" />
      </div>
      <div class="calendar-main line" v-loading="loading" element-loading-text="数据正在加载中">
        <div class="plan-tabs-content"></div>
        <div class="needToBeDealt">
          <div class="add-btn">
            <div class="content-right"></div>
            <div class="content-button">
              <el-radio-group v-model="time" @change="selectChange">
                <el-radio-button v-for="(itemn, indexn) in fromList" :key="indexn" :label="itemn.val" :value="itemn.val">
                  {{ itemn.text }}
                </el-radio-button>
              </el-radio-group>
              <el-button
                v-if="showAddButton"
                type="primary"
                icon="el-icon-plus"
                @click="addSchedule"
              >
                新建日程
              </el-button>
            </div>
          </div>
          <full-calendar ref="calendar" id="calendar" style="height: calc(100vh - 150px)" :options="calendarOptions">
            <template v-slot:slotLabelContent="arg">
              <div class="calendar-timeline">
                <span v-if="dayjs(arg.date).format('HH:mm') !== '00:00'">{{
                  dayjs(arg.date).format('HH:mm')
                }}</span>
              </div>
            </template>
            <template v-slot:dayHeaderContent="arg">
              <div class="header-box">
                <div class="day-header" :class="period == 1 ? 'ml30' : ''">
                  <span class="week">{{ getWeek(arg.date) }}</span>
                  <span class="date" v-if="period !== 3">{{ dayjs(arg.date).format('D') }}</span>
                </div>
              </div>
            </template>

            <template v-slot:eventContent="arg">
              <div
                class="item"
                :style="{
                  background: period == 3 ? getColorFn(arg.event.textColor, '0.1') : arg.event.color
                }"
              >
                <div class="flex over-title" @click="handleEventClick(arg)">
                  <img v-if="period !== 2" :src="arg.event.extendedProps.avatar" class="img" alt="" />
                  <div class="over-title" :style="{ color: period == 3 ? arg.event.textColor : arg.event.textColor }">
                    {{ arg.event.title }}
                  </div>
                </div>
                <template v-if="arg.event.extendedProps.show > -1">
                  <span
                    v-if="arg.event.extendedProps.finish == 2"
                    class="el-icon-error"
                    :style="{ color: arg.event.textColor }"
                  ></span>

                  <span
                    v-else-if="arg.event.extendedProps.finish == 3"
                    @click="putStatus('取消完成', 1, arg.event)"
                    class="el-icon-success"
                    :style="{ color: arg.event.textColor }"
                  ></span>
                  <span
                    v-else
                    class="yuan"
                    :style="{ 'border-color': arg.event.textColor }"
                    @click="putStatus('完成', 3, arg.event, arg)"
                  ></span>
                </template>
              </div>
            </template>
          </full-calendar>
        </div>
      </div>
    </div>
    <!-- 通用弹窗表单  -->
    <addTodo ref="addTodo" @getList="getList" :leftTime="leftTime"></addTodo>
    <calendarDetails ref="calendarDetails" @deleteFn="getList" @editFn="editFn" :dateInfo="dateInfo"></calendarDetails>
    <contract-dialog ref="contractDialog" :config="configContract" @isOk="getList"></contract-dialog>
    <!-- 跟进弹窗 -->
    <el-dialog title="添加跟进记录" class="record" v-model="dialogVisible" width="40%">
      <recordUpload :form-info="formInfo" @change="recordChange"></recordUpload>
    </el-dialog>
    <edit-examine
      ref="editExamine"
      :parameterData="parameterData"
      :ids="formInfo.data.id"
      @scheduleRecord="scheduleRecord"
    ></edit-examine>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
// 导入FullCalendar插件
import dayGridPlugin from '@fullcalendar/daygrid'
import listPlugin from '@fullcalendar/list'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

// 导入API接口
import { clientRemindDetailApi } from '@/api/enterprise.ts'
import { scheduleListApi, scheduleStatusApi } from '@/api/user.ts'
import { configRuleApproveApi } from '@/api/config.ts'

// 导入工具函数
import { toGetWeek, getColor } from '@/utils/format.ts'
import { getStorageJson } from '@/utils/storage.ts'

export default {
  name: 'MyCalendar',
  props: {
    showAddButton: {
      type: Boolean,
      default: false
    }
  },
  components: {
    calendarBar: defineAsyncComponent(() => import('./calendarBar')),
    FullCalendar: defineAsyncComponent(() => import('@fullcalendar/vue3')),
    addTodo: defineAsyncComponent(() => import('./addTodo')),
    contractDialog: defineAsyncComponent(() => import('@/views/customer/contract/components/contractDialog')),
    recordUpload: defineAsyncComponent(() => import('@/views/customer/list/components/recordUpload')),
    calendarDetails: defineAsyncComponent(() => import('./calendarDetails')),
    editExamine: defineAsyncComponent(() => import('@/views/user/examine/components/editExamine'))
  },
  computed: {
    dayjs: () => dayjs
  },
  data() {
    return {
      loading: false,
      calendarApi: null,
      title: '',
      time: '1',
      cid: null,
      dialogVisible: false,
      configContract: {},
      formInfo: {
        avatar: '',
        type: 'add',
        show: 1,
        data: {},
        follow_id: 0
      },
      fromList: [
        { text: '天', val: '1' },
        { text: '周', val: '2' },
        { text: '月', val: '3' }
      ],

      start_time: '',
      end_time: '',
      dateInfo: {},
      type: [],
      period: 1,
      calendarOptions: {
        height: 'calc(100vh - 232px)',
        eventColor: '',
        initialDate: dayjs().format('YYYY-MM-DD HH:mm:ss'),
        plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin, listPlugin],
        handleWindowResize: true,
        displayEventTime: false,
        slotDuration: '00:60:00',
        scrollTime: '05:30:00',
        slotMinTime: '00:00:00',
        slotMaxTime: '24:00:00',
        headerToolbar: {
          left: '',
          center: 'prev title next',
          right: ''
        },

        buttonText: {
          month: '月',
          week: '周',
          day: '天'
        },

        allDaySlot: true,
        dayMaxEventRows: 6,
        allDayText: '',
        moreLinkContent: this.moreLinkContent,
        weekends: true,
        nowIndicator: true,
        weekNumbers: false,
        slotLabelFormat: {
          hour: '2-digit',
          minute: '2-digit',
          meridiem: false,
          hour12: false
        },
        selectable: true,
        displayEventEnd: false,
        initialView: 'timeGridDay',
        dateClick: this.handleDateClick,
        customButtons: {
          next: {
            text: 'PREV',
            click: () => {
              this.next()
            }
          },
          prev: {
            text: 'PREV',
            click: () => {
              this.prev()
            }
          }
        },
        events: [],
        slotEventOverlap: false,
        eventOverlap: false,
        locale: 'zh-cn',
        weekNumberCalculation: 'ISO'
      },
      detailedData: {},
      parameterData: {
        contract_id: '',
        customer_id: '',
        invoice_id: '',
        bill_id: '',
        remind_id: ''
      },
      leftTime: '',
      buildData: [],
      item: {},
      status: 0
    }
  },

  mounted() {
    const userInfo = getStorageJson('userInfo')
    if (userInfo) {
      this.userId = userInfo.userId
      this.formInfo.avatar = userInfo.avatar
    }

    this.getConfigApprove()

    setTimeout(() => {
      if (this.$refs.calendar) {
        this.calendarApi = this.$refs.calendar.getApi()
        this.dayRender()
        this.title = dayjs(this.calendarApi.view.currentStart).format('YYYY-MM-DD')
        this.start_time = this.title + ' 00:00:00'
        this.end_time = this.title + ' 23:59:59'
      }
    }, 400)

    this.removeTitle()
  },

  methods: {
    // 父组件点击"新建日程"按钮时调用
    addSchedule() {
      this.$refs.addTodo.openBox()
    },
    // 父组件切回我的日程 Tab 时调用，刷新 FullCalendar 布局
    refresh() {
      this.$nextTick(() => {
        this.calendarApi?.updateSize()
      })
    },
    handleEventClick(e) {
      this.$refs.calendarDetails.openBox(e.event.extendedProps)
    },
    recordChange() {
      this.dialogVisible = false
      this.getList()
    },
    async getConfigApprove() {
      const result = await configRuleApproveApi(0)
      this.buildData = result.data
    },
    putStatus(text, status, item) {
      const { extendedProps } = item
      this.item = extendedProps
      const bill_id = extendedProps.bill_id
      this.status = status

      switch (extendedProps.cid_value) {
        case 3:
        case 4:
          const id = bill_id.bill_id !== 0 ? bill_id.bill_id : bill_id.remind_id
          if (status == 3) {
            clientRemindDetailApi(id).then((res) => {
              res.data.id = bill_id.remind_id
              this.parameterData.customer_id = res.data.eid
              this.parameterData.contract_id = res.data.cid
              this.parameterData.remind_id = bill_id.remind_id

              const switchType = res.data.types === 0 ? 'contract_refund_switch' : 'contract_renew_switch'
              const data = { id: this.buildData[switchType] }
              this.$refs.editExamine.openBox(data, res.data.cid, switchType, item, status)
            })
          }
          break
        case 2:
          if (extendedProps.finish === 3) {
            return false
          }
          this.formInfo.follow_id = bill_id ? bill_id.follow_id : 0
          this.formInfo.data.eid = extendedProps.link_id
          this.dialogVisible = true
          break
        default:
          this.scheduleRecord(extendedProps, status)
      }
    },

    async scheduleRecord(data = this.item, status = this.status) {
      const { start_time: start, end_time: end, itemId: id } = data
      const info = { status, start, end }
      await scheduleStatusApi(id, info)
      await this.getList()
    },
    handleDateClick(e) {
      this.detailedData = {
        startDate: dayjs(e.date).format('YYYY-MM-DD'),
        startTime: dayjs(e.date).format('HH:mm:ss'),
        endDate: dayjs(e.date).format('YYYY-MM-DD'),
        time: dayjs(e.date).format('YYYY-MM-DD HH:mm:ss')
      }
      this.$refs.addTodo.openBox(this.detailedData)
    },

    dayRender() {
      document.documentElement.style.setProperty(`--fc-today-bg-color`, '#fff')
      let dayTime = document.querySelectorAll('.fc-timegrid-slot-label-cushion')
      if (dayTime[0]) dayTime[0].classList.add('fcTime')
      document.querySelectorAll('.fc-button-primary').forEach((li) => li.setAttribute('title', ''))
    },

    moreLinkContent(e) {
      return '还有' + e.num + '个日程'
    },

    day() {
      this.period = 1
      this.calendarApi?.changeView('timeGridDay')
      this.getList()
      this.removeTitle()
      this.dayRender()
    },

    month() {
      this.period = 3
      this.calendarApi.changeView('dayGridMonth')
      this.getList()
      this.removeTitle()
    },

    week() {
      this.period = 2
      this.calendarApi.changeView('timeGridWeek')
      this.getList()
      this.removeTitle()
      this.dayRender()
    },

    prev() {
      this.calendarApi.prev()
      this.getList()

      if (this.period === 2) {
        const date = this.$refs.calendarBar.value
        this.$refs.calendarBar.value = dayjs(date).subtract(7, 'd').format('YYYY-MM-DD')
      } else {
        this.$refs.calendarBar.value = dayjs(this.calendarApi.view.currentStart).format('YYYY-MM-DD')
      }
    },
    removeTitle() {
      let row = null
      setTimeout(() => {
        row = document.querySelectorAll('.fc-daygrid-day-bottom a')
        for (var i = 0; i < row.length; i++) {
          row[i].title = ''
        }
      }, 300)
    },

    next() {
      this.calendarApi.next()
      this.getList()
      if (this.period == 2) {
        let date = this.$refs.calendarBar.value
        this.$refs.calendarBar.value = dayjs(dayjs(date).add(7, 'd'))
      } else {
        this.$refs.calendarBar.value = dayjs(this.calendarApi.view.currentStart).format('YYYY-MM-DD')
      }
    },
    getList() {
      this.loading = true
      let currentStart = this.calendarApi.view.currentStart
      let currentEnd = this.calendarApi.view.currentEnd
      if (this.period == 1) {
        this.start_time = dayjs(currentStart).format('YYYY-MM-DD') + ' 00:00:00'
        this.end_time = dayjs(currentStart).format('YYYY-MM-DD') + ' 23:59:59'
      } else if (this.period == 2) {
        this.start_time = dayjs(currentStart).format('YYYY-MM-DD') + ' 00:00:00'
        this.end_time = dayjs(currentEnd).subtract(1, 'days').format('YYYY-MM-DD') + ' 23:59:59'
      } else {
        this.start_time = dayjs(this.calendarApi.view.activeStart).format('YYYY-MM-DD') + ' 00:00:00'
        this.end_time = dayjs(currentEnd).subtract(1, 'days').format('YYYY-MM-DD') + ' 23:59:59'
      }
      let data = {
        start_time: this.start_time,
        end_time: this.end_time,
        period: this.period,
        cid: this.cid
      }

      scheduleListApi(data).then((res) => {
        this.loading = false
        let newArr = []
        res.data.map((item) => {
          let start_time = item.start_time
          let end_time = item.end_time
          let itemStartTime = dayjs(item.start_time).format('YYYY-MM-DD')
          let itemEndTime = dayjs(item.end_time).format('YYYY-MM-DD')

          if (itemEndTime != itemStartTime) {
            item.all_day = true
            item.end_time = itemEndTime + ' 24:00:00'
          } else if (itemEndTime == itemStartTime && item.all_day == 1) {
            item.all_day = true
            item.end_time = itemEndTime + ' 24:00:00'
          } else if (item.all_day == 0) {
            item.all_day = false
          }
          if (item.user.length == 0) {
            item.user.push(item.master)
          }

          let avatar = getStorageJson('userInfo', {}).avatar
          newArr.push({
            start: item.start_time,
            end: item.end_time,
            allDay: item.all_day,
            title: item.title,
            avatar: item.master.avatar || avatar,
            content: item.content,
            start_time,
            end_time,
            color: getColor(item.color, '0.1'),
            textColor: item.color,
            show: this.findItem(item.user, 'id', item.master.id),
            finish: item.finish,
            itemId: item.id,
            cid_value: item.cid,
            overlap: false,
            bill_id: item.relation || '',
            link_id: item.link_id
          })
        })

        this.calendarOptions.events = newArr
        this.$refs.calendarBar?.getList(dayjs(currentEnd).format('YYYY-MM-DD'))
      })
    },

    findItem(arr, key, val) {
      for (var i = 0; i < arr.length; i++) {
        if (arr[i].id === val || arr[i].id == this.userId) {
          return 2
        }
      }
      return -1
    },
    getWeek(date) {
      return toGetWeek(date)
    },

    editFn(id, type, date) {
      let data = {
        id,
        type,
        edit: true,
        date
      }
      this.$refs.addTodo.openBox(data)
    },
    handleDate(data, val) {
      this.cid = data.type
      this.leftTime = data.time
      if (val) {
        this.calendarApi.gotoDate(data.time)
      }
      setTimeout(() => {
        const calendar = this.$refs.calendar
        if (!calendar) return
        this.calendarApi = calendar.getApi()
        this.time = 1
        this.day()
      }, 300)
    },
    selectChange(e) {
      this.time = e
      if (this.time == 1) {
        this.day()
      } else if (this.time == 2) {
        this.week()
      } else {
        this.month()
      }
    },

    getColorFn(thisColor, thisOpacity) {
      getColor(thisColor, thisOpacity)
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.fc-header-toolbar) {
  position: absolute;
  top: 0;
  left: 0;
}

.day-header {
  display: flex;
  flex-direction: column;
  justify-content: center;
  height: 52px;
  font-family: PingFang SC-Regular, PingFang SC;
  font-weight: 400;

  line-height: 20px;

  .week {
    font-size: 13px;
    color: #909399;
  }
  .date {
    font-size: 18px;
    color: #606266;
    font-weight: 800;
  }
}

:deep(.fc .fc-button .fc-icon) {
  color: #c0c4cc;
}
:deep(.fc .fc-timegrid-axis-frame-liquid) {
  border-bottom: none !important;
}
:deep(.fc .fc-popover) {
  z-index: 22;
}
.ml30 {
  margin-left: -30px;
}

:deep(.fc .fc-timegrid-slot) {
  height: 3.5em !important;
}
:deep(.fc .fc-timegrid-divider) {
  border: none;
  padding: 0;
}

:deep(.fc .fc-cell-shaded) {
  background: #fff;
}
:deep(.fc-timegrid-slot-label-cushion) {
  color: #909399;
  font-size: 12px;
}

:deep(.fc .fc-daygrid-day-top) {
  padding-top: 6px;
  display: block;
}

:deep(.fc .fc-timegrid-slot-label) {
  border-bottom: 1px solid #fff;
  vertical-align: top;
  position: relative;
}
:deep(.fc .fc-scrollgrid-shrink) {
  width: 60px !important;
}
:deep(.fc-timegrid-slot-label-cushion) {
  position: absolute;
  left: 0;
  top: -8px;
}

:deep(.fcTime) {
  position: absolute;
  left: 0;
  top: -16px !important;
}

.calendar-layout {
  display: flex;
  gap: 14px;
}

.calendar-sidebar {
  width: 350px;
  flex-shrink: 0;
}

.calendar-main {
  flex: 1;
  min-width: 0;
}

.line {
  border-left: 1px solid #f0f2f5;
}
.item {
  width: 100%;
  display: flex;
  font-size: 14px;
  font-family: PingFang SC-Regular, PingFang SC;
  font-weight: 400;
  justify-content: space-between;
  align-items: center;
  padding-right: 4px;
  border-radius: 13px 13px 13px 13px;

  .img {
    flex-shrink: 0;
    display: block;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    margin-right: 5px;
    object-fit: cover;
  }
  .yuan {
    width: 12px;
    height: 12px;
    border-radius: 50px;
    border: 1px solid #fff;
  }
}
.over-title {
  width: 95% !important;
  overflow: hidden !important;
  white-space: nowrap;
  text-overflow: ellipsis !important;
}

:deep(.fc-direction-ltr .fc-daygrid-event.fc-event-start) {
  border-radius: 13px !important;
}

:deep(.el-tabs__nav-wrap::after) {
  display: none;
}
.plan-tabs-content {
  margin-top: 14px;
  :deep(.el-tabs) {
    padding: 0 20px;
  }
  :deep(.el-tabs__item) {
    line-height: 26px;
  }
}

.add-btn {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
}
.content-button {
  display: flex;
  align-items: center;
  gap: 14px;
}
.needToBeDealt {
  padding: 0 20px;
  position: relative;
}

:deep(.fc .fc-button-primary) {
  background-color: #fff;
  color: #606266;
  border: none;
}

:deep(.fc .fc-toolbar-chunk) {
  display: flex;
  align-items: center;
}
:deep(.fc .fc-button-primary:hover) {
  background-color: #fff;
  color: #606266;
  border: none;
}
:deep(.fc .fc-button:focus) {
  box-shadow: none !important;
}
:deep(.fc-h-event) {
  margin: 2px 0 !important;
}

:deep(.fc .fc-toolbar-title) {
  font-size: 16px;
  font-weight: 500;
  color: #303133;
}
:deep(.fc-scrollgrid-sync-inner) {
  a {
    color: #606266 !important;
  }
}

:deep(a) {
  text-decoration: none;
  outline: none;
}

:deep(.fc-timeGridWeek-view) {
  th {
    text-align: left;
  }

  .fc-col-header-cell-cushion {
    padding: 2px 8px;
  }
}

:deep(.fc-dayGridMonth-view) {
  th {
    text-align: left;
  }
  .day-header {
    height: 40px;

    .week {
      font-size: 14px;
    }
  }
  td {
    height: 95px;
  }
  .fc-col-header-cell-cushion {
    padding: 2px 8px;
  }
}

:deep(.fc-timegrid-axis) {
  width: 100px !important;

  z-index: 99;
}
:deep(.fc-widget-content) {
  width: 150px !important;
  background-color: pink;
  z-index: 99;
}
.calendar-timeline {
  width: 50px;
  display: flex;
  justify-content: center;
}
:deep(.fc .fc-more-popover .fc-popover-body) {
  max-height: 300px;
  overflow-y: scroll;
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.record {
  .el-dialog__body {
    padding: 20px 20px 30px 20px;
  }
}
</style>
