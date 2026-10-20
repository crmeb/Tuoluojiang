<template>
  <!-- 我的汇报页面 -->
  <div class="divBox">
    <el-card class="normal-page">
      <!-- 填写日报 -->
      <div>
        <oaFromBox
          :isAddBtn="false"
          :isViewSearch="false"
          :search="search"
          :total="totalPage"
          title="我的汇报"
          @confirmData="confirmData"
        >
          <template #rightBtn class="box">
            <el-dropdown type="primary" @command="handleDaily">
              <el-button type="primary">填写汇报</el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item v-for="(item, index) in dailyData" :key="item.id" :command="item.id">
                    {{ item.name }}
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            <el-popover placement="bottom" trigger="click" width="auto">
              <div class="popoverWidth">
                <calendar :calendar="calendar" :tabType="tableFrom.type" @switchTime="switchTime"> </calendar>
              </div>
              <template #reference>
                <span class="btn" type="text">
                  <span class="iconfont icongengduo2"></span>
                </span>
              </template>
            </el-popover>
          </template>
        </oaFromBox>
        <div v-loading="loading" class="mt-10">
          <el-table ref="elTable" :data="tableData" :height="TABLE_HEIGHT">
            <el-table-column label="姓名" prop="name" width="90" />
            <el-table-column label="部门" min-width="100" prop="frame_name" />
            <el-table-column label="工作总结" min-width="250" prop="finish">
              <template #default="scope">
                <div v-for="(item, index) in scope.row.finish" :key="index" class="textover3">{{ item }}</div>
              </template>
            </el-table-column>
            <el-table-column label="工作计划" min-width="250" prop="plan">
              <template #default="scope">
                <div v-if="scope.row.types != 3">
                  <div v-for="(item, index) in scope.row.plan" :key="index" class="textover3">{{ item }}</div>
                </div>
                <span v-else>--</span>
              </template>
            </el-table-column>

            <el-table-column label="汇报类型" width="120">
              <template #default="scope">
                {{ getDailyTypes(scope.row.types) }}
              </template>
            </el-table-column>

            <el-table-column label="创建时间" prop="created_at" width="160" />

            <el-table-column
              label="操作"
              :show-overflow-tooltip="true"
              :width="tableFrom.type == 0 ? 160 : 60"
              fixed="right"
            >
              <template #default="scope">
                <el-button
                  v-hasPermi="['user:daily:my:check']"
                  type="primary" link
                  @click="onCheck(scope.row.daily_id, 'check', scope.row)"
                >
                  查看
                </el-button>

                <el-button
                  v-if="tableFrom.type == 0 && new Date(scope.row.end_time).getTime() > new Date().getTime()"
                  type="primary" link
                  @click="onEdit(scope.row.daily_id, 'edit', scope.row)"
                >
                  编辑
                </el-button>

                <el-button
                  v-if="tableFrom.type == 0 && new Date(scope.row.end_time).getTime() > new Date().getTime()"
                  type="primary" link
                  @click="onDelete(scope.row.daily_id)"
                >
                  删除
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="page-fixed">
            <el-pagination
              :current-page="tableFrom.page"
              :page-size="tableFrom.limit"
              :page-sizes="[15, 20, 30]"
              :total="totalPage"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="handleCurrentChange"
            />
          </div>
        </div>
      </div>
    </el-card>
    <addBox
      ref="addBox"
      :daily-id="dailyIndex"
      :edit-data="editData"
      :edit-id="editId"
      :finishList="finishList"
      :needList="needList"
      @getDailyTodoInfo="getDailyTodoInfo"
      @tableList="tableList"
      @updateValue="updateValue"
    />
  </div>
</template>
<script>
import dayjs from 'dayjs'
import { modalSure } from '@/libs/public.ts'
import pickerOptions from '@/libs/pickerOptions.ts'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import { getEnterpriseDaily, getEnterpriseUsersApi } from '@/api/enterprise.ts'
import { deleteDailyApi, submitStatisticsApi, noSubmitListApi, scheduleListApi } from '@/api/user.ts'
import addBox from './components/addBox'
import calendar from './components/calendar'

