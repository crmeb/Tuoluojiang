<template>
  <div>
    <el-card :body-style="{ padding: '10px 20px 0 20px' }" class="mb14">
      <el-tabs v-model="activeName" class="cr-header-tabs" @tab-change="handleClick">
        <el-tab-pane label="未读" name="1"></el-tab-pane>
        <el-tab-pane label="全部" name="2"></el-tab-pane>
      </el-tabs>
    </el-card>

    <el-card class="content employees-card" v-if="false">
      <div class="mb14">
        <form-box ref="formBox" @confirmData="confirmData" />
        <div class="mb20">
          <el-button v-if="activeName == 1" type="primary" @click="handleIsRead">标记为已读</el-button>
          <el-button @click="handleDelete">批量删除</el-button>
        </div>
      </div>
      <div>
        <el-table ref="table" :data="tableData" v-loading="loading" @selection-change="handleSelectionChange">
          <el-table-column type="selection" width="45"> </el-table-column>
          <el-table-column label="查看" width="50">
            <template #default="scope">
              <el-image :src="scope.row.is_read === 0 ? unreadIcon : readIcon"></el-image>
            </template>
          </el-table-column>
          <el-table-column prop="title" label="消息标题" min-width="100"></el-table-column>
          <el-table-column prop="message" label="消息内容" min-width="360" />
          <el-table-column prop="cate_name" label="类型" min-width="90" />
          <el-table-column prop="created_at" label="发送时间" min-width="120"></el-table-column>
          <el-table-column label="操作" width="100" fixed="right">
            <template #default="scope">
              <el-button type="primary" link @click="handleDetails(scope.row)">查看详情</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <div class="pagination mt14">
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
    </el-card>
    <!-- 通用弹窗表单   -->
    <dialog-form ref="dialogForm" :roles-config="rolesConfig" :form-data="formBoxConfig" @isOk="getList()" />
    <message-details ref="messageDetails" :message-data="messageData" @isOk="isOk" />
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { noticeMessageListApi, noticeMessageReadApi, noticeMessageDeleteApi } from '@/api/user.ts'
import { messageListApi } from '@/api/public.ts'

import unreadIcon from '@/assets/images/unread-icon.png';
import readIcon from '@/assets/images/read-icon.png';

export default {
  name: 'Apply',
  components: {
    dialogForm: defineAsyncComponent(() => import('./components/index')),
    formBox: defineAsyncComponent(() => import('./components/formBox')),
    messageDetails: defineAsyncComponent(() => import('./components/messageDetails'))
  },
  computed: {
    ...mapStores(useUserStore)
  },
  data() {
    return {
      unreadIcon,
      readIcon,
      rolesConfig: [],
      formBoxConfig: {},
      tableData: [],
      activeName: '1',
      multipleSelection: [],
      messageData: {},
      where: {
        page: 1,
        limit: 15,
        title: '',
        cate_id: '',
        is_read: 0
      },
      total: 0,
      loading: false
    }
  },
  mounted() {
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
    // 获取列表
    getTableData(type = 1) {
      if (type === 1) {
        this.loading = true
      }
      noticeMessageListApi(this.where)
        .then((res) => {
          if (type === 1) {
            this.loading = false
          }
          this.tableData = res.data.list
          this.total = res.data.messageNum
          if (this.activeName === '1') {
            this.userStore.setMessageCount(this.total)
          }
        })
        .catch(() => {
          if (type === 1) {
            this.loading = false
          }
        })
    },
    handleClick() {
      this.where.page = 1
      if (this.activeName == 1) {
        this.where.is_read = 0
      } else {
        this.where.is_read = ''
      }
      this.getTableData()
    },
    confirmData(data, val) {
      if (val == 'reset') {
        this.where.limit = 15
      }
      this.where.page = 1
      this.where.title = data.name
      this.where.cate_id = data.types
      this.getTableData()
    },
    handleDelete() {
      if (this.multipleSelection.length <= 0) {
        this.$message.error('至少选择一项内容')
      } else {
        modalSure('删除后不可恢复,您确认要删除吗').then(() => {
          const ids = []
          this.multipleSelection.map((value) => {
            ids.push(value.id)
          })
          this.batchMessageDelete({ ids: ids })
        })
      }
    },
    handleSelectionChange(val) {
      this.multipleSelection = val
    },
    handleDetails(row) {
      this.messageData = {
        width: '560px',
        data: row
      }
      this.$refs.messageDetails.handleOpen()
    },
    isOk() {
      this.getTableData(0)
    },
    handleIsRead() {
      if (this.multipleSelection.length <= 0) {
        this.$message.error('至少选择一项内容')
      } else {
        const ids = []
        this.multipleSelection.map((value) => {
          ids.push(value.id)
        })
        this.batchMessageRead(1, { ids: ids }, 1)
      }
    },
    // 批量标记未已读
    batchMessageRead(status, data, type) {
      noticeMessageReadApi(status, data).then(() => {
        this.getMessage()
        this.getTableData()
      })
    },
    // 批量删除消息
    batchMessageDelete(data) {
      noticeMessageDeleteApi(data).then(() => {
        this.getMessage()
        this.getTableData()
      })
    },
    // 消息数量
    getMessage() {
      messageListApi({ page: 1, limit: 5 }).then((res) => {
        const num = res.data.messageNum ? res.data.messageNum : 0
        this.userStore.setMessageCount(num)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.content {
  height: calc(100% - 65px);
  :deep(.el-card__body) {
    height: 100%;
  }
  .table-box {
    height: calc(100% - 144px) !important;
  }
  :deep(.el-image) {
    width: 23px !important;
    height: 23px !important;
  }
}
:deep(.el-table th) {
  background-color: #f7fbff;
}
.pagination {
  margin-top: 14px;
  display: flex;
  justify-content: flex-end;
}
</style>
