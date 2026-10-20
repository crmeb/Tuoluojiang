<template>
  <!-- Element UI 的 Dialog 弹窗组件 -->
  <el-dialog :title="tabCur == 1 ? '设置部门目标' : '设置业务员目标'" v-model="dialogVisible" width="660px">
    <div class="flex" style="display: flex; gap: 10px" v-if="dialogVisible">
      <select-department
        :onlyOne="false"
        isSearch="true"
        v-if="tabCur == 1"
        @changeMastart="changeMastart"
        style="width: 250px"
      ></select-department>

      <select-member
        v-if="tabCur == 0"
        isSearch="true"
        :onlyOne="false"
        @getSelectList="getSelectList"
        style="width: 250px"
      >
      </select-member>
      <el-date-picker
        v-model="form.year"
        type="year"
        format="YYYY"
        value-format="YYYY"
        placeholder="选择年"
      >
      </el-date-picker>
      <el-input v-model="monthData.annual" style="width: 250px" placeholder="请输入年度目标" suffix="元" />
      <el-button type="primary" @click="averageAllocateToMonthly"> 目标平均分配到每月 </el-button>
    </div>
    <!-- 季度、月度目标输入区域 -->
    <el-row :gutter="10" class="mt20">
      <el-col :span="6" v-for="(quarter, index) in quarters" :key="index">
        <div class="quarter-box">
          <div class="title">{{ quarter }}季度</div>
          <div v-for="(month, monthIndex) in month[index]" :key="monthIndex">
            <div v-if="month.title" class="title">{{ month.title }}月份</div>
            <el-input-number
              v-model="monthData[month.key]"
              :controls="false"
              :precision="2"
              :min="0"
              @change="handleChange($event, month)"
              :disabled="!month.title"
              class="input"
            >
            </el-input-number>
            <el-divider v-if="!month.title" style="width: 100%; padding: 0"></el-divider>
          </div>
        </div>
      </el-col>
    </el-row>

    <!-- 底部操作按钮 -->
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="reset()">取消</el-button>
        <el-button type="primary" @click="confirm">确定</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import { clientTargetPutApi } from '@/api/client.ts'
