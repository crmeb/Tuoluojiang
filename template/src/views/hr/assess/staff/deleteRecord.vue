<template>
  <div class="divBox">
    <div class="form-wrapper">
      <el-card class="employees-card-bottom">
        <div class="title-16 mb20">删除记录</div>
        <div class="total-16 mb10">共 {{ total }} 条</div>
        <div v-loading="loading">
          <el-table :data="tableData" :height="tableHeight1" style="width: 100%" row-key="id" default-expand-all>
            <el-table-column prop="info.name" label="考核名称" min-width="140" />
            <el-table-column prop="mark" label="删除原因" min-width="150" />
            <el-table-column prop="card" label="删除人" min-width="140">
              <template #default="{ row }">
                <div style="display: flex; align-items: center; gap: 6px" class="pointer">
                  <el-avatar :size="24" :src="row.card.avatar"></el-avatar>
                  <span>{{ row.card.name || '--' }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="check" label="考核人" min-width="140">
              <template #default="{ row }">
                <div style="display: flex; align-items: center; gap: 6px" class="pointer">
                  <el-avatar :size="24" :src="row.check.avatar"></el-avatar>
                  <span>{{ row.check.name || '--' }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="test" label="被考核人" min-width="140">
              <template #default="{ row }">
                <div style="display: flex; align-items: center; gap: 6px" class="pointer">
                  <el-avatar :size="24" :src="row.test.avatar"></el-avatar>
                  <span>{{ row.test.name || '--' }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="address" label="操作" fixed="right" width="100">
              <template #default="scope">
                <el-button
                  type="primary" link
                  @click="handleScore(scope.row)"
                  v-hasPermi="['hr:assessStaff:deleteRecord:details']"
                  >考核详情</el-button
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
      </el-card>
      <details-dialog ref="detailsDialog" :config="config" />
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { userAssessDeletesList } from '@/api/user.ts'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
export default {
  name: 'DeleteInfo',
  components: {
    detailsDialog: defineAsyncComponent(() => import('./components/detailsDialog'))
  },

  data() {
    return {
      where: {
        page: 1,
        limit: 15
      },
      loading: false,
      tableHeight1: window.innerHeight - 244 + 'px',
      total: 0,
      tableData: [],
      config: {}
    }
  },
  created() {
    this.getTableData()
  },
  methods: {
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
      const data = {
        page: this.where.page,
        limit: this.where.limit
      }
      const result = await userAssessDeletesList(data)
      this.tableData = result.data.list
      this.loading = false
      this.total = result.data.count
    },
    handleScore(row) {
      this.config = {
        title: '考核详情',
        width: DIALOG_SIZE.XL,
        data: row.info
      }
      this.$refs.detailsDialog.handleOpen()
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-drawer__body) {
  height: 100%;
  overflow-y: auto;
}
</style>
