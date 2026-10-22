<!-- 项目-我的项目-项目列表页面 -->
<template>
  <div class="divBox bill-type">
    <el-card class="normal-page">
      <oaFromBox
        :isViewSearch="false"
        :search="search"
        :title="$route.meta.title"
        :total="total"
        :treeData="treeData"
        :treeDefault="treeDefault"
        btnText="新建项目"
        @addDataFn="addProgram"
        @confirmData="confirmData"
        @treeChange="treeChange"
      ></oaFromBox>

      <div class="mt10">
        <oa-table
          :height="TABLE_HEIGHT"
          :loading="loading"
          :tableData="tableData"
          :tableOptions="tableOptions"
          :total="total"
          @handleSizeChange="handleSizeChange"
          @handleCurrentChange="pageChange"
        >
          <template #name="{ row }">
            <div class="flex">
              <i class="iconfont iconxiangmuguanli"></i>
              <div v-if="row.name.length < 18" class="point line1" @click="goTask(row)">
                {{ row.name || '- -' }}
              </div>
              <el-popover v-else placement="top" trigger="hover" width="250">
                <div>{{ row.name || '- -' }}</div>
                <template #reference>
                  <div class="line1 point" style="max-width: 200px" @click="goTask(row)">
                    {{ row.name || '- -' }}
                  </div>
                </template>
              </el-popover>
            </div>
          </template>

          <template #status="{ row }">
            <el-tag :type="row.display_status.type" effect="plain">{{ row.display_status.label }}</el-tag>
          </template>

          <template #admins="{ row }">
            <img v-if="row.admins?.[0]" :src="getAvatarSrc(row.admins[0])" alt="" class="img" />
            <span>{{ row.admins?.[0]?.name || '--' }}</span>
          </template>

          <template #progress="{ row }">
            <!-- :text-inside="true"
              :stroke-width="16" -->
            <el-progress :percentage="getProgress(row.task_statistics)"></el-progress>
          </template>

          <template #taskCount="{ row }">
            <span>{{ row.task_statistics.incomplete }}/{{ row.task_statistics.total }}</span>
          </template>
        </oa-table>
      </div>
    </el-card>

    <!-- 新建项目 -->
    <el-drawer
      :before-close="handleClose"
      :visible.sync="taskDrawer"
      :wrapper-closable="false"
      size="1120px"
      title="新建项目"
    >
      <add-program
        v-if="taskDrawer"
        ref="addProgram"
        :customer="customerList"
        :type="`edit`"
        @getTableData="getTableData"
        @handleClose="handleClose"
      />
    </el-drawer>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import { TABLE_HEIGHT } from '@/constants/tableSize'
import { getAvatarSrc } from '@/utils/avatar'
import { getProgramListApi, deleteProgramApi } from '@/api/program'
import { customerSelectApi } from '@/api/enterprise'
import { roterPre } from '@/settings'

