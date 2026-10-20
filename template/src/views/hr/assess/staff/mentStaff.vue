<template>
  <div class="divBox" :class="{ 'none-bg': !checkBtn }">
    <div class="form-wrapper">
      <el-card class="employees-card-bottom" v-show="checkBtn">
        <div>
          <formBox
            ref="formBox"
            @confirmData="confirmData"
            @openAssessed="openAssessed"
            @getExportData="getExportData"
            :total="total"
          />
          <div v-loading="loading">
            <el-table
              :data="tableData"
              style="width: 100%"
              row-key="id"
              :height="TABLE_HEIGHT"
              default-expand-all
              :tree-props="{ children: 'children' }"
            >
              <el-table-column prop="test" label="被考核人" min-width="100">
                <template #default="{ row }">
                  <div style="display: flex; align-items: center; gap: 6px" class="pointer">
                    <el-avatar :size="24" :src="row.test.avatar"></el-avatar>
                    <span>{{ row.test.name || '--' }}</span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column prop="frame.name" label="部门" min-width="120" />
              <el-table-column prop="name" label="考核名称" min-width="100" />
              <el-table-column prop="period" label="考核周期" min-width="100">
                <template #default="scope">
                  <span>{{ getPeriodText(scope.row.period) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="score" label="考核得分" min-width="80" />
              <el-table-column prop="level" label="考核等级" min-width="80" />
              <el-table-column prop="check" label="考核人" min-width="100">
                <template #default="{ row }">
                  <div style="display: flex; align-items: center; gap: 6px" class="pointer">
                    <el-avatar :size="24" :src="row.check.avatar"></el-avatar>
                    <span>{{ row.check.name || '--' }}</span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column prop="status" label="考核状态" min-width="80">
                <template #default="scope">
                  <span>{{ getStatusText(scope.row.status) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="id" label="考核时间" min-width="150">
                <template #default="scope">
                  <p class="p-time">
                    <span>开始：</span>
                    {{ dayjs(scope.row.start_time).format('YYYY-MM-DD') }}
                  </p>
                  <p class="p-time">
                    <span>结束：</span>
                    {{ dayjs(scope.row.end_time).format('YYYY-MM-DD') }}
                  </p>
                </template>
              </el-table-column>
              <el-table-column prop="address" label="操作" fixed="right" width="180">
                <template #default="scope">
                  <el-button
                    type="primary" link
                    @click="changeStaff(scope.row)"
                    v-hasPermi="['hr:assessStaff:assessmentStaff:edit']"
                    >查看</el-button
                  >
                  <el-button v-if="scope.row.status == 1" type="primary" link @click="skipSelfAssessment(scope.row)"
                    >跳过自评</el-button
                  >

                  <el-button
                    type="primary" link
                    @click="handleDelete(scope.row)"
                    v-hasPermi="['hr:assessStaff:assessmentStaff:delete']"
                    >删除</el-button
                  >

                  <el-button
                    v-if="scope.row.status == 3 || scope.row.status == 4"
                    type="primary" link
                    @click="handleScore(scope.row)"
                    v-hasPermi="['hr:assessStaff:assessmentStaff:record']"
                    >评分记录</el-button
                  >
                </template>
              </el-table-column>
            </el-table>

            <div class="page-fixed">
              <el-pagination
                :page-size="where.limit"
                :current-page="where.page"
                :page-sizes="[15, 20, 30]"
                layout="total,sizes, prev, pager, next, jumper"
                :total="total"
                @size-change="handleSizeChange"
                @current-change="pageChange"
              />
            </div>
          </div>
        </div>
      </el-card>
      <export-excel :template="false" :save-name="saveName" :export-data="exportData" ref="exportExcel" />
      <score-dialog ref="scoreDialog" :config="config" />
      <!-- 未考核人员 -->
      <notAssessed ref="sssessed"></notAssessed>
    </div>
    <!-- 考核详情   -->
    <auditProcess
      v-if="!checkBtn"
      :id="id"
      ref="auditProcess"
      :strType="`check`"
      :process-index="status"
      @auditProcess="auditProcess"
    />
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import Commnt from '@/components/user/accessCommon.ts'
import { modalSure } from '@/libs/public.ts'
import modalForm from '@/libs/modal-form.ts'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import {
  userAssessDelete,
  userAssessList,
  userAssessRemindApi,
  userAssessAbnormal,
  skipSelfAssessment
} from '@/api/user.ts'

export default {
  name: 'AssessRecord',
  components: {
    formBox: defineAsyncComponent(() => import('./components/formBox')),
    scoreDialog: defineAsyncComponent(() => import('@/views/user/assessment/components/scoreDialog')),
    auditProcess: defineAsyncComponent(() => import('@/views/user/assessment/components/auditProcess')),
    exportExcel: defineAsyncComponent(() => import('@/components/common/exportExcel')),
    notAssessed: defineAsyncComponent(() => import('./components/notAssessed'))
  },

  computed: {
    dayjs: () => dayjs
  },
  data() {
    return {
      TABLE_HEIGHT,
      where: {
        page: 1,
        limit: 15
      },
      checkBtn: true,
      status: '',
      total: 0,
      loading: false,
      tableData: [],
      search: {
        time: '',
        period: '',
        status: '',
        frame: [],
        test_uid: [],
        check_uid: [],
        date: ''
      },
      showTips: 0,
      config: {},
      saveName: '导出.xlsx',
      exportData: {
        data: [],
        cols: [
          { wpx: 70 },
          { wpx: 70 },
          { wpx: 100 },
          { wpx: 100 },
          { wpx: 100 },
          { wpx: 100 },
          { wpx: 100 },
          { wpx: 100 },
          { wpx: 160 },
          { wpx: 160 }
        ],
        rows: [{ hpt: 30 }]
      },
      rows: []
    }
  },
  created() {
    this.getTableData()
  },
  methods: {
    // formBox 是异步组件，表格首次渲染时 $refs.formBox 可能尚未就绪，直接取公共映射
    getStatusText(status) {
      return Commnt.getStatusText(status)
    },
    getPeriodText(id) {
      if (id == 1) {
        return '周考核'
      } else if (id == 2) {
        return '月考核'
      } else if (id == 3) {
        return '年考核'
      } else if (id == 5) {
        return '季度考核'
      } else if (id == 4) {
        return '半年考核'
      } else {
        return '月考核'
      }
    },
    getTips() {
      const data = {
        period: this.search.period,
        time: this.search.date
      }
      userAssessAbnormal(data).then((res) => {
        this.showTips = res.data.count
      })
    },
    openAssessed() {
      this.$refs.sssessed.openBox()
    },
    changeStaff(data) {
      this.id = data.id
      this.status = data.status
      this.checkBtn = false
    },
    async skipSelfAssessment(data) {
      await modalSure('确定跳过自评吗')
      await skipSelfAssessment(data.id)

      this.getTableData()
    },
    auditProcess() {
      this.checkBtn = true
    },
    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    async getTableData() {
      this.loading = true
      const result = await userAssessList(this.where)
      this.tableData = result.data.list
      this.loading = false
      this.total = result.data.count
      this.getTips()
    },
    // 批量导出
    getExportData() {
      let obj = {}
      obj = { ...this.where }
      obj.page = 1
      obj.limit = 0
      this.saveName =
        '绩效导出' + '_' + dayjs(new Date()).format('MM-DD-HH-mm-ss') + '.xlsx'
      userAssessList(obj).then((res) => {
        const data = res.data.list
        if (data.length <= 0) {
          this.$message.error('暂无内容可导出')
        } else {
          const aoaData = [
            [
              '姓名',
              '部门',
              '考核名称',
              '考核周期',
              '考核得分',
              '考核等级',
              '考核人',
              '考核状态',
              '考核开始时间',
              '考核结束时间'
            ]
          ]
          data.forEach((value) => {
            aoaData.push([
              value.test ? value.test.name : '--',
              value.frame ? value.frame.name : '--',
              value.name,
              this.$refs.formBox.getPeriodText(value.period),
              value.score,
              value.level,
              value.check ? value.check.name : '--',
              this.$refs.formBox.getStatusText(value.status),
              value.start_time,
              value.end_time
            ])
            this.rows.push({
              hpt: 30
            })
          })
          this.exportData.data = aoaData
          this.exportData.rows = this.rows
          this.$refs.exportExcel.exportExcel()
        }
      })
    },

    // 删除
    handleDelete(row) {
      modalForm(userAssessDelete(row.id)).then(() => {
        this.getTableData()
      })
    },
    handleScore(row) {
      this.config = {
        title: '评分记录',
        width: '720px',
        id: row.id
      }
      this.$refs.scoreDialog.handleOpen()
    },
    async getAssessRemind(item) {
      await userAssessRemindApi(item.id)
    },
    confirmData(data) {
      this.where.page = 1
      if (data == 'reset') {
        this.where = {
          page: 1,
          limit: 15
        }
      } else {
        this.where = { ...this.where, ...data }
      }

      this.getTableData()
    }
  }
}
</script>
<style>
.none-bg {
  background: none !important;
  border: none !important;
}
</style>
<style lang="scss" scoped>
.alert {
  margin-top: 10px;
  width: 100%;
  padding-top: 0;
}
:deep(.cr-alert .el-icon-info) {
  font-size: 16px;
}
:deep(.el-alert) {
  padding-left: 30px;
  border: 1px solid #1890ff;
  color: #1890ff;
  font-size: 13px;
  background-color: #edf7ff;
  line-height: 1;
  margin-bottom: 14px;
}
:deep(.el-alert--info .el-alert__description) {
  color: #303133;
  font-size: 13px;
  font-weight: 500;
}

.info {
  color: #1890ff;
  cursor: pointer;
}
.p-time {
  margin: 0 0 10px 0;
  padding: 0;
}
.p-time:last-of-type {
  margin: 0;
}

:deep(.el-table th) {
  background-color: #f7fbff;
}
:deep(.el-drawer__body) {
  height: 100%;
  overflow-y: auto;
}
</style>
