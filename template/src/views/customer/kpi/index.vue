<template>
  <div class="divBox">
    <div>
      <el-card class="employees-card-bottom">
        <div class="plan-tabs-content mb20">
          <el-tabs v-model="where.link_type" @tab-click="tapClick">
            <el-tab-pane label="部门目标" name="1" />
            <el-tab-pane label="业务员目标" name="0" />
          </el-tabs>
          <div>
            <el-button class="btn-create" size="small" type="primary" @click="addFinance">设置目标</el-button>
            <el-button size="small" class="btn-create" v-if="!editShow" @click="editFn"> 编辑 </el-button>
            <el-button size="small" class="btn-create" type="primary" v-if="editShow" @click="saveFinance">
              保存
            </el-button>
            <el-button size="small" class="btn-create" v-if="editShow" @click="editShow = false"> 取消 </el-button>
          </div>
        </div>
        <!-- 筛选 -->
        <div class="flex mb10">
          <el-date-picker
            v-model="where.year"
            type="year"
            size="small"
            format="yyyy"
            value-format="yyyy"
            placeholder="选择年"
            @change="getTableData"
          >
          </el-date-picker>
          <select-department
            v-if="where.link_type == 1"
            :onlyOne="false"
            :value="userList"
            :isSearch="true"
            @changeMastart="changeMastart"
            style="width: 250px"
            class="ml10 mr10"
          ></select-department>
          <select-member
            v-if="where.link_type == 0"
            class="ml10 mr10"
            :onlyOne="false"
            :selectIdData="where.user_id"
            :isSearch="true"
            @getSelectList="getSelectList"
            style="width: 250px"
          >
          </select-member>
          <el-tooltip effect="dark" content="重置搜索条件" placement="top">
            <div class="reset" @click="reset"><i class="iconfont iconqingchu"></i></div>
          </el-tooltip>
        </div>

        <!-- 表格 -->
        <el-table
          :data="tableData"
          ref="tableRef"
          v-loading="loading"
          :height="height"
          :cell-style="tableCellStyle"
          row-key="link_id"
          style="width: 100%"
        >
          <el-table-column
            v-for="(item, index) in header"
            :key="index"
            :prop="item.prop"
            :label="item.label"
            min-width="145"
            :fixed="item.prop == 'user' ? true : false"
          >
            <template #default="scope">
              <el-input-number
                size="small"
                v-if="editShow && !disabled.includes(item.prop) && scope.row.type !== 'total'"
                v-model="scope.row[item.prop]"
                :controls="false"
                :precision="2"
                @change="handleChange($event, scope.row)"
                :min="0"
                style="width: 120px"
              ></el-input-number>

              <div v-else>
                <span v-if="item.prop == 'user'" class="over-text1">
                  {{ scope.row.user && typeof scope.row.user === 'object' && scope.row.user.name ? scope.row.user.name : '--' }}
                </span>
                <span v-else> {{ scope.row[item.prop] }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="操作" fixed="right" width="80">
            <template #default="scope">
              <el-button type="text" v-if="scope.row.user && typeof scope.row.user === 'object' && scope.row.user.name != '目标合计'" @click="delFn(scope.$index, scope.row)">
                删除</el-button
              >
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </div>
    <!-- 添加目标设置 -->
    <addKpi ref="addKpi" @getTableData="getTableData"></addKpi>
  </div>
</template>
<script>
import moment from 'moment'
import { modalSure } from '@/libs/public'
import { clientTargetsApi, clientTargetPutApi, clientTargetDelApi } from '@/api/client'
export default {
  name: 'kpi',
  components: {
    selectMember: () => import('@/components/form-common/select-member'),
    addKpi: () => import('./components/addKpi'),
    selectDepartment: () => import('@/components/form-common/select-department')
  },
  data() {
    return {
      loading: false,
      editShow: false,
      userList: [],
      height: ' calc(100vh - 209px)',
      where: { year: '', frame_id: [], user_id: [], link_type: '1' },
      header: [
        {
          label: '部门名称',
          prop: 'user'
        },
        {
          label: '全年',
          prop: 'annual'
        },
        {
          label: '第一季度',
          prop: 'q1'
        },
        {
          label: '1月',
          prop: 'month1'
        },
        {
          label: '2月',
          prop: 'month2'
        },
        {
          label: '3月',
          prop: 'month3'
        },
        {
          label: '第二季度',
          prop: 'q2'
        },
        {
          label: '4月',
          prop: 'month4'
        },
        {
          label: '5月',
          prop: 'month5'
        },
        {
          label: '6月',
          prop: 'month6'
        },
        {
          label: '第三季度',
          prop: 'q3'
        },
        {
          label: '7月',
          prop: 'month7'
        },
        {
          label: '8月',
          prop: 'month8'
        },
        {
          label: '9月',
          prop: 'month9'
        },
        {
          label: '第四季度',
          prop: 'q4'
        },
        {
          label: '10月',
          prop: 'month10'
        },
        {
          label: '11月',
          prop: 'month11'
        },
        {
          label: '12月',
          prop: 'month12'
        }
      ],
      disabled: ['user', 'annual', 'q1', 'q2', 'q3', 'q4'],

      tableData: [
        {
          departmentName: '事业一部',
          annual: 0,
          q1: 0,
          month1: 0,
          month2: 0,
          month3: 0,
          q2: 0,
          month4: 0,
          month5: 0,
          month6: 0,
          q3: 0,
          month9: 0,
          month8: 0,
          month7: 0,
          month10: 0,
          month11: 0,
          month12: 0,
          q4: 0
        }
      ]
    }
  },
  mounted() {
    this.where.year = moment().format('YYYY')
    this.getTableData()
  },
  methods: {
    reset() {
      this.where.year = moment().format('YYYY')
      this.where.frame_id = []
      this.where.user_id = []
      this.userList = []
      this.getTableData()
    },
    // 获取表格数据
    async getTableData() {
      // 显示加载状态
      this.loading = true

      // 发起API请求获取数据
      const res = await clientTargetsApi(this.where)

      // 处理数据
      if (res && res.data) {
        this.tableData = res.data

        // 对每条数据进行处理
        this.tableData.forEach((item) => {
          this.handleChange('', item)
        })

        // 所有数据处理完成后再计算总和，避免重复计算
        this.totalFn()
        this.$nextTick(() => {
          // 确保 DOM 更新后执行
          this.$refs.tableRef.doLayout()
        })
        this.loading = false
      } else {
        this.$message.warning('未获取到有效数据')
        this.tableData = []
      }
    },
    editFn() {
      this.editShow = true
      this.$nextTick(() => {
        // 确保 DOM 更新后执行
        this.$refs.tableRef.doLayout()
      })
    },

    // 删除
    delFn(index, item) {
      modalSure('您确定要删除此数据吗').then(() => {
        let obj = {
          year: item.year,
          link_id: item.link_id,
          link_type: this.where.link_type
        }
        clientTargetDelApi(obj).then((res) => {
          this.getTableData()
        })
      })
    },

    // 设置单元格背景色
    tableCellStyle({ row, column, rowIndex, columnIndex }) {
      let color = [1, 2, 6, 10, 14]
      if (color.includes(columnIndex)) {
        return 'background-color: #F7F7F7;'
      }
      if (rowIndex == this.tableData.length - 1) {
        return 'background-color: #F7F7F7;'
      }
    },

    saveFinance() {
      const arr = []
      this.tableData.pop()
      this.tableData.map((item) => {
        for (let i = 1; i <= 12; i++) {
          let obj = {
            link_id: item.link_id,
            link_type: this.where.link_type,
            year: item.year,
            amount: item['month' + i],
            month: i
          }
          arr.push(obj)
        }
      })
      clientTargetPutApi({ data: arr }).then((res) => {
        this.editShow = false
        this.getTableData()
      })
    },

    handleChange(val, row) {
      row.q1 = Number([row.month1, row.month2, row.month3].reduce((sum, num) => sum + num, 0).toFixed(2))
      row.q2 = Number([row.month4, row.month5, row.month6].reduce((sum, num) => sum + num, 0).toFixed(2))
      row.q3 = Number([row.month7, row.month8, row.month9].reduce((sum, num) => sum + num, 0).toFixed(2))
      row.q4 = Number([row.month10, row.month11, row.month12].reduce((sum, num) => sum + num, 0).toFixed(2))
      row.annual = Number([row.q1, row.q2, row.q3, row.q4].reduce((sum, num) => sum + num, 0).toFixed(2))
    },

    //   计算最后一行数据目标总和
    totalFn() {
      if (this.tableData.length <= 0) return
      this.tableData.push({ user: { name: '目标合计' } })
      let lastRow = {}

      lastRow = this.tableData[this.tableData.length - 1]

      // 需要计算总和的字段列表
      const sumFields = [
        'annual',
        'q1',
        'q2',
        'q3',
        'q4',
        'month1',
        'month2',
        'month3',
        'month4',
        'month5',
        'month6',
        'month7',
        'month8',
        'month9',
        'month10',
        'month11',
        'month12'
      ]

      // 重置最后一行所有需要计算的字段
      sumFields.forEach((field) => {
        lastRow[field] = 0
      })

      this.tableData.slice(0, -1).forEach((currentRow) => {
        sumFields.forEach((field) => {
          // 处理可能的非数字值，确保累加正确
          const currentValue = Number(currentRow[field]) || 0
          lastRow[field] += currentValue
        })
      })

      // 对所有累加结果保留两位小数
      sumFields.forEach((field) => {
        lastRow[field] = Number(lastRow[field].toFixed(2))
      })
    },
    tapClick(tab, event) {
      if (tab.name == 0) {
        this.header[0].label = '业务员名称'
      } else {
        this.header[0].label = '部门名称'
      }
      this.editShow = false
      this.where.frame_id = []
      this.where.user_id = []
      this.userList = []
      this.where.link_type = tab.name
      this.getTableData()
    },
    addFinance() {
      this.$refs.addKpi.openBox(this.where.link_type)
    },
    getSelectList(val) {
      this.userList = val
      this.where.user_id = []

      val.map((item) => {
        this.where.user_id.push(item.value)
      })
      this.getTableData()
    },
    changeMastart(val) {
      this.userList = val
      this.where.frame_id = []
      val.map((item) => {
        this.where.frame_id.push(item.id)
      })
      this.getTableData()
    }
  }
}
</script>
<style lang="scss" scoped>
.btn-create {
  margin-top: -15px;
}

.plan-tabs-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e6ebf5;

  ::v-deep .el-tabs__header {
    margin-bottom: 0;

    .el-tabs__item {
      font-size: 15px;
    }

    .el-tabs__nav-wrap::after {
      height: 1px;
      background-color: #eee;
    }
  }
}
</style>
