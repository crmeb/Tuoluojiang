<template>
  <div class="divBox">
    <el-card class="card-box">
      <el-form>
        <div v-if="!searchBtn" class="flex-row flex">
          <el-form-item class="select-bar">
            <el-select
              v-model="period"
              style="width: 160px"
              placeholder="请选择"
              @change="handlePeriod"
            >
              <el-option v-for="(item, index) in periodOptions" :key="index" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>

          <el-form-item class="select-bar">
            <el-date-picker
              v-model="time"
              type="daterange"
              v-bind="$pickerProps(pickerOptions)"
              placeholder="选择时间"
              format="YYYY/MM/DD"
              value-format="YYYY/MM/DD"
              range-separator="至"
              start-placeholder="开始时间"
              end-placeholder="结束时间"
              :clearable="false"
              style="width: 250px"
              @change="onchangeTime"
            />
          </el-form-item>

          <div v-if="typeLocal > 0">
            <el-form-item class="select-bar">
              <select-department
                :value="departmentList || []"
                :placeholder="`选择部门`"
                @changeMastart="changeMastart"
                class="mr10"
                :is-search="true"
                style="width: 200px"
              ></select-department>
            </el-form-item>
          </div>
          <div v-if="typeLocal > 0">
            <el-form-item class="select-bar">
              <select-member
                :value="userList || []"
                :placeholder="`选择人员`"
                @getSelectList="getSelectList"
                class="mr10"
                :is-search="true"
                style="width: 200px"
              ></select-member>
            </el-form-item>
          </div>
          <el-form-item>
            <el-tooltip effect="dark" content="重置搜索条件" placement="top">
              <div class="reset" @click="reset"><i class="iconfont iconqingchu"></i></div>
            </el-tooltip>
          </el-form-item>
        </div>
      </el-form>
      <div class="statistics-title mt10">
        <ul>
          <li v-for="(item, index) in tableData" :key="index">
            <span class="icon" :style="{ backgroundColor: colorList[index] }" />
            <span>{{ item.name }}:</span>
            <span>{{ item.min }}-{{ item.max }}分 </span>
            <span class="ml4">{{ item.mark }}</span>
          </li>
        </ul>
      </div>
      <div class="mt20 statistics-con">
        <div>
          <div id="statistics-con" :style="styles1" />
        </div>
      </div>
    </el-card>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import pickerOptions from '@/libs/pickerOptions.ts'
