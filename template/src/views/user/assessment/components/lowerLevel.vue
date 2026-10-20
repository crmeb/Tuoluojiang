<template>
  <div>
    <oaFromBox
      :isAddBtn="false"
      :isViewSearch="false"
      :search="search"
      :total="total"
      @addDataFn="addFinance"
      @confirmData="confirmData"
    ></oaFromBox>

    <div class="table-box mt-10">
      <el-table
        v-loading="loading"
        :data="tableData"
        :height="TABLE_HEIGHT"
        :tree-props="{ children: 'children' }"
        default-expand-all
        row-key="id"
        style="width: 100%"
      >
        <el-table-column label="姓名" min-width="100" prop="test">
          <template #default="{ row }">
            <div style="display: flex; align-items: center; gap: 6px" class="pointer">
              <el-avatar :size="24" :src="row.test.avatar"></el-avatar>
              <span>{{ row.test.name || '--' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="部门" min-width="160" prop="frame.name" />
        <el-table-column label="考核名称" min-width="120" prop="name" />
        <el-table-column label="考核周期" min-width="100" prop="period">
          <template #default="scope">
            <span>{{ getPeriodText(scope.row.period) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="考核得分" min-width="80" prop="score" />
        <el-table-column label="考核等级" min-width="100" prop="level" />
        <el-table-column label="开启状态" min-width="80" prop="is_show">
          <template #default="scope">
            <el-switch
              v-if="scope.row.status !== 5"
              active-text="已启用"
              :disabled="scope.row.is_show === 1"
              inactive-text="未启用"
              :model-value="!(scope.row.is_show === 0)"
              :width="70"
              @change="handleIsShow(scope.row, 1)"
            />
            <el-switch
              v-else
              active-text="已启用"
              inactive-text="未启用"
              :model-value="!(scope.row.is_show === 0)"
              :width="70"
              @change="handleIsShow(scope.row, 2)"
            />
          </template>
        </el-table-column>
        <el-table-column label="考核状态" min-width="110" prop="status">
          <template #default="scope">
            <el-tag :type="getStatusTag(scope.row.status).type">
              {{ getStatusTag(scope.row.status).text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="考核开始时间" min-width="100" prop="start_time">
          <template #default="scope">
            {{ dayjs(scope.row.start_time).format('YYYY-MM-DD') }}
          </template>
        </el-table-column>
        <el-table-column label="考核结束时间" min-width="100" prop="end_time">
          <template #default="scope">
            {{ dayjs(scope.row.end_time).format('YYYY-MM-DD') }}
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" prop="address" width="220">
          <template #default="scope">
            <el-button v-if="scope.row.status !== 0" type="primary" link @click="handleCheck(scope.row, 'check')">
              查看
            </el-button>
            <el-button
              v-if="userInfoUid == scope.row.check.uid && scope.row.status == 2"
              type="primary" link
              @click="handleCheck(scope.row, scope.$index)"
            >
              评分
            </el-button>
            <el-button v-if="userInfoUid == scope.row.check.uid" type="primary" link @click="handleCheck(scope.row, 'edit')">
              修改考核
            </el-button>
            <!-- {{ scope.row.check.uid }} -->
            <el-button v-if="userInfoUid !== scope.row.check.uid" type="primary" link @click="handleScore(scope.row)">
              评分记录
            </el-button>
            <el-dropdown class="ml10">
              <el-button type="primary" link class="el-dropdown-link">
                更多
                <i class="el-icon-arrow-down el-icon--right" />
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="copy(scope.row, scope.$index)"> 复制 </el-dropdown-item>
                  <el-dropdown-item @click="handleDelete(scope.row)"> 删除 </el-dropdown-item>
                  <el-dropdown-item
                    v-if="(scope.row.status == 3 || scope.row.status == 4) && userInfoUid == scope.row.check.uid"
                    @click="handleScore(scope.row)"
                  >
                    评分记录
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
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
    <!-- 评分记录弹窗 -->
    <score-dialog ref="scoreDialog" :config="config" />
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import { modalSure } from '@/libs/public.ts'
import modalForm from '@/libs/modal-form.ts'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import { userAssessDelete, userAssessSetShow, userAssessSubord } from '@/api/user.ts'
import Common from '@/components/user/accessCommon.ts'

import oaFromBox from '@/components/common/oaFromBox'
export default {
  emits: ['lowerHandleCheck'],
  name: 'LowerLevel',
  components: {
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage')),
    scoreDialog: defineAsyncComponent(() => import('./scoreDialog')),
    oaFromBox
  },
  props: ['handle'],
  computed: {
    ...mapStores(useUserStore),
    dayjs: () => dayjs
  },
  watch: {
    // prop 由父组件更新时同步到本地副本（组件内部改写的是 handleLocal）
    handle(val) {
      this.handleLocal = val
    }
  },
  data() {
    return {
      // Vue 3 的 prop 只读，组件内部需要改写时用本地副本
      handleLocal: this.handle,
      TABLE_HEIGHT,
      tableData: [],
      where: {
        page: 1,
        limit: 15,
        period: '',
        time: '',
        date: '',
        status: '',
        test_uid: '',
        scope_frame: 'all',
        type: 1,
        sort_value: '',
        sort_field: ''
      },
      userInfoUid: 0,
      loading: false,
      is_show: true,
      total: 0,
      from: {
        status: '',
        id: '',
        tabIndex: '2',
        addBtn: 1
      },
      period: '',
      scope_frame: 'all',
      status: '',
      test_uid: '',
      config: {},
      time: '',
      date: '',
      pageHeight: 0,
      prevHeight: 0,
      // periodOptions: Common.periodOption,
      // statusOptions: Common.statusOptions,
      search: [
        {
          field_name: '被考核人',
          field_name_en: 'user_id',
          form_value: 'user_id',
          data_dict: []
        },
        {
          field_name: '考核周期',
          field_name_en: 'period',
          form_value: 'select',
          data_dict: Common.periodOption
        },
        {
          field_name: '考核状态',
          field_name_en: 'status',
          form_value: 'select',
          data_dict: Common.statusOptions
        },
        {
          field_name: '开始时间',
          field_name_end: '结束时间',
          field_name_en: 'time',
          form_value: 'date_picker'
        }
      ]
    }
  },
  mounted() {
    this.userInfoUid = this.userStore.userInfo.uid
  },
  methods: {
    addFinance() {
      this.from.status = 0
      this.from.id = 0
      this.from.addBtn = 2
      this.$emit('lowerHandleCheck', this.from, {}, '')
    },

    getPeriodText(id) {
      return Common.getPeriodText(id)
    },
    getStatusText(id) {
      return Common.getStatusText(id)
    },
    getStatusTag(status) {
      return Common.getStatusTag(status)
    },
    async getTableData() {
      this.loading = true
      const data = {
        ...this.where,
        handle: this.handleLocal,
        date: this.date,
        type: 1
      }
      const result = await userAssessSubord(data)
      this.tableData = result.data.list
      this.loading = false
      this.total = result.data.count
    },
    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },

    confirmData(data) {
      this.where = { ...this.where, ...data }
      this.where.page = 1
      this.where.period = data.period
      this.where.status = data.status
      this.where.time = data.time
      this.where.test_uid = data.user_id
      this.getTableData()
    },
    handleDelete(row) {
      modalForm(userAssessDelete(row.id)).then(() => {
        if (this.where.page > 1 && this.tableData.length <= 1) {
          this.where.page--
        }
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
    handleIsShow(row, type) {
      const status = row.is_show === 1 ? 0 : 1
      let str = ''
      if (type === 2) {
        if (status === 0) {
          str = '确定关闭考核吗'
        } else {
          str = '确定开启考核吗'
        }
      } else {
        str = '启用后，被考核人将会看到考核，确定开启吗'
      }
      modalSure(str).then(() => {
        userAssessSetShow(row.id, { status }).then((res) => {
          this.getTableData()
        })
      })
    },
    handleCheck(row, str) {
      this.from.status = row.status
      this.from.id = row.id
      this.from.is_show = row.is_show
      if (row.status === 5) {
        this.from.addBtn = 2
      }
      this.from.addBtn = 1
      this.$emit('lowerHandleCheck', this.from, row, str)
    },
    copy(row) {
      this.from.status = 0
      this.from.id = 0
      this.from.addBtn = 2
      this.$emit('lowerHandleCheck', this.from, row, 'copy')
    },
    getFormBoxFrame() {
      // this.$refs.formBox.getFrameUser()
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-switch__label--right) {
  margin-left: 6px;
}
.el-icon--right {
  margin-left: 0;
}
:deep(.el-card__body) {
  padding: 0 20px;
}
.cell .el-tag {
  background-color: #fff;
}
:deep(.el-table .cell) {
  overflow: none;
  text-overflow: clip;
}
</style>
