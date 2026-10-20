<template>
  <div class="left-box">
    <div class="dealt-date">
      <el-calendar v-model="value">
        <template #date-cell="{ date, data, index }">
          <div class="dealt-date-item" @click="dateBnt(data.day)">
            <input type="hidden" :value="data.day" />
            <div class="calendar_area" :class="getRest(data.day) ? 'activeRed' : ''">
              {{ data.day.split('-').slice(2).join('-') }}
              <div class="dealt-content" v-html="getCalendarDay(data.day)"></div>
            </div>
          </div>
        </template>
      </el-calendar>
    </div>
    <div class="dealt-list">
      <div class="el-submenu__title">
        <span>日程类型</span>
        <el-tooltip effect="dark" content="添加日程类型" placement="top">
          <i @click="handleShow" class="iconfont icontianjia"></i>
        </el-tooltip>
      </div>
      <el-checkbox-group v-model="checkedTypes" @change="handleCheckedKey">
        <div class="flex checkbox-item" v-for="(item, index) in tableData">
          <el-checkbox :key="index" :label="item.id" :value="item.id" :style="{ '--fill-color': item.color }">
            {{ item.name }}
          </el-checkbox>
          <el-dropdown class="more" v-if="item.id >= 6">
            <i class="iconfont icongengduo" />
            <template #dropdown>
              <el-dropdown-menu style="text-align: center">
                <el-dropdown-item v-if="item.id >= 6" @click="handleEdit(item)"> 编辑 </el-dropdown-item>
                <el-dropdown-item v-if="item.id >= 6" @click="handleDelete(item)"> 删除 </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-checkbox-group>
    </div>
  </div>
</template>

<script>
import dayjs from 'dayjs'
import { modalSure } from '@/libs/public.ts'
import modalForm from '@/libs/modal-form.ts'
import {
  dealtScheduleCountApi,
  scheduleTypesCreateApi,
  scheduleTypesEditApi,
  scheduleTypesDeleteApi,
  scheduleTypesApi
} from '@/api/user.ts'

export default {
  emits: ['handleDate'],
  name: 'CalendarBar',
  components: {},
  data() {
    return {
      value: new Date(),
      calendar: [],
      dateShow: false,
      dailyDay: {
        startTime: '',
        endTime: ''
      },
      tableData: [],
      checkedTypes: [],
      isShow: true,
      data: {
        time: dayjs(new Date()).format('YYYY-MM-DD'),
        type: []
      }
    }
  },
  mounted() {
    // mounted 只保证 $el 已创建，el-calendar 的表格此时可能尚未渲染，放到 nextTick 再绑
    this.$nextTick(() => {
      this.nextTPrevClick()
    })
    this.getTypes()
  },
  methods: {
    dateBnt(data) {
      this.dateShow = true
      this.data.time = data
      this.handleEmit()
    },

    handleEmit() {
      if (this.dateShow) {
        this.$emit('handleDate', this.data, 1)
      } else {
        this.$emit('handleDate', this.data)
      }
    },
    // 列表
    async getList(val) {
      const data = {
        cid: this.checkedTypes,
        start_time: this.dailyDay.startTime,
        end_time: this.dailyDay.endTime,
        period: 3
      }
      if (val) {
        if (val >= this.dailyDay.startTime && val <= this.dailyDay.endTime) {
        } else {
          const result = await dealtScheduleCountApi(data)
          this.calendar = result.data
        }
      } else {
        const result = await dealtScheduleCountApi(data)
        this.calendar = result.data
      }
    },

    handleCheckedKey(value) {
      this.data.type = this.checkedTypes
      this.handleEmit()
    },
    async getTypes() {
      this.checkedTypes = []
      const result = await scheduleTypesApi()
      this.tableData = result.data
      this.tableData.map((value) => {
        this.checkedTypes.push(value.id)
      })
      this.data.type = this.checkedTypes
      await this.handleEmit()
      await this.setCalendarButton()
    },
    // 新增
    handleShow() {
      modalForm(scheduleTypesCreateApi({})).then((res) => {
        this.getTypes(1)
      })
    },

    // 编辑
    handleEdit(val) {
      modalForm(scheduleTypesEditApi(val.id)).then((res) => {
        this.getTypes()
      })
    },

    // 删除
    handleDelete(val) {
      modalSure('您确定要删除这条日程类型吗').then(() => {
        scheduleTypesDeleteApi(val.id).then((res) => {
          this.getTypes()
        })
      })
    },
    // 日历首行首格 / 末行末格的隐藏 input 承载当前视图的起止日期。
    // 日历尚未渲染时返回 false，调用方据此跳过取数，避免用脏范围请求。
    syncDailyDayRange() {
      const root = this.$el
      if (!root) return false
      const startInput = root.querySelector('.dealt-date .el-calendar-table__row:first-child td:nth-child(1) input')
      const endInput = root.querySelector('.dealt-date .el-calendar-table__row:last-child td:last-child input')
      if (!startInput || !endInput) return false
      this.dailyDay.startTime = startInput.value
      this.dailyDay.endTime = endInput.value
      return true
    },
    // 把 el-calendar 自带的「上月/下月」文字按钮改成箭头图标
    decorateArrowButton(btn, iconClass) {
      const icon = btn && btn.lastElementChild
      if (!icon) return
      btn.setAttribute('class', 'el-button--text')
      icon.setAttribute('class', iconClass)
      icon.innerHTML = ''
    },
    setCalendarButton() {
      this.$nextTick(() => {
        const root = this.$el
        if (!root) return
        if (this.syncDailyDayRange()) {
          this.getList()
        }
        this.decorateArrowButton(
          root.querySelector('.el-calendar__button-group .el-button-group>button:nth-child(1)'),
          'el-icon-arrow-left'
        )
        this.decorateArrowButton(
          root.querySelector('.el-calendar__button-group .el-button-group>button:nth-child(3)'),
          'el-icon-arrow-right'
        )
      })
    },
    nextTPrevClick() {
      const root = this.$el
      if (!root) return
      const prevFirst = root.querySelector('.dealt-date .el-calendar-table__row:first-child')
      const nextLast = root.querySelector('.dealt-date .el-calendar-table__row:last-child')
      const targets = [
        ...(prevFirst ? prevFirst.getElementsByClassName('prev') : []),
        ...(nextLast ? nextLast.getElementsByClassName('next') : []),
        root.querySelector('.dealt-date .el-calendar__button-group .el-button-group>button:nth-child(1)'),
        root.querySelector('.dealt-date .el-calendar__button-group .el-button-group>button:nth-child(3)')
      ]
      targets.forEach((el) => this.tabClick(el))
    },
    tabClick(obj) {
      if (!obj) return
      obj.addEventListener('click', () => {
        if (this.syncDailyDayRange()) {
          this.getList()
        }
      })
    },
    // 判断是否是休息日
    getRest(day) {
      let len = this.calendar.length

      let show = true
      if (len > 0) {
        for (let i = 0; i < len; i++) {
          if (this.calendar[i].time === day) {
            if (this.calendar[i].is_rest == 1) {
              show = true
            } else if (this.calendar[i].is_rest == 0) {
              show = false
            }

            break
          }
        }
      }
      return show
    },
    // 判断是否显示完成图标
    getCalendarDay(day) {
      let len = this.calendar.length

      let str = ''
      if (len > 0) {
        for (let i = 0; i < len; i++) {
          if (this.calendar[i].time === day) {
            if (this.calendar[i].no_submit == 0) {
              str = '<i class="iconfont iconyiguo color-default"></i>'
            } else if (this.calendar[i].no_submit > 0) {
              str = '<i class="iconfont iconyiguo color-danger"></i>'
            }

            break
          }
        }
      }
      return str
    }
  }
}
</script>