export default {
  name: 'programList',
  components: {
    oaFromBox: () => import('@/components/common/oaFromBox'),
    oaTable: () => import('@/components/form-common/oa-table'),
    addProgram: () => import('./components/addProgram'),
    taskDrawer: () => import('../programTask/index'),
    dynamicsDrawer: () => import('./dynamics')
  },
  data() {
    return {
      TABLE_HEIGHT,
      loading: false,
      tableData: [],
      tableFrom: {
        page: 1,
        limit: 15,
        types: 0,
        status: '',
        admins: [],
        scope_frame: 'all',
        scope_normal: 0,
        eid: [],
        cid: []
      },
      total: 0,
      customerList: [],
      taskDrawer: false,
      taskTitle: '',
      programId: 0,
      treeData: [
        {
          options: [
            {
              value: 0,
              label: '全部项目'
            },
            {
              value: 1,
              label: '我负责的'
            },
            {
              value: 2,
              label: '我参与的'
            },
            {
              value: 3,
              label: '我创建的'
            }
          ]
        }
      ],
      search: [
        {
          field_name: '项目名称',
          field_name_en: 'name',
          form_value: 'input'
        },
        {
          field_name: '状态',
          field_name_en: 'status',
          form_value: 'select',
          multiple: true,
          props: {
            collapseTags: true
          },
          data_dict: [
            {
              value: 5,
              name: '已延期'
            },
            {
              value: 4,
              name: '进行中'
            },
            {
              value: 3,
              name: '待开始'
            },
            {
              value: 1,
              name: '已暂停'
            },
            {
              value: 2,
              name: '已关闭'
            }
          ]
        },
        {
          field_name: '关联客户',
          field_name_en: 'eid',
          form_value: 'input'
        },
        {
          field_name: '关联订单',
          field_name_en: 'cid',
          form_value: 'input'
        },
        {
          form_value: 'manage'
        }
      ],
      tableOptions: [
        {
          label: '项目编号',
          prop: 'ident',
          width: '120px'
        },
        {
          label: '项目名称',
          type: 'slot',
          name: 'name'
          // width: '450px'
        },
        {
          label: '状态',
          type: 'slot',
          name: 'status'
          // width: '120px'
        },
        {
          label: '负责人',
          type: 'slot',
          name: 'admins'
          // width: '150px'
        },
        {
          label: '计划开始',
          prop: 'start_date'
          // width: '140px'
        },
        {
          label: '计划结束',
          prop: 'end_date'
          // width: '140px'
        },
        {
          label: '项目进度',
          type: 'slot',
          name: 'progress'
        },
        {
          label: '未完成/总任务数',
          type: 'slot',
          name: 'taskCount'
        }
      ],
      treeDefault: 0
    }
  },
  created() {
    this.getCustomer()
  },
  mounted() {
    this.getTableData()
  },
  methods: {
    getAvatarSrc,
    handleClose() {
      this.taskDrawer = false
    },
    getDisplayStatus(row = {}) {
      const status = Number(row.status)
      const total = Number(row.task_statistics?.total || 0)
      const incomplete = Number(row.task_statistics?.incomplete || 0)

      if (status === 1) return { label: '已暂停', type: 'warning' }
      if (status === 2) return { label: '已关闭', type: 'info' }
      if (total > 0 && incomplete === 0) return { label: '已完成', type: 'success' }
      if (row.end_date && this.nowTime() > row.end_date) return { label: '已延期', type: 'danger' }
      if (row.start_date && this.nowTime() < row.start_date) return { label: '待开始', type: 'success' }
      return { label: '进行中', type: '' }
    },
    // 项目进度 = 已完成任务数 / 总任务数，接口只给未完成数，需先换算
    getProgress({ incomplete = 0, total = 0 } = {}) {
      if (!total) return 0
      return Math.round(((total - incomplete) / total) * 100)
    },
    nowTime() {
      const now = new Date()
      const year = now.getFullYear()
      const month = (now.getMonth() + 1).toString().padStart(2, '0') // 月份从0开始计数，所以加1，然后用padStart保证两位数
      const day = now.getDate().toString().padStart(2, '0') // 日期使用padStart保证两位数
      let nowData = `${year}-${month}-${day}`
      return nowData
    },

    // 获取表格数据
    async getTableData(tableFrom, type) {
      this.loading = true
      let data = tableFrom ? tableFrom : this.tableFrom
      const res = await getProgramListApi(data)
      this.tableData = res.data.list.map((item) => ({
        ...item,
        display_status: this.getDisplayStatus(item)
      }))
      this.total = res.data.count
      this.loading = false
      if (type === 1) {
        this.programId = res.data.list[0].id
        this.taskTitle = res.data.list[0].name
        this.taskDrawer = false
      }
    },
    addProgram() {
      this.taskDrawer = true
    },
    addTask() {
      this.$refs.taskDrawerRef.addProgram()
    },
    batchOperation() {
      this.$refs.taskDrawerRef.batchOperation()
    },
    // 获取客户数据
    async getCustomer() {
      const result = await customerSelectApi()
      this.customerList = result.data
    },
    pageChange(page) {
      this.tableFrom.page = page
      this.getTableData()
    },
    handleSizeChange(val) {
      this.tableFrom.limit = val
      this.getTableData()
    },
    // 编辑项目
    handleEdit(row, type) {
      this.$refs.addProgram.openBox(row.id, type)
    },
    // 项目动态
    goDynamics(row) {
      this.programId = row.id
      this.$refs.dynamicsDrawer.drawer = true
      // this.$router.push(`${roterPre}/program/programList/dynamics?id=${row.id}`)
    },
    // 项目任务
    goTask(row) {
      this.$router.push(`${roterPre}/program/programList/taskDetails?id=${row.id}`)
    },
    // 删除项目
    handleDelete(row) {
      modalSure('删除项目，同时会删除项目中的工作项！你确定要删除该项目吗').then(() => {
        deleteProgramApi(row.id).then((res) => {
          this.getTableData()
          this.$refs.addProgram.handleClose()
        })
      })
    },
    async setPayStatus(row, status) {
      const data = {
        id: row.id,
        name: row.name,
        status
      }
      await enterprisePayTypeStatusApi(data)
      this.getTableData()
    },
    confirmData(data) {
      if (data == 'reset') {
        this.tableFrom = {
          page: 1,
          limit: 15,
          types: [],
          name: '',
          status: '',
          eid: [],
          cid: []
        }
        this.treeDefault = 0
      } else {
        this.tableFrom = { ...this.tableFrom, ...data }
      }

      this.getTableData(this.tableFrom)
    },
    treeChange(data) {
      this.tableFrom.types = data.value
      this.getTableData(this.tableFrom)
    }
  }
}
</script>

<style lang="scss" scoped>
.bill-type {
  .header {
    display: flex;
    justify-content: space-between;
    span {
      font-size: 18px;
      line-height: 32px;
      color: #303133;
    }
  }
  .text-right {
    text-align: right;
  }
  .el-radio {
    margin-right: 15px;
  }
  .title {
    font-size: 15px;
    font-weight: 600;
    margin-left: 10px;
    position: relative;
    &:after {
      content: '';
      height: 100%;
      width: 3px;
      background-color: #1890ff;
      position: absolute;
      left: -10px;
      top: 0;
    }
  }
}
.img {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: inline-block;
  vertical-align: top;
  margin-right: 4px;
}
::v-deep .el-table .cell {
  line-height: 26px;
}

.point {
  cursor: pointer;
  &:hover {
    color: #1890ff;
  }
}
.iconxiangmuguanli {
  color: #ff9900;
  margin-right: 4px;
}
.more {
  margin-left: 10px;
}
.btn-box {
  display: flex;
  justify-content: space-between;
  span {
    font-size: 18px;
    line-height: 32px;
    color: #303133;
  }
  .fz30 {
    font-size: 30px;
    margin-left: 14px;
    margin-right: 8px;
    color: #909399;
    font-weight: 400;
  }
}
::v-deep .el-drawer__header {
  padding: 10px 24px 10px 20px;
  font-size: 14px;
}
.flex-between {
  height: 32px;
}

:deep() .el-progress__text {
  font-size: 13px !important;
}
</style>
