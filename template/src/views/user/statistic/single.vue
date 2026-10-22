<template>
  <div class="divBox">
    <!-- 个人统计 -->
    <div class="box-height">
      <el-card>
        <oaFromBox
          :isAddBtn="false"
          :isTotal="false"
          :isViewSearch="false"
          :search="search"
          :sortSearch="false"
          @confirmData="confirmData"
        >
        </oaFromBox>
        <!-- 考勤统计 -->
        <div class="flex-box mt14">
          <div class="left">
            <div class="title">
              平均工时(小时) <span class="num">{{ work_hours || 0 }}</span>
            </div>

            <echartBox :option-data="optionData" :styles="styles" />
          </div>
          <div class="right">
            <div class="top">
              <div class="title">异常考勤汇总</div>
              <div class="right-box">
                <div v-for="(item, index) in attendanceList" :key="index" class="attendance">
                  <img :src="item.img" alt="" />
                  <div class="attendance-days">
                    <div class="day">{{ item.num || 0 }}</div>
                    <div class="tips">{{ item.title }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="lower">
              <div class="title">打卡异常汇总</div>
              <div class="right-box">
                <div v-for="(item, index) in clockInList" :key="index" class="attendance">
                  <img :src="item.img" alt="" />
                  <div class="attendance-days">
                    <div class="day">{{ item.num || 0 }}</div>
                    <div class="tips">{{ item.title }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </el-card>
      <!-- 考勤表格 -->
      <el-card class="mt14">
        <div class="mb10 flex">
          <span class="title-k">个人打卡统计</span>
          <el-select v-model="where.status" clearable placeholder="请选择打卡结果" @change="getDataList">
            <el-option v-for="item in options" :key="item.id" :label="item.name" :value="item.id"> </el-option>
          </el-select>
        </div>
        <!-- 表格 -->
        <el-table :data="tableData" style="width: 100%">
          <el-table-column label="姓名" prop="card.name"> </el-table-column>
          <el-table-column label="考勤班次" min-width="150px" prop="name">
            <template #default="{ row }">
              <div>{{ row.shift_data.name }}</div>
              <span>{{ getShift(row.shift_data) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="日期" prop="created_at" width="160">
            <template #default="{ row }">
              {{ formatDate(row.created_at) }}
            </template>
          </el-table-column>

          <el-table-column label="上班1">
            <el-table-column label="最早打卡" min-width="150" prop="province">
              <template #default="{ row }">
                <div v-if="row.one_shift_time">{{ row.one_shift_is_after ? '次日' : '当日' }}</div>
                {{ formatTime(row.one_shift_time) }}
              </template>
            </el-table-column>
            <el-table-column label="打卡结果" prop="city" width="120">
              <template #default="{ row }">
                <span :class="isAbnormal(row.one_shift_status, row.one_shift_location_status, row, 1) ? 'red' : ''">
                  {{ getStatus(row.one_shift_status, row.one_shift_location_status, row) }}
                  <span v-if="1 < row.one_shift_status < 5 && row.one_shift_normal !== 0"
                    >-{{ row.one_shift_normal }}分钟</span
                  >
                </span>
              </template>
            </el-table-column>
          </el-table-column>
          <el-table-column label="下班1">
            <el-table-column label="最晚打卡" prop="province" width="120">
              <template #default="{ row }">
                <div v-if="row.two_shift_time">{{ row.two_shift_is_after == 0 ? '当日' : '次日' }}</div>
                {{ formatTime(row.two_shift_time) }}
              </template>
            </el-table-column>
            <el-table-column label="打卡结果" prop="city" width="120">
              <template #default="{ row }">
                <span :class="isAbnormal(row.two_shift_status, row.two_shift_location_status, row, 0) ? 'red' : ''">
                  {{ getStatus(row.two_shift_status, row.two_shift_location_status, row) }}
                  <span v-if="1 < row.two_shift_status < 5 && row.two_shift_normal !== 0"
                    >-{{ row.two_shift_normal }}分钟</span
                  >
                </span>
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column label="上班2">
            <el-table-column label="最早打卡" prop="province" width="120">
              <template #default="{ row }">
                <div v-if="row.three_shift_time">{{ row.three_shift_is_after == 0 ? '当日' : '次日' }}</div>
                {{ formatTime(row.three_shift_time) }}
              </template>
            </el-table-column>
            <el-table-column label="打卡结果" prop="city" width="120">
              <template #default="{ row }">
                <span
                  v-if="row.three_shift_status"
                  :class="isAbnormal(row.three_shift_status, row.three_shift_location_status, row, 0) ? 'red' : ''"
                >
                  {{ getStatus(row.three_shift_status, row.three_shift_location_status, row) }}
                  <span v-if="1 < row.one_shift_status < 5 && row.three_shift_normal !== 0"
                    >-{{ row.three_shift_normal }}分钟</span
                  >
                </span>
                <span v-else>--</span>
              </template>
            </el-table-column>
          </el-table-column>
          <el-table-column label="下班2">
            <el-table-column label="最晚打卡" prop="province" width="120">
              <template #default="{ row }">
                <div v-if="row.four_shift_time">{{ row.four_shift_is_after == 0 ? '当日' : '次日' }}</div>
                {{ formatTime(row.four_shift_time) }}
              </template>
            </el-table-column>
            <el-table-column label="打卡结果" prop="city" width="120">
              <template #default="{ row }">
                <span
                  v-if="row.four_shift_status"
                  :class="isAbnormal(row.four_shift_status, row.four_shift_location_status, row, 0) ? 'red' : ''"
                >
                  {{ getStatus(row.four_shift_status, row.four_shift_location_status, row) }}
                  <span v-if="1 < row.one_shift_status < 5 && row.four_shift_normal !== 0"
                    >-{{ row.four_shift_normal }}分钟</span
                  >
                </span>
                <span v-else>--</span>
              </template>
            </el-table-column>
          </el-table-column>
          <el-table-column label="时长统计（小时）">
            <el-table-column label="应出勤" prop="required_work_hours" width="120"> </el-table-column>
            <el-table-column label="实际出勤" prop="actual_work_hours" width="120"> </el-table-column>
            <el-table-column label="加班时长" prop="overtime_work_hours" width="120"> </el-table-column>
            <el-table-column label="请假时长" prop="leave_time" width="120"> </el-table-column>
          </el-table-column>
        </el-table>

        <el-pagination
          :current-page="where.page"
          :page-size="where.limit"
          :page-sizes="[10, 15, 20]"
          :total="totalPage"
          layout="total, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </el-card>
    </div>
  </div>
</template>
<script>
import moment from 'moment'
import { attendanceStatistics, individualStatistics } from '@/api/user'

// 预加载图片资源，避免在渲染时多次加载
import clock1 from '../../../assets/images/clock1.png'
import clock2 from '../../../assets/images/clock2.png'
import clock3 from '../../../assets/images/clock3.png'
import clock4 from '../../../assets/images/clock4.png'
import clock5 from '../../../assets/images/clock5.png'
import single1 from '../../../assets/images/single1.png'
import single2 from '../../../assets/images/single2.png'
import single3 from '../../../assets/images/single3.png'
import single4 from '../../../assets/images/single4.png'
import single5 from '../../../assets/images/single5.png'
import { divTime } from '@/utils'

// 缺卡状态：后端历史枚举 5 与现行返回值 7 并存，两者都按缺卡处理
const LACK_CARD_STATUS = [5, 7]

const clockImages = {
  clock1,
  clock2,
  clock3,
  clock4,
  clock5,
  single1,
  single2,
  single3,
  single4,
  single5
}

export default {
  name: 'SingleStatistic',
  components: {
    echartBox: () => import('@/components/common/echarts'),
    oaFromBox: () => import('@/components/common/oaFromBox')
  },
  data() {
    return {
      work_hours: 0, // 平均工时
      required_days: 0, // 应出勤天数
      absenteeism: 0, // 未出勤
      normal_days: 0, // 实际出勤
      userList: [],
      search: [
        {
          field_name: '开始时间',
          field_name_end: '结束时间',
          field_name_en: 'time',
          form_value: 'date_picker',
          data_dict: this.getCurrentMonthRange() // 默认本月
        },
        {
          field_name: '请选择人员',
          field_name_en: 'user_id',
          form_value: 'user_id',
          data_dict: [],
          value: [],
          onlyOne: true // 设置为单选
        }
      ],
      totalPage: 0,
      options: [
        { id: 1, name: '正常' },
        { id: 2, name: '迟到' },
        { id: 3, name: '严重迟到' },
        { id: 4, name: '早退' },
        { id: 5, name: '缺卡' },
        { id: 6, name: '地点异常' }
      ],
      clockInList: [
        { num: 0, img: clockImages.clock1, title: '迟到(次)' },
        { num: 0, img: clockImages.clock2, title: '早退(次)' },
        { num: 0, img: clockImages.clock3, title: '缺卡(次)' },
        { num: 0, img: clockImages.clock5, title: '地点异常(次)' },
        { num: 0, img: clockImages.clock4, title: '旷工(天)' }
      ],
      attendanceList: [
        { num: 0, img: clockImages.single1, title: '请假(小时)' },
        { num: 0, img: clockImages.single2, title: '出差(天)' },
        { num: 0, img: clockImages.single3, title: '外出(小时)' },
        { num: 0, img: clockImages.single4, title: '加班(小时)' },
        { num: 0, img: clockImages.single5, title: '补卡(次)' }
      ],

      where: {
        time: this.getDefaultTimeRange(),
        user_id: '',
        status: '',
        page: 1,
        limit: 15
      },
      defaultUse: {},
      tableData: [],
      optionData: {},
      styles: {
        height: '256px',
        width: '256px',
        margin: 'auto'
      }
    }
  },
  computed: {
    // 计算平均工时显示值
    workHoursDisplay() {
      return this.work_hours || 0
    },

    // 打卡状态映射
    statusMap() {
      return {
        0: '正常',
        1: '正常',
        2: '迟到',
        3: '严重迟到',
        4: '早退',
        5: '缺卡',
        7: '缺卡'
      }
    },

    // 地点状态映射
    locationStatusMap() {
      return {
        0: '',
        1: '(外勤卡)',
        2: '(地点异常)'
      }
    }
  },
  mounted() {
    this.initializeComponent()
  },
  methods: {
    // 初始化组件
    initializeComponent() {
      const userInfo = this.getUserInfo()
      if (userInfo) {
        this.defaultUse = {
          label: userInfo.name,
          value: userInfo.id,
          name: userInfo.name,
          id: userInfo.id
        }

        this.userList.push(this.defaultUse)
        this.where.user_id = userInfo.value || userInfo.id

        this.getOptionData()
        this.getList()
        this.getDataList()
      }
    },

    // 获取用户信息
    getUserInfo() {
      try {
        const userInfoStr = localStorage.getItem('userInfo')
        return userInfoStr ? JSON.parse(userInfoStr) : null
      } catch (error) {
        console.error('获取用户信息失败:', error)
        return null
      }
    },

    // 获取本月时间范围 [开始日期, 结束日期]
    getCurrentMonthRange() {
      return [
        moment().startOf('month').format('YYYY/MM/DD'),
        moment().endOf('month').format('YYYY/MM/DD')
      ]
    },

    // 获取默认时间范围（本月）
    getDefaultTimeRange() {
      return this.getCurrentMonthRange().join('-')
    },

    // 将时间筛选恢复为本月，同步筛选框展示与查询参数
    resetTimeRange() {
      const range = this.getCurrentMonthRange()
      const timeSearch = this.search.find((item) => item.field_name_en === 'time')
      if (timeSearch) {
        timeSearch.data_dict = range
      }
      this.where.time = range.join('-')
    },

    confirmData(data) {
      if (data === 'reset') {
        this.resetFilters()
      } else {
        // 更新查询参数
        Object.keys(data).forEach((key) => {
          if (this.where.hasOwnProperty(key)) {
            if (Array.isArray(data[key])) {
              this.where[key] = data[key].join(',')
            } else {
              this.where[key] = data[key]
            }
          }
        })
      }

      this.refreshData()
    },

    // 重置过滤器
    resetFilters() {
      this.where.user_id = []
      this.where.status = ''
      this.resetTimeRange()
    },

    // 刷新数据
    refreshData() {
      this.getDataList()
      this.getList()
    },

    getSelectList(data) {
      this.userList = data
      if (data && data.length > 0) {
        this.where.user_id = data[0].value
      }
      this.timeChange()
    },

    timeChange() {
      this.refreshData()
    },

    async getDataList() {
      const data = {
        time: this.where.time,
        user_id: this.where.user_id,
        status: this.where.status,
        page: this.where.page,
        limit: this.where.limit
      }

      try {
        const result = await individualStatistics(data)
        this.tableData = result.data?.list || []
        this.totalPage = result.data?.count || 0
      } catch (error) {
        console.error('获取个人统计数据失败:', error)
        this.tableData = []
        this.totalPage = 0
      }
    },

    handleSizeChange(val) {
      this.where.page = 1
      this.where.limit = val
      this.getDataList()
    },

    handleCurrentChange(page) {
      this.where.page = page
      this.getDataList()
    },

    getList() {
      const data = {
        time: this.where.time,
        user_id: this.where.user_id
      }

      attendanceStatistics(data)
        .then((res) => {
          if (res && res.data) {
            const data = res.data
            this.work_hours = data.work_hours || 0
            this.absenteeism = data.absenteeism || 0
            this.required_days = data.required_days || 0
            this.normal_days = data.normal_days || 0

            // 更新考勤列表数据
            this.updateAttendanceList(data)

            // 更新打卡列表数据
            this.updateClockInList(data)

            this.getOptionData()
          }
        })
        .catch((error) => {
          console.error('获取考勤统计失败:', error)
        })
    },

    // 更新考勤列表数据
    updateAttendanceList(data) {
      this.attendanceList[0].num = data.leave_hours || 0
      this.attendanceList[1].num = data.trip_hours || 0
      this.attendanceList[2].num = data.out_hours || 0
      this.attendanceList[3].num = data.overtime_hours || 0
      this.attendanceList[4].num = data.sign || 0
    },

    // 更新打卡列表数据
    updateClockInList(data) {
      this.clockInList[0].num = data.late || 0
      this.clockInList[1].num = data.early_leave || 0
      this.clockInList[2].num = data.lack_card || 0
      this.clockInList[3].num = data.location_abnormal || 0
      this.clockInList[4].num = data.absenteeism_days || data.absenteeism || 0
    },

    // 处理表格班次数据
    getShift(data) {
      if (!data || !data.rules || data.rules.length === 0) {
        return ''
      }

      const formatRule = (rule) => {
        const startDay = rule.first_day_after == 0 ? '当日' : '次日'
        const endDay = rule.second_day_after == 0 ? '当日' : '次日'
        return `${startDay}${rule.work_hours} - ${endDay}${rule.off_hours}`
      }

      const text1 = formatRule(data.rules[0])
      const text2 = data.rules[1] ? formatRule(data.rules[1]) : ''

      return text1 + text2
    },

    // 判断该班次打卡结果是否为缺卡
    isLackCard(status) {
      return LACK_CARD_STATUS.includes(Number(status))
    },

    // 判断该班次的缺卡是否由请假造成（接口仅提供当日请假总时长，不区分具体班次）
    isLeaveShift(status, row) {
      return this.isLackCard(status) && Number(row && row.leave_time) > 0
    },

    // 判断打卡结果是否需要标红，请假不算考勤异常
    isAbnormal(status, locationStatus, row, locationLimit) {
      if (this.isLeaveShift(status, row)) {
        return false
      }
      return status > 1 || locationStatus > locationLimit
    },

    // 处理表格打卡结果数据
    getStatus(status, locationStatus, row) {
      if (this.isLeaveShift(status, row)) {
        return '请假'
      }
      const statusText = this.statusMap[status] || '未知'
      const locationText = this.locationStatusMap[locationStatus] || ''
      return statusText + locationText
    },

    // 格式化日期为年月日
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      return `${year}-${month}-${day}`
    },

    // 格式化时间为时分秒
    formatTime(timeString) {
      if (!timeString) return '--'
      // 如果时间字符串包含日期和时间，则只提取时间部分
      if (timeString.includes('T')) {
        const timePart = new Date(timeString).toTimeString()
        return timePart.substring(0, 8) // HH:MM:SS
      } else if (timeString.includes(' ')) {
        const parts = timeString.split(' ')
        const timePart = parts[1]
        return timePart ? timePart.substring(0, 8) : '--'
      } else {
        // 如果已经是时间格式（HH:MM:SS 或 HH:MM），则截取到分钟或秒
        const timeParts = timeString.split(':')
        if (timeParts.length >= 2) {
          return timeParts[0] + ':' + timeParts[1] + ':' + (timeParts[2] || '00')
        } else {
          return timeString
        }
      }
    },

    getOptionData() {
      const absenteeism = this.absenteeism || 0
      const normalDays = this.normal_days || 0
      const days = this.required_days || 0

      const dataArr = [
        { value: normalDays, name: '实际出勤(天)' },
        { value: absenteeism, name: '未出勤(天)' }
      ]

      this.optionData = {
        color: ['#1890FF', '#FF9900'],
        tooltip: {
          trigger: 'item'
        },
        legend: {
          bottom: '1%',
          left: 'center',
          icon: 'circle',
          itemWidth: 10,
          textStyle: {
            fontSize: 13,
            color: '#606266',
            lineHeight: 20
          },
          formatter: (name) => {
            const item = dataArr.find((d) => d.name === name)
            const count = item ? item.value : 0
            return `${name}  ${count}`
          }
        },
        series: [
          {
            type: 'pie',
            radius: ['45%', '70%'],
            avoidLabelOverlap: false,
            label: {
              show: true,
              position: 'center',
              formatter: `${days}\n{name|应出勤天数}`,
              color: '#333',
              textStyle: {
                fontSize: 18,
                fill: '#333',
                lineHeight: 26,
                rich: {
                  name: {
                    fontSize: 14,
                    fill: '#333',
                    color: '#3D3D3D'
                  }
                }
              }
            },
            labelLine: {
              show: false
            },
            data: dataArr
          }
        ]
      }
    },

    reset() {
      this.resetTimeRange()
      this.userList = []
      this.where.user_id = ''
      this.refreshData()
    }
  }
}
</script>
<style lang="scss" scoped>
.plan-footer-one {
  height: 26px;
  line-height: 28px;
}
.flex {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.flex-box {
  height: 290px;
  display: flex;
  .left {
    width: 302px;
    .title {
      text-align: center;
      font-size: 13px;
      font-family: PingFang SC-Medium, PingFang SC;
      font-weight: 500;
      color: #606266;
      .num {
        display: inline-block;
        font-size: 24px;
        font-weight: 600;
        color: #1890ff;
        margin-left: 10px;
      }
      .box {
        width: 156px;
        height: 156px;
      }
    }
  }
  .right {
    flex: 1;
    display: flex;
    flex-direction: column;
    .title {
      line-height: 30px;

      font-size: 13px;
      font-family: PingFang SC-Medium, PingFang SC;
      font-weight: 500;
      color: #606266;
    }
    .right-box {
      margin-top: 13px;
      width: 100%;
      height: 92px;
      background-color: #f7fbff;
      padding: 24px 0px;
      padding-right: 0;
      display: flex;

      // justify-content: space-between;
      .attendance {
        margin-left: 30px;
        width: 20%;
        display: flex;
        .attendance-days {
          margin-left: 21px;
          display: flex;
          flex-direction: column;
          .day {
            font-size: 24px;
            font-family: PingFang SC-Semibold, PingFang SC;
            font-weight: 600;
            color: #303133;
          }
          .tips {
            font-size: 13px;
            font-family: PingFang SC-Regular, PingFang SC;
            font-weight: 400;
            color: #606266;
            margin-top: 2px;
          }
        }
      }
    }
    .top {
      width: 100%;
      height: 145px;
    }
    .lower {
      flex: 1;
      width: 100%;
    }
  }
}
::v-deep .el-table thead.is-group th {
  background-color: rgba(247, 251, 255, 1);
  border-color: #fff;
}
::v-deep .el-table {
  border: none;
}

::v-deep .el-table td {
  border: none;
}
.red {
  color: red;
}
.title-k {
  font-size: 16px;
  font-weight: 500;
}
</style>
