<template>
  <div class="divBox">
    <el-card class="normal-page">
      <formBox ref="formBox" :total="total" @confirmData="confirmData" @getExportData="getExportData" />
      <div v-if="tableData.length > 0" class="mt10">
        <el-table
          v-loading="tableLoading"
          :data="tableData"
          :height="TABLE_HEIGHT"
          default-expand-all
          row-key="id"
          style="width: 100%"
        >
          <el-table-column label="申请人" min-width="120" prop="card.name" show-overflow-tooltip>
            <template #default="{ row }">
              <div class="flex" v-if="row.card">
                <img :src="row.card.avatar" alt="" class="img" />
                <span>{{ row.card.name }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="部门" min-width="150" prop="frame.name" show-overflow-tooltip />
          <el-table-column label="审批类型" min-width="150" prop="approve.name" show-overflow-tooltip />
          <el-table-column label="审批状态" min-width="120" prop="name" show-overflow-tooltip>
            <template #default="scope">
              <el-tag size="small" effect="plain" v-if="scope.row.status === -1" type="info"> 已撤销 </el-tag>
              <el-tag size="small" effect="plain" v-if="scope.row.status === 0" type="warning"> 待审核 </el-tag>
              <el-tag size="small" effect="plain" v-if="scope.row.status === 1" type="info"> 已通过 </el-tag>
              <el-tag size="small" effect="plain" v-if="scope.row.status === 2" type="danger"> 已拒绝 </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="提交时间" min-width="150" prop="created_at" show-overflow-tooltip />
          <el-table-column label="操作" prop="name" show-overflow-tooltip width="180">
            <template #default="scope">
              <el-button v-hasPermi="['business:record:index:details']" type="primary" link @click="handleDetail(scope.row)"
                >详情</el-button
              >

              <el-button v-hasPermi="['business:record:index:delete']" type="primary" link @click="handleDelete(scope.row)"
                >删除</el-button
              >

              <el-button type="primary" link @click="handlePrint(scope.row)">
                打印
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        <div class="page-fixed">
          <el-pagination
            :current-page="where.page"
            :page-size="where.limit"
            :page-sizes="[15, 20, 30]"
            :total="total"
            layout="total,sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="pageChange"
          />
        </div>
      </div>
      <div v-else>
        <default-page v-height :index="14" :min-height="320" />
      </div>
    </el-card>

    <export-excel ref="exportExcel" :export-data="exportData" :save-name="saveName" :template="false" />
    <detail-examine ref="detailExamine" :type="3" />
    <print-preview ref="printPreview"  :print-data="printData" />
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import { modalSure } from '@/libs/public.ts'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import { approveApplyApi, approveApplyDeleteApi, approveApplyExportApi, approveApplyEditApi } from '@/api/business.ts'
export default {
  name: 'IndexVue',
  components: {
    formBox: defineAsyncComponent(() => import('./components/formBox')),
    detailExamine: defineAsyncComponent(() => import('@/views/user/examine/components/detailExamine')),
    exportExcel: defineAsyncComponent(() => import('@/components/common/exportExcel')),
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage')),
    printPreview: defineAsyncComponent(() => import('./components/printPreview'))
  },
  data() {
    return {
      TABLE_HEIGHT,
      where: {
        page: 1,
        limit: 15,
        types: 2
      },
      total: 0,
      tableData: [],
      exportData: {
        data: [],
        cols: []
      },
      saveName: '',
      exportLoading: false,
      tableLoading: false,
      printData: {}
    }
  },
  mounted() {
    this.getTableData()
  },
  methods: {
    async pageChange(page) {
      this.where.page = page
      this.tableLoading = true
      await this.getTableData()
      this.tableLoading = false
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    // 获取列表
    async getTableData() {
      const data = this.where
      const result = await approveApplyApi(data)
      this.tableData = result.data.list
      this.total = result.data.count
    },
    confirmData(data) {
      this.where.page = 1
      this.where = { ...data, page: 1, limit: 15 }

      this.getTableData()
    },
    // 详情
    handleDetail(row) {
      this.$refs.detailExamine.openBox(row)
    },
    handleDelete(row) {
      modalSure('你确定要删除这条申请记录吗').then(() => {
        approveApplyDeleteApi(row.id).then((res) => {
          if (this.where.page > 1 && this.tableData.length <= 1) {
            this.where.page--
          }
          this.getTableData()
        })
      })
    },
    // 打印
    handlePrint(row) {
      approveApplyEditApi(row.id, { types: 1 }).then((res) => {

        this.printData = res.data

        this.$refs.printPreview.openBox(res.data)

      })
    },
    getExportData() {
      if (this.where.approve_id == '') return this.$message.error('请选择审批类型')
      this.saveName = '审批导出_' + dayjs(new Date()).format('HH_mm_ss') + '.xlsx'
      const where = JSON.parse(JSON.stringify(this.where))
      where.limit = 0
      this.exportLoading = true
      where.page = 1
      approveApplyExportApi(where)
        .then((res) => {
          let aoaData = []
          aoaData.push(res.data.title)
          let data = res.data.exports
          if (data.length > 0) {
            aoaData.push(...data)
          }
          this.exportData.data = aoaData
          this.$refs.exportExcel.exportExcel()
          this.exportLoading = false
        })
        .catch((error) => {
          this.exportLoading = false
        })
    }
  }
}
</script>

<style lang="scss" scoped>
// .normal-page {
//   padding-bottom: 0;
// }
:deep(.el-table th) {
  background-color: #f7fbff;
}
.search {
  width: 100%;
  padding: 0 10px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;

  span {
    font-size: 13px;
    font-family: PingFang SC-中黑体, PingFang SC;
    font-weight: normal;
    color: #909399;
  }
  .num {
    color: #303133;
    font-size: 13px;
    font-family: PingFang SC-中黑体, PingFang SC;
    font-weight: 600;
  }
}

.img {
  display: block;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  margin-right: 4px;
}
</style>
