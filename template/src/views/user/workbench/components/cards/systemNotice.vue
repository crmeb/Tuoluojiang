<template>
  <div class="system-notice-card">
  <el-card body-style="padding:0" class="table-box note" style="height: 386px">
    <div class="calendar_title clearfix">
      <div class="pull-left acea-row row-middle">
        <div class="dynamics">系统通知</div>
      </div>
      <div class="pull-right acea-row row-middle">
        <div class="notice-more" @click="handleNewMore()">
          更多
          <i class="el-icon-arrow-right"></i>
        </div>
      </div>
    </div>
    <!-- 骨架：复刻 list 内的消息行，撑满固定高度的卡片 -->
    <div v-if="loading" class="list el-skeleton is-animated">
      <div class="item sk-item" v-for="i in 7" :key="'sk-note' + i">
        <el-skeleton-item variant="text" class="sk-line" />
        <el-skeleton-item variant="text" class="sk-time" />
      </div>
    </div>
    <div v-else-if="systemNote.length > 0" class="list">
      <div
        v-for="(item, index) in systemNote"
        :key="index"
        class="item acea-row row-between-wrapper pointer"
        @click="handleDetails(item)"
        :class="item.is_read ? 'finish' : ''"
      >
        <div class="item-list system-note">
          <div class="line1">
            <span class="label">【{{ item.title }}】</span>
            <span>{{ item.message }}</span>
          </div>
          <div class="time">{{ moment(item.created_at).format('MM-DD HH:mm') }}</div>
        </div>
      </div>
    </div>
    <default-page v-else :min-height="406" :top="`60px`" :index="14" />
  </el-card>
  <noticeList ref="noticeList" v-if="noticeListVisible"></noticeList>
  <message-details ref="messageDetails" :message-data="messageData" />
  </div>
</template>

<script>
import moment from 'moment'
import { ref, onMounted, getCurrentInstance } from 'vue'
import { noticeMessageListApi } from '@/api/user'

export default {
  name: 'SystemNoticeCard',
  components: {
    defaultPage: () => import('@/components/common/defaultPage'),
    messageDetails: () => import('@/views/user/news/components/messageDetails'),
    noticeList: () => import('@/layout/components/Notice/noticeList')
  },
  setup() {
    const { proxy } = getCurrentInstance()
    const noticeListVisible = ref(false)
    const systemNote = ref([]) // 系统通知
    const messageData = ref({})
    const loading = ref(true)

    // 获取系统消息列表
    async function getNewListData() {
      try {
        const result = await noticeMessageListApi({ page: 1, limit: 11, cate_id: '', is_read: '' })
        systemNote.value = result.data.list
      } finally {
        loading.value = false
      }
    }
    // 打开系统消息弹窗
    function handleDetails(row) {
      messageData.value = {
        width: '560px',
        data: row
      }
      proxy.$refs.messageDetails.handleOpen()
    }
    // 打开系统通知弹窗
    function handleNewMore() {
      noticeListVisible.value = true
      setTimeout(() => {
        proxy.$refs.noticeList.openBox()
      }, 100)
    }

    onMounted(() => {
      getNewListData()
    })

    return { moment, noticeListVisible, systemNote, messageData, loading, getNewListData, handleDetails, handleNewMore }
  }
}
</script>

<style lang="scss" scoped>
@import './card-common';

::-webkit-scrollbar-thumb {
  -webkit-box-shadow: inset 0 0 6px #ccc;
  display: none;
}
::-webkit-scrollbar {
  width: 4px !important;
}
.table-box .list:hover::-webkit-scrollbar-thumb {
  display: block;
}

.table-box {
  ::v-deep .table-box-title {
    padding-left: 21px;
    height: 58px;
    border-bottom: 0;
    display: flex;
    align-items: center;
    color: #000000;
  }
  .el_tabs {
    padding: 5px 30px;
  }
  .list {
    padding: 0 20px 0 16px;
    height: 280px;
    overflow-x: hidden;
    overflow-y: auto;
    .item {
      margin-top: 23px;
      font-size: 13px;
      font-weight: 400;
      color: rgba(0, 0, 0, 0.85) !important;
      &:first-of-type {
        margin-top: 0;
      }
      &.finish {
        color: #909399 !important;
        .time {
          color: #909399;
        }
        .el-icon-check {
          margin-right: 6px;
          vertical-align: bottom;
          font-size: 14px;
        }
        .line1 {
          color: #909399 !important;
          .label {
            color: #909399 !important;
          }
        }
      }
      .item-list {
        width: 100%;
        display: flex;
      }
      .line1 {
        width: 70%;
        color: #303133;
      }
      .system-note {
        .line1 {
          width: calc(100% - 80px);
        }
        .time {
          width: 80px;
        }
      }
      .label {
        color: #1890ff;
        margin-right: 4px;
      }
      .time {
        width: 30%;
        text-align: right;
      }
      .el-icon-remove-outline {
        color: #1890ff;
      }
    }
  }

  &.note .list .item .label {
    color: rgba(0, 0, 0, 0.85) !important;
  }
}

// 骨架行：沿用 .item 的间距，只补 flex 与条目尺寸
.sk-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.sk-line {
  width: calc(100% - 90px);
  height: 18px;
}
.sk-time {
  width: 80px;
  height: 18px;
}
</style>
