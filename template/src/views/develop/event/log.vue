<template>
  <div class="divBox">
    <el-card :body-style="{ padding: '20px 20px 20px 20px' }" class="mb14 normal-page">
      <oaFromBox
        :isViewSearch="false"
        :search="search"
        :title="`触发器日志`"
        :total="total"
        :isAddBtn="false"
        @confirmData="confirmData"
      >
      </oaFromBox>
      <el-table
        :data="tableData"
        v-loading="loading"
        :height="TABLE_HEIGHT"
        row-key="id"
        class="mt10"
        style="width: 100%"
      >
        <el-table-column label="触发器名称" prop="name" />
        <el-table-column :min-width="100" label="触发类型" prop="field_name">
          <template #default="scope">
            {{ getEvent(scope.row.event) }}
          </template>
        </el-table-column>

        <el-table-column label="触发动作" prop="name">
          <template #default="scope">
            <span v-if="scope.row.action && scope.row.action.length > 0">
              {{ getAction(scope.row.action) }}
            </span>
            <span v-else class="color-file">（无触发动作）</span>
          </template>
        </el-table-column>
        <el-table-column label="关联实体" prop="name">
          <template #default="scope">
            {{ scope.row.crud.table_name || '--' }}
          </template>
        </el-table-column>
        <el-table-column label="执行结果" prop="log" min-width="100">
          <template #default="scope">
            <el-tag :type="scope.row.result === 'success' ? 'success' : 'danger'" effect="plain">
              {{ scope.row.result === 'success' ? '成功' : '失败' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="创建时间" prop="name">
          <template #default="scope">
            <span> {{ scope.row.created_at }}</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" width="100">
          <template #default="scope">
            <el-button type="primary" link @click="editFn(scope.row)">查看</el-button>
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
      <!-- 查看 -->
      <oaDialog :fromData="fromData" ref="oaDialog">
        <div>
          <json-viewer style="height: 300px; width: 100%" :value="rowData.log" :expand-depth="8" copyable></json-viewer>
        </div>
      </oaDialog>
    </el-card>
  </div>
</template>
<script>
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import { dataEventLogApi, dataEventTypeApi, dataEventActionApi, getDatabaseApi } from '@/api/develop.ts'
import JsonViewer from '@/components/common/jsonViewer.ts'
import oaDialog from '@/components/form-common/drawer-form'
import oaFromBox from '@/components/common/oaFromBox'
export default {
  name: 'FinanceList',
  components: {
    oaFromBox,
    oaDialog,
    JsonViewer
  },
  data() {
    return {
      TABLE_HEIGHT,
      typesList: [],
      actionList: [],
      loading: false,
      search: [
        {
          field_name: '触发器名称',
          field_name_en: 'name',
          form_value: 'input'
        },
        {
          field_name: '关联实体',
          field_name_en: 'crud_id',
          form_value: 'cascaderSelect',
          data_type: 1,
          data_dict: [] // 关联实体
        }
      ],
      tableData: [],
      fromData: {
        title: '查看',
        type: 'slot',
        width: '40%'
      },
      rowData: {},
      total: 0,
      where: {
        page: 1,
        limit: 15,
        name: '',
        crud_id: ''
      }
    }
  },
  mounted() {
    this.getDatabase()
    this.getActionList()
    this.getOptions()
    this.getTableData()
  },
  methods: {
    // 表格数据
    getTableData() {
      this.loading = true
      dataEventLogApi(this.where).then((res) => {
        this.loading = false
        this.tableData = res.data.list
        this.total = res.data.count
      })
    },

    // 获取应用数据
    getDatabase() {
      getDatabaseApi().then((res) => {
        this.search[1].data_dict = res.data
      })
    },
    confirmData(data) {
      this.where.page = 1
      this.where.name = data.name || ''
      this.where.crud_id = data.crud_id || ''
      this.getTableData()
    },
    getEvent(val) {
      const targetOption = this.typesList[0]?.options?.find((item) => item.value === val)
      return targetOption ? targetOption.label : '--'
    },
    // 获取触发器类型
    async getOptions() {
      const data = await dataEventTypeApi(this.crud_id)
      this.typesList = [{ options: data.data }]
    },
    // 获取执行动作类型
    getActionList() {
      dataEventActionApi().then((res) => {
        this.actionList = res.data
      })
    },
    // 根据id获取触发动作
    getAction(val) {
      let textArr = []
      this.actionList.map((item) => {
        val.map((key) => {
          if (item.value == key) {
            textArr.push(item.label)
          }
        })
      })
      return textArr.join('/')
    },

    // 分页
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },

    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },

    editFn(row) {
      this.rowData = row
      this.$refs.oaDialog.openBox()
    }
  }
}
</script>
<style scoped lang="scss"></style>