export default {
  emits: ['getTableData'],
  name: 'addKpi',
  components: {
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member')),
    selectDepartment: defineAsyncComponent(() => import('@/components/form-common/select-department'))
  },
  data() {
    return {
      tabCur: 1,
      dialogVisible: false,
      form: {
        link_id: '',
        year: dayjs().format('YYYY'),
        link_type: ''
      },
      listData: [],
      monthData: {
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
      },

      quarters: ['第一', '第二', '第三', '第四'],
      month: [
        [
          {
            key: 'q1'
          },
          {
            key: 'month1',
            title: 1
          },
          {
            key: 'month2',
            title: 2
          },
          {
            key: 'month3',
            title: 3
          }
        ],
        [
          {
            key: 'q2'
          },
          {
            key: 'month4',
            title: 4
          },
          {
            key: 'month5',
            title: 5
          },
          {
            key: 'month6',
            title: 6
          }
        ],
        [
          {
            key: 'q3'
          },
          {
            key: 'month7',
            title: 7
          },
          {
            key: 'month8',
            title: 8
          },
          {
            key: 'month9',
            title: 9
          }
        ],
        [
          {
            key: 'q4'
          },
          {
            key: 'month10',
            title: 10
          },
          {
            key: 'month11',
            title: 11
          },
          {
            key: 'month12',
            title: 12
          }
        ]
      ],
      userList: [],
      quarterMonths: [
        [1, 2, 3],
        [4, 5, 6],
        [7, 8, 9],
        [10, 11, 12]
      ]
    }
  },
  methods: {
    // 平均分配年度目标到每月
    averageAllocateToMonthly() {
      // 验证年度目标是否存在且为有效数字
      if (
        this.monthData.annual === undefined ||
        this.monthData.annual === null ||
        isNaN(Number(this.monthData.annual)) ||
        Number(this.monthData.annual) <= 0
      ) {
        this.$message.error('请输入有效的年度目标（正数）')
        return
      }

      // 转换为数字类型并保留两位小数
      const annualTarget = Number(this.monthData.annual).toFixed(2)
      const quarterlyTarget = (annualTarget / 4).toFixed(2)
      const monthlyTarget = (annualTarget / 12).toFixed(2)

      // 定义季度和月份数据映射关系
      const quarterMap = {
        q1: ['month1', 'month2', 'month3'],
        q2: ['month4', 'month5', 'month6'],
        q3: ['month7', 'month8', 'month9'],
        q4: ['month10', 'month11', 'month12']
      }

      // 分配季度目标和月度目标
      Object.entries(quarterMap).forEach(([quarter, months]) => {
        this.monthData[quarter] = quarterlyTarget
        months.forEach((month) => {
          this.monthData[month] = monthlyTarget
        })
      })

      // 调整12月目标，确保总和等于年度目标（处理精度误差）
      const totalOfFirst11Months = (Number(monthlyTarget) * 11).toFixed(2)
      this.monthData.month12 = (annualTarget - totalOfFirst11Months).toFixed(2)
    },

    handleChange(event, month) {
      // 定义季度和对应月份的映射关系
      const quarterMap = {
        q1: ['month1', 'month2', 'month3'],
        q2: ['month4', 'month5', 'month6'],
        q3: ['month7', 'month8', 'month9'],
        q4: ['month10', 'month11', 'month12']
      }

      // 浮点累加会产生误差（如 21340645.330000002），统一保留两位小数
      const round2 = (num) => Math.round(num * 100) / 100

      // 计算每个季度的目标值
      for (const [quarter, months] of Object.entries(quarterMap)) {
        this.monthData[quarter] = round2(months.reduce((sum, monthKey) => sum + Number(this.monthData[monthKey] || 0), 0))
      }

      // 计算年度目标值
      this.monthData.annual = round2(
        Object.keys(quarterMap).reduce((sum, quarterKey) => sum + Number(this.monthData[quarterKey] || 0), 0)
      )
    },
    reset() {
      this.form = {
        link_id: '',
        year: '2025',
        link_type: ''
      }

      this.userList = []
      this.monthData = {
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
      this.dialogVisible = false
    },
    openBox(val) {
      this.tabCur = val
      this.dialogVisible = true
    },
    getSelectList(val) {
      this.userList = val
    },
    changeMastart(val) {
      this.userList = val
    },
    confirm() {
      if (this.userList.length == 0) {
        this.$message.error(`请选择${this.tabCur == 1 ? '部门' : '业务员'}`)
        return
      }

      this.listData = []

      if (this.userList.length > 0) {
        this.userList.map((item) => {
          let obj = {
            user: item,
            ...this.monthData
          }
          this.listData.push(obj)
        })
      }
      const arr = []
      this.listData.map((item) => {
        for (let i = 1; i <= 12; i++) {
          let obj = {
            link_id: item.user.id,
            link_type: this.tabCur,
            year: this.form.year,
            amount: item['month' + i],
            month: i
          }
          arr.push(obj)
        }
      })
      clientTargetPutApi({ data: arr }).then((res) => {
        this.$emit('getTableData')
        this.reset()
      })
    }
  }
}
</script>

<style scoped lang="scss">
.title {
  font-size: 13px;
  color: #606266;
  margin: 10px 10px 10px 10px;
}

.input {
  width: calc(100% - 20px);
  margin: 0 10px 10px 10px;
}

:deep(.el-divider--horizontal) {
  margin: 10px 0;
}

/* 底部按钮区域样式 */
.dialog-footer {
  text-align: right;
}

.quarter-box {
  border: 1px solid #ccc;
  border-radius: 8px;
}
</style>