import { assessCensusApi } from '@/api/enterprise.ts'
import Commnt from '@/components/user/accessCommon.ts'
import echarts from 'echarts'
export default {
  name: 'AssessStatistics',
  components: {
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member')),
    selectDepartment: defineAsyncComponent(() => import('@/components/form-common/select-department'))
  },
  props: {
    searchBtn: {
      type: Boolean,
      default: false
    },
    planId: {
      type: String,
      default: ''
    },
    type: {
      type: Number,
      default: 2
    }
  },
  watch: {
    // prop 由父组件更新时同步到本地副本（组件内部改写的是 typeLocal）
    type(val) {
      this.typeLocal = val
    },
    // prop 由父组件更新时同步到本地副本（组件内部改写的是 planIdLocal）
    planId(val) {
      this.planIdLocal = val
    }
  },
  data() {
    return {
      // Vue 3 的 prop 只读，组件内部需要改写时用本地副本
      typeLocal: this.type,
      // Vue 3 的 prop 只读，组件内部需要改写时用本地副本
      planIdLocal: this.planId,
      pickerOptions,
      departmentList: [],
      periodOptions: Commnt.periodOption,
      statusOptions: Commnt.statusOptions,
      userList: [],
      colorList: [
        '#657899',
        '#6FE9EA',
        '#679AFA',
        '#FBB221',
        '#F67878',
        '#00BFFF',
        '#00FFFF',
        '#FFD700',
        '#FAF0E6',
        '#F0F8FF'
      ],
      periodOptions: Commnt.periodOptions,
      fromList: [
        { label: '全部', value: '' },
        { label: '今天', value: 'today' },
        { label: '昨天', value: 'yesterday' },
        { label: '最近7天', value: 'lately7' },
        { label: '最近30天', value: 'lately30' },
        { label: '本月', value: 'month' },
        { label: '本年', value: 'year' }
      ],
      frameArray: [{ name: '选择部门', id: '' }],
      userArray: [{ label: '选择人员', value: '' }],
      tableData: [],
      frame: [],
      period: 2,
      time: [
        dayjs().subtract(1, 'month').startOf('month').format('YYYY/MM/DD'),
        dayjs().subtract(1, 'month').endOf('month').format('YYYY/MM/DD')
      ],
      date:
        dayjs().subtract(1, 'month').startOf('month').format('YYYY/MM/DD') +
        '-' +
        dayjs().subtract(1, 'month').endOf('month').format('YYYY/MM/DD'),
      frame_id: '',
      test_uid: '',
      total: 0,
      totalNumber: 0.8,
      height: 420,
      styles1: {
        height: '570px',
        width: '100%'
      },

      optionData: {}
    }
  },

  mounted() {
    this.getTableData()
  },
  methods: {
    // 选择成员回调
    getSelectList(data) {
      const testUid = []
      data.forEach((item) => {
        testUid.push(item.value)
      })
      this.test_uid = testUid
      this.userList = data
      this.getTableData()
    },
    changeMastart(data) {
      const frame = []
      data.forEach((value) => {
        frame.push(value.id)
      })
      this.frame_id = frame
      this.departmentList = data
      this.getTableData()
    },

    async getTableData() {
      const data = {
        period: this.period,
        time: this.date,
        frame_id: this.frame_id,
        test_uid: this.test_uid,
        number: this.planIdLocal,
        types: this.typeLocal
      }

      const result = await assessCensusApi(data)
      this.tableData = result.data.score
      this.total = result.data.count
      this.init()
      this.frame = result.data.frame ? result.data.frame : []
      this.frameArray = this.frame
      this.frameArray.unshift({ name: '选择部门', id: '' })
    },

    reset() {
      // 统一获取上个月的开始和结束时间
      const lastMonthStart = dayjs().subtract(1, 'month').startOf('month').format('YYYY/MM/DD')
      const lastMonthEnd = dayjs().subtract(1, 'month').endOf('month').format('YYYY/MM/DD')

      // 重置表单数据
      this.period = 2
      this.time = [lastMonthStart, lastMonthEnd]
      this.date = `${lastMonthStart}-${lastMonthEnd}`
      this.frame_id = ''
      this.test_uid = ''
      this.planIdLocal = ''
      this.typeLocal = 2
      this.departmentList = []
      this.userList = []

      // 重新获取表格数据
      this.getTableData()
    },

    handlePeriod(num) {
      this.period = num
      this.frame_id = ''
      this.test_uid = ''

      // 定义一个获取时间范围的函数，减少代码重复
      const getTimeRange = (startFn, endFn) => [
        dayjs()[startFn]().format('YYYY/MM/DD'),
        dayjs()[endFn]().format('YYYY/MM/DD')
      ]

      // 使用对象映射来简化条件判断
      const timeRangeMap = {
        1: () => getTimeRange('startOf', 'endOf').map((fn) => fn.call(dayjs(), 'week')),
        2: () => getTimeRange('startOf', 'endOf').map((fn) => fn.call(dayjs(), 'month')),
        5: () => getTimeRange('startOf', 'endOf').map((fn) => fn.call(dayjs(), 'quarter')),
        3: () => getTimeRange('startOf', 'endOf').map((fn) => fn.call(dayjs(), 'year')),
        4: () => {
          const m = new Date().getMonth() + 1
          return m < 7
            ? [dayjs().format('YYYY/01/01'), dayjs().format('YYYY/06/30')]
            : [dayjs().format('YYYY/07/01'), dayjs().format('YYYY/12/31')]
        }
      }

      this.time = timeRangeMap[num] ? timeRangeMap[num]() : this.time
      this.date = this.time[0] + '-' + this.time[1]
      this.getTableData()
    },

    onchangeTime(e) {
      if (e === null) {
        this.time = ''
        this.date = ''
      } else {
        if (e[0] === e[1]) {
          const lastDate = dayjs(e[0], 'YYYY/MM/DD').endOf('month').format('YYYY/MM/DD')
          this.time[1] = lastDate
          this.date = e[0] + '-' + lastDate
        } else {
          this.date = e[0] + '-' + e[1]
        }
      }
      this.getTableData()
    },
    init() {
      this.optionData = {
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'none'
          },
          formatter: (option) => {
            return `${option[0].name}<br/>${option[0].value}人`
          }
        },
        grid: {
          right: '0',
          bottom: 80
        },
        toolbox: {},
        xAxis: [
          {
            type: 'category',
            axisLine: {
              show: false
            },
            axisTick: {
              show: false,
              alignWithLabel: true
            },
            data: []
          },
          {
            position: 'bottom',
            offset: 30,
            axisLine: {
              show: false
            },
            nameTextStyle: {
              color: '#EEEEEE'
            },
            type: 'category',
            axisTick: {
              show: false,
              alignWithLabel: true
            },
            data: []
          }
        ],
        yAxis: [
          {
            type: 'value',
            position: 'left',
            axisLine: {
              show: false
            },
            splitLine: {
              show: false
            },
            axisTick: {
              show: false,
              alignWithLabel: true
            },
            axisLabel: {
              show: false
            }
          }
        ],
        series: [
          {
            name: '',
            type: 'bar',
            itemStyle: {
              normal: {
                color: (params) => {
                  const colorList = this.colorList
                  return colorList[params.dataIndex]
                },
                label: {
                  show: true,
                  color: '#000000',
                  position: 'top',
                  formatter: (params) => {
                    return this.total == 0 ? 0 : ((params.value / this.total) * 100).toFixed(2) + '%'
                  }
                }
              }
            },
            data: []
          }
        ]
      }
      const xAxisName = []
      const xAxisCount = []
      const seriesData = []
      if (this.tableData.length > 0) {
        this.tableData.forEach((value) => {
          xAxisName.push(value.name)
          xAxisCount.push(!this.test_uid ? value.count + ' 人' : value.count + ' 次')
          seriesData.push(value.count)
        })
        this.optionData.xAxis[0].data = xAxisName
        this.optionData.xAxis[1].data = xAxisCount
        this.optionData.series[0].data = seriesData
      }
      this.myChart = echarts.init(document.getElementById('statistics-con'))
      let option = null
      option = this.optionData
      // 基于准备好的dom，初始化echarts实例
      this.myChart.setOption(option, true)
    }
  }
}
</script>
<style lang="scss" scoped>
.card-box {
  height: calc(100vh - 76px);
  overflow-y: scroll;
}