<style lang="scss" scoped>
.dealt-bar {
  height: 100%;
}
.left-box {
  height: calc(100vh - 180px);
  scrollbar-width: none; /* firefox */
  -ms-overflow-style: none; /* IE 10+ */
  overflow-y: auto;
  background-color: transparent;
}

.icongengduo {
  color: #909399;
  font-size: 12px;
  cursor: pointer;
}
:deep(.el-submenu__title) {
  height: 30px;
  line-height: 30px;
  margin-bottom: 20px;
}
.dealt-date {
  margin-top: 10px;
  :deep(.el-calendar__header) {
    padding: 20px;
    border-bottom: none;
  }
  .calendar_area {
    width: 100%;
    text-align: center;
    display: flex;
    justify-content: center;
    height: 40px;
    align-items: center;
    position: relative;
  }
  .activeRed {
    color: red;
  }
  :deep(.el-calendar-table td .dealt-content) {
    i {
      position: absolute;
      right: 0;
      top: 0;
      font-size: 8px;
      font-weight: bold;
    }
  }
  .dealt-date-item {
    width: 100%;
    height: 100%;
  }
  .is-selected {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #1890ff;
  }
  position: relative;
  :deep(.el-calendar-table .el-calendar-day) {
    height: 40px;
    padding: 0;
  }
  :deep(.el-calendar) {
    background-color: transparent;
  }
  :deep(.el-calendar__body) {
    padding-bottom: 20px;
  }
  :deep(.el-calendar-table__row td) {
    text-align: center;
  }
  :deep(.el-calendar__header) {
    justify-content: center;
  }
  :deep(.el-calendar__button-group) {
    .el-button-group {
      button {
        color: #000000;
      }
      button:nth-of-type(1) {
        position: absolute;
        left: 14px;
        top: 18px;
      }
      button:nth-of-type(2) {
        display: none;
      }
      button:nth-of-type(3) {
        position: absolute;
        right: 14px;
        top: 18px;
      }
    }
  }
}
.dealt-list {
  :deep(.is-checked .el-checkbox__label) {
    color: #606266;
  }
  .icontianjia {
    color: #1890ff;
  }
  .el-submenu__title {
    display: flex;
    justify-content: space-between;
    padding-left: 0;
    padding-right: 0;
  }
  :deep(.el-submenu__icon-arrow) {
    right: 0;
    font-size: 16px;
  }
  :deep(.el-checkbox) {
    display: block;
    margin: 0 0 14px 4px;
    margin-bottom: 0;
  }
  :deep(.el-checkbox__inner) {
    border-radius: 50%;
  }
  padding: 0 20px;
  .icon-title {
    color: #1890ff;
  }
  .el-icon-item {
    margin-left: -40px;
  }
  // 不同颜色判断
  :deep(.el-checkbox) {
    .is-checked .el-checkbox__inner {
      background-color: var(--fill-color);
      border-color: var(--fill-color);
    }
    .el-checkbox__inner {
      background-color: #fff;
      border-color: var(--fill-color);
    }
  }
}

.flex {
  width: 100%;
  height: 35px;
  display: flex;
  border-radius: 4px;
  align-items: center !important;
  justify-content: space-between;
}

.checkbox-item {
  &:hover {
    background: #f3f5f9;
  }
}
</style>
