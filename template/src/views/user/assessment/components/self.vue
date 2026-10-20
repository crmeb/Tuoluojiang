<template>
  <div>
    <div>
      <oaFromBox :isAddBtn="false" :search="search" :total="total" :isViewSearch="false" @confirmData="confirmData">
      </oaFromBox>
      <div class="mt-10" v-if="tableData.length > 0">
        <div>
          <el-table
            :data="tableData"
            style="width: 100%"
            row-key="id"
            :loading="loading"
            default-expand-all
            :height="TABLE_HEIGHT"
            :tree-props="{ children: 'children' }"
          >
            <el-table-column prop="name" label="考核名称" min-width="130" />
            <el-table-column prop="period" label="考核周期" min-width="100">
              <template #default="scope">
                <span>{{ getPeriodText(scope.row.period) }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="score" label="考核得分" min-width="100" />
            <el-table-column prop="level" label="考核等级" min-width="100" />
            <el-table-column prop="check.name" label="考核人" min-width="100" />
            <el-table-column prop="status" label="考核状态" min-width="120">
              <template #default="scope">
                <el-tag :type="getStatusTag(scope.row.status).type">{{ getStatusTag(scope.row.status).text }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="start_time" label="考核开始时间" min-width="100">
              <template #default="scope">
                {{ dayjs(scope.row.start_time).format('YYYY-MM-DD') }}
              </template>
            </el-table-column>
            <el-table-column prop="end_time" label="考核结束时间" min-width="100">
              <template #default="scope">
                {{ dayjs(scope.row.end_time).format('YYYY-MM-DD') }}
              </template>
            </el-table-column>
            <el-table-column prop="address" label="操作" fixed="right" width="240">
              <template #default="scope">
                <el-button v-if="scope.row.status !== 1" type="primary" link @click="handleCheck(scope.row, 'check')">
                  查看
                </el-button>

                <el-button
                  v-if="scope.row.status > 0 && scope.row.status < 5"
                  type="primary" link
                  @click="handleCheck(scope.row, 'selfEdit')"
                >
                  {{ scope.row.status === 1 ? '自评' : '修改自评' }}
                </el-button>

                <el-button
                  type="primary" link
                  v-if="scope.row.status !== 0 && scope.row.status !== 1"
                  @click="handleScore(scope.row)"
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
      <default-page v-else :index="25" :imgWidth="`150px`" :min-height="420" />
    </div>
    <score-dialog ref="scoreDialog" :config="config" />
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import { userAssessSubord } from '@/api/user.ts'
import Common from '@/components/user/accessCommon.ts'

import oaFromBox from '@/components/common/oaFromBox'
export default {
  emits: ['selfHandleCheck'],
  name: 'Self',
  components: {
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage')),
    scoreDialog: defineAsyncComponent(() => import('./scoreDialog')),
    oaFromBox
  },
  computed: {
    dayjs: () => dayjs
  },
  data() {
    return {
      TABLE_HEIGHT,
      tableData: [],
      where: {
        page: 1,
        limit: 15,
        period: ''
      },
      loading: false,
      periodOptions: Common.periodOption,
      period: '',
      total: 0,
      from: {
        status: '',
        id: '',
        tabIndex: '1'
      },
      type: 0,
      config: {},
      search: [
        {
          field_name: '考核周期',
          field_name_en: 'period',
          form_value: 'select',
          data_dict: Common.periodOption
        }
      ]
    }
  },
  mounted() {},
  methods: {
    confirmData(data) {
      if (data === 'reset') {
        this.where = {
          page: 1,
          limit: 15,
          period: ''
        }
      } else {
        this.where = { ...this.where, ...data }
      }
      this.getTableData()
    },
    async getTableData() {
      this.loading = true
      var data = {
        page: this.where.page,
        limit: this.where.limit,
        type: 0,
        period: this.where.period
      }
      const result = await userAssessSubord(data)
      this.loading = false
      this.tableData = result.data.list
      this.total = result.data.count
    },
    selectPeriod() {
      this.where.page = 1
      this.getTableData()
    },
    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    handleCheck(row, str) {
      this.from.status = row.status
      this.from.id = row.id
      this.$emit('selfHandleCheck', this.from, str, row)
    },
    getPeriodText(id) {
      const txt = Common.getPeriodText(id)
      return txt
    },
    getStatusText(id) {
      const txt = Common.getStatusText(id)
      return txt
    },
    getStatusTag(status) {
      return Common.getStatusTag(status)
    },
    handleScore(row) {
      this.config = {
        title: '评分记录',
        width: '720px',
        id: row.id
      }
      this.$refs.scoreDialog.handleOpen()
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-form-item) {
  margin-bottom: 14px;
}
:deep(.el-table th) {
  background-color: #f7fbff;
}
:deep(.from-s .flex-row .el-form-item) {
  margin-right: 10px;
  margin-left: 0;
}
</style>
