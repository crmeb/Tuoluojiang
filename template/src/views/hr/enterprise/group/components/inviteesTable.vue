<template>
  <div>
    <!-- 被邀请人列表弹窗  -->
    <el-dialog
      title="被邀请人员列表"
      v-model="dialogFormVisible"
      width="920px"
      :modal="true"
      :close-on-click-modal="true"
      class="person"
    >
      <el-table :data="dataTable" style="width: 100%" :max-height="500">
        <el-table-column label="头像" min-width="100" align="center">
          <template #default="scope">
            <img class="avatar" :src="scope.row.user.avatar" alt="" />
          </template>
        </el-table-column>
        <el-table-column prop="user.real_name" label="姓名" min-width="100" />
        <el-table-column prop="user.phone" label="手机号码" min-width="120" />
        <el-table-column prop="created_at" label="邀请时间" min-width="150" />
        <el-table-column prop="user.phone" label="用户状态" min-width="100">
          <template #default="scope">
            <el-tag size="small" v-if="scope.row.status === -1" type="warning"> 待处理 </el-tag>
            <el-tag size="small" v-if="scope.row.status === 0" type="danger"> 已拒绝 </el-tag>
            <el-tag size="small" v-if="scope.row.status === 1" type="info"> 已同意 </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="user.phone" label="审核状态" min-width="100">
          <template #default="scope">
            <template v-if="scope.row.status === 1">
              <el-tag size="small" v-if="scope.row.verify === -1" type="danger"> 已拒绝 </el-tag>
              <el-tag size="small" v-if="scope.row.verify === 0" type="danger"> 待审核 </el-tag>
              <el-tag size="small" v-if="scope.row.verify === 1" type="info"> 已同意 </el-tag>
            </template>
          </template>
        </el-table-column>
        <el-table-column width="160" label="操作">
          <template #default="scope">
            <el-button type="primary" link @click="handleDelete(scope.row.id, scope.$index)">
              删除
            </el-button>
            <template v-if="scope.row.status == 1 && scope.row.verify == 0">
              <el-button type="primary" link @click="handleAgree(scope.row.id)">
                同意
              </el-button>

              <el-button type="primary" link @click="handleRefuse(scope.row.id)">
                拒绝
              </el-button>
            </template>
          </template>
        </el-table-column>
      </el-table>
      <el-pagination
        :page-size="limit"
        :current-page="page"
        layout="total,prev, pager, next, jumper"
        :total="total"
        @current-change="pageChange"
      />
    </el-dialog>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public.ts'
import { getApplyApi, applyDeleteApi, applyAgreeApi, applyRefuseApi } from '@/api/user.ts'
export default {
  emits: ['getQuantity', 'handleInvitees'],
  name: 'InviteesTable',
  data() {
    return {
      dialogFormVisible: false,
      dataTable: [],
      page: 1,
      limit: 15,
      total: 0
    }
  },
  methods: {
    open() {
      this.dialogFormVisible = true
      this.getList()
    },
    async getList() {
      const result = await getApplyApi({
        page: this.page,
        limit: this.limit
      })
      this.dataTable = result.data.list
      this.total = result.data.count
    },
    // 删除申请人
    handleDelete(id, index) {
      modalSure('删除该申请人吗').then(() => {
        applyDeleteApi(id).then((res) => {
          this.dataTable.splice(index, 1)
          this.getList()
        })
      })
    },
    // 同意申请人
    async handleAgree(id) {
      await applyAgreeApi(id)
      this.getList()
      this.handleEmit()
      await this.$emit('getQuantity')
    },
    // 拒绝申请人
    async handleRefuse(id) {
      await applyRefuseApi(id)
      this.getList()
      this.handleEmit()
      await this.$emit('getQuantity')
    },
    pageChange(num) {
      this.page = num
      this.getList()
    },
    handleEmit() {
      this.$emit('handleInvitees')
    }
  }
}
</script>

<style lang="scss" scoped>
::-webkit-scrollbar-thumb {
  -webkit-box-shadow: inset 0 0 6px #ccc;
}
::-webkit-scrollbar {
  width: 4px !important; /*对垂直流动条有效*/
}
.avatar {
  width: 40px;
  height: 40px;
}
</style>