.ml4 {
  display: inline-block;
  margin-left: 4px;
}

.plan-footer-one {
  height: 26px;
  line-height: 28px;
}

.statistics-title {
  ul,
  li {
    padding: 0;
    margin: 0;
    list-style: none;
  }
  ul {
    display: flex;
    align-items: center;
    li {
      display: flex;
      align-items: center;
      margin-right: 20px;
      font-size: 13px;
      color: #000000;
    }
    li:last-of-type {
      margin-right: 0;
    }
  }
  .icon {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #00b7ee;
    margin-right: 4px;
  }
}

:deep(.el-range-editor .el-range-input) {
  font-size: 13px;
  cursor: pointer;
}

.el-form-item {
  margin-right: 10px;
}
.statistics-con {
  .statistics-con-ul {
    p {
      margin: 0;
      padding: 0;
    }
    ul,
    li {
      padding: 0;
      margin: 0;
      list-style: none;
    }
    display: flex;
    align-items: flex-end;
    align-content: center;
    justify-content: center;
    li {
      text-align: center;
      font-size: 13px;
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 70px;
      margin-right: 20px;
      p {
        margin-bottom: 15px;
      }
      p:last-of-type {
        margin-bottom: 0;
      }
      .icon-bar {
        width: 100%;
        height: 85%;
        background-color: #00b7ee;
      }
      .people {
        color: rgba(0, 0, 0, 0.3);
      }
      .level {
        color: rgba(0, 0, 0, 0.85);
      }
    }
    li:last-of-type {
      margin-right: 0;
    }
  }
}
</style>