import oaFromBox from '@/components/common/oaFromBox'
export default {
  name: 'IndexVue',
  components: {
    addBox,
    calendar,
    oaFromBox
  },
  data() {
    return {
      TABLE_HEIGHT,
      loading: false,
      currentTime: '',
      submitType: '1',
      value: new Date(),
      active: false,
      submitFrom: {
        page: 1,
        limit: 15
      },
      totalSubmit: 0,

      fromList: [
        { text: '全部', val: '' },
        { text: '本周', val: 'week' },
        { text: '本月', val: 'month' },
        { text: '上月', val: 'last month' }
      ],
      dailyData: [
        { name: '填写日报', id: 0 },
        { name: '填写周报', id: 1 },
        { name: '填写月报', id: 2 },
        { name: '填写汇报', id: 3 }
      ],
      options: [
        { name: '日报', id: 0 },
        { name: '周报', id: 1 },
        { name: '月报', id: 2 },
        { name: '汇报', id: 3 }
      ],

      weekInfo: '',
      dailyName: '',
      dailyIndex: 1,
      totalPage: 0,
      timeVal: [dayjs().subtract(1, 'months').format('YYYY/MM/DD'), dayjs().format('YYYY/MM/DD')],
      year: '',
      tableFrom: {
        page: 1,
        limit: 15,
        type: 0,
        types: 0,
        time: dayjs().subtract(1, 'months').format('YYYY/MM/DD') + '-' + dayjs().format('YYYY/MM/DD'),
        user_id: '',
        scope_frame: 'self'
      },
      table: [],
      total: '',
      submit: '',
      no_submit: '',
      calendar: [],
      disabled: false,
      pickerOptions,
      tableData: [],
      departmentalData: [],
      editId: 0,
      editData: {},
      nowDateStar: null, // 今天时间0点时间戳
      nowDateEnd: null, // 今天时间23:59:59 时间戳
      needList: [],
      finishList: '',
      time: dayjs(new Date()).format('YYYY-MM-DD'),
      windowHeight: 0,
      search: [
        {
          field_name: '汇报类型',
          field_name_en: 'types',
          form_value: 'select',
          data_dict: [
            { name: '日报', id: 0 },
            { name: '周报', id: 1 },
            { name: '月报', id: 2 },
            { name: '汇报', id: 3 }
          ],
          value: 0
        },
        {
          field_name: '开始时间',
          field_name_end: '结束时间',
          field_name_en: 'time',
          form_value: 'date_picker',
          data_dict: [dayjs().subtract(1, 'months').format('YYYY/MM/DD'), dayjs().format('YYYY/MM/DD')]
        }
      ]
    }
  },
  created() {
    this.windowHeight = window.innerHeight - 330
    this.getList()
    this.nowDateStar = parseInt(dayjs(new Date()).startOf('day').format('x'))
    this.nowDateEnd = parseInt(dayjs(new Date()).endOf('day').format('x'))
    this.dailyName = this.dailyData[this.dailyIndex - 1].name
    // this.getUsersOption()
  },
  mounted() {
    this.getDate()
    this.getDailyTodoInfo()
  },
  methods: {
    updateValue(newVal) {
      this.finishList = newVal
    },
    confirmData(data) {
      if (data && data == 'reset') {
        this.tableFrom = {
          page: 1,
          limit: 15,
          type: 0,
          types: 0,
          time: dayjs().subtract(1, 'months').format('YYYY/MM/DD') + '-' + dayjs().format('YYYY/MM/DD'),
          user_id: '',
          scope_frame: 'self'
        }
        this.timeVal = [dayjs().subtract(1, 'months').format('YYYY/MM/DD'), dayjs().format('YYYY/MM/DD')]
        this.search[1].data_dict = this.timeVal
      } else {
        this.tableFrom = { ...this.tableFrom, ...data }
      }
      this.getList()
    },
    // 获取待办列表
    getDailyTodoInfo() {
      let start_time = dayjs(this.time).format('YYYY-MM-DD') + ' 00:00:00'
      let end_time = dayjs(this.time).format('YYYY-MM-DD') + ' 23:59:59'
      const data = {
        start_time: start_time,
        end_time: end_time,
        cid: [1, 2, 3, 4, 5, 6],
        period: 1
      }
      scheduleListApi(data).then((res) => {
        res.data.forEach((item) => {
          item['checked'] = false
        })
        this.needList = res.data.filter((item) => [0, 1, -1].includes(item.finish))

        let data = res.data
          .filter((item) => item.finish === 3)
          .map((item) => item.title)
          .join('\n')
        this.finishList = data
      })
    },
    // 获取日期
    getDate() {
      this.currentTime = dayjs().subtract('day').format('YYYY-MM-DD') // 当天日期
      this.thePreviousDay = dayjs().subtract(1, 'day').format('YYYY-MM-DD') // 前一天日期
      this.thePreviousDay = dayjs(this.thePreviousDay).format('YYYY年MM月DD日') // 格式化日期
    },
    // 获取未提交的日历
    async submitStatisticsApi() {
      let data = {
        time: this.calendarTime,
        types: this.tableFrom.types,
        frame_id: this.tableFrom.frame_id,
        type: this.tableFrom.type == 0 ? '1' : '0'
      }
      const result = await submitStatisticsApi(data)
      this.calendar = result.data
    },
    onchangeTime() {
      this.tableFrom.page = 1
      if (this.timeVal) {
        this.tableFrom.time = this.timeVal[0] ? this.timeVal.join('-') : ''
      } else {
        this.tableFrom.time = ''
      }
      this.selectTypes()
    },

    // 切换日历月份触发的回调
    switchTime(data) {
      this.calendarTime =
        dayjs(data.startTime).format('YYYY/MM/DD') + '-' + dayjs(data.endTime).format('YYYY/MM/DD')
      this.submitStatisticsApi()
    },
    // async getUsersOption() {
    //   const result = await getEnterpriseUsersApi()
    //   this.usersOption = result.data ? result.data : []
    // },


    async getList() {
      this.tableFrom.scope_frame = 'self'
      this.loading = true
      const result = await getEnterpriseDaily(this.tableFrom)
      const data = result.data

      if (data.list.length) {
        data.list.forEach((el) => {
          el.isEdit = true
        })
      }
      this.totalPage = data.count
      this.tableData = data.list
      this.loading = false
    },

    tableList() {
      this.getList()
    },
    onEdit(id, type, data) {
      this.editId = id
      this.dailyIndex = data.types
      this.$refs.addBox.openBox(id, type, data)
    },
    onCheck(id, type, data) {
      this.editId = id
      this.dailyIndex = data.types
      this.editData = data
      this.$refs.addBox.openBox(id, type, data)
    },

    // 删除当日当周当月日报
    onDelete(id) {
      modalSure('确认删除当前汇报').then(() => {
        deleteDailyApi(id).then((res) => {
          this.tableFrom.page = 1
          this.getList()
        })
      })
    },
    // 添加日报
    // addDaily(val) {
    //   this.editId = 0
    //   this.editData = {}

    //   this.$refs.addBox.approveApplyList()

    //   this.disabled = this.$refs.addBox.disabled
    // },
    handleClick() {
      this.tableFrom.page = 1
      this.tableFrom.user_id = ''

      this.getList()
    },

    // 获取未提交的汇报表格数据
    async noSubmitList() {
      let data = {
        time: this.leftTime,
        types: this.tableFrom.types,
        frame_id: this.tableFrom.frame_id,
        page: this.submitFrom.page,
        limit: this.submitFrom.limit
      }
      const result = await noSubmitListApi(data)
      this.totalSubmit = result.data.count
      this.table = result.data.list
    },

    // 搜索
    selectTypes() {
      if (this.tableFrom.types == 0) {
      }
      this.tableFrom.page = 1
      this.getList()
    },

    // 清空
    reset() {
      this.leftTime =
        dayjs().subtract(1, 'day').format('YYYY/MM/DD') +
        '-' +
        dayjs().subtract(1, 'day').format('YYYY/MM/DD')
      this.thePreviousDay = dayjs().subtract(1, 'day').format('YYYY年MM月DD日')

      this.tableFrom.types = 0
      this.tableFrom.time =
        dayjs().subtract(1, 'months').format('YYYY/MM/DD') + '-' + dayjs().format('YYYY/MM/DD')
      this.tableFrom.user_id = ''
      this.tableFrom.frame_id = ''
      this.tableFrom.page = 1
      this.timeVal = [dayjs().subtract(1, 'months').format('YYYY/MM/DD'), dayjs().format('YYYY/MM/DD')]
      setTimeout(() => {
        this.$refs.week.removeStyle()
        this.$refs.week.value = new Date(new Date().getTime() - 24 * 60 * 60 * 1000)
      }, 300)

      this.getList()
    },

    getDailyTypes(type) {
      var str = ''
      this.options.map((value) => {
        if (value.id === type) {
          str = value.name
        }
      })
      return str
    },

    handleSizeChange(val) {
      this.tableFrom.page = 1
      this.tableFrom.limit = val
      this.getList()
    },
    handleCurrentChange(page) {
      this.tableFrom.page = page
      this.getList()
    },

    handleDaily(command) {
      this.dailyIndex = command
      this.$refs.addBox.openBox(0, command)
      // this.dailyIndex = command
      // this.dailyName = this.dailyData[command - 1].name
      // this.addDaily('fillInTheReport')
    }
  }
}
</script>
<style lang="scss" scoped>
.iconrili3 {
  font-size: 14px;
}
:deep(.el-table th) {
  background-color: #f7fbff;
}
.flex-btn {
  display: flex;
  align-items: center;
}

.box {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
}

.tabs :deep(.el-tabs__item) {
  font-size: 13px;
}
.tabs {
  margin-bottom: 20px;
}

.popoverWidth {
  width: 316px;
  height: 370px;
}
.tips {
  display: flex;
  align-items: center;
  font-size: 13px;
  margin-left: 15px;
  font-family: PingFang SC-常规体, PingFang SC;
  font-weight: normal;
  color: #606266;
  .icontishi2 {
    color: #1890ff;
    margin-right: 4px;
  }
}

.color1 {
  color: #1890ff !important;
}
.color2 {
  color: #ed4014 !important;
}

.btn {
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-left: 10px;
  padding: 0;
  line-height: 30px;
  .fz14 {
    font-size: 14px;
  }
}
.textover3 {
  white-space: pre-wrap;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 10;
  overflow: hidden;
  line-height: 1.5;
}

:deep(.el-dropdown-menu__item) {
  text-align: left;
  i {
    color: #00c050;
  }
}
:deep(.el-dropdown__caret-button) {
  height: 32px;
}

:deep(.el-tabs__header) {
  margin-bottom: 0;
}
:deep(.el-tabs__nav-wrap::after) {
  display: none;
}

.active-add {
  background-color: #f2f8fe;
}
.divBox .el-pagination {
  margin-top: 0;
}
</style>
