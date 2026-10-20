<template>
  <div>
    <el-dialog
      :append-to-body="true"
      :before-close="beforeClose"
      v-model="dialogVisible"
      :width="messageData.width"
      class="message-details"
    >
      <template v-if="messageData.data">
        <div :class="messageData.data.button_template ? '' : 'mb14'" class="details">
          <el-row>
            <el-col class="left">
              <img v-if="messageData.data.image" :src="messageData.data.image" alt="" class="image" />
              <div v-else class="left-icon">
                <i class="iconfont iconxiaoxi"></i>
              </div>
            </el-col>
            <el-col class="right">
              <div class="top">
                <el-tag effect="dark">{{ messageData.data.cate_name }}</el-tag>
              </div>
              <div
                v-if="messageData.data.buttons.length > 0"
                class="pointer"
                @click="handleConfirm(messageData.data.buttons[0])"
              >
                <div class="mt10 content over-text">{{ messageData.data.title }}</div>
                <div class="mt10 bottom">{{ messageData.data.message }}</div>
              </div>
              <div v-else class="pointer">
                <div class="mt10 content over-text">{{ messageData.data.title }}</div>
                <div class="mt10 bottom">{{ messageData.data.message }}</div>
              </div>
            </el-col>
          </el-row>
        </div>
      </template>
      <template #footer>
        <div v-if="messageData.data && messageData.data.buttons.length > 0" class="dialog-footer">
          <template v-if="messageData.data.cate_name !== '考勤'">
            <el-button
              v-for="(item, index) in messageData.data.buttons"
              :key="index"
              :disabled="selectedType.includes(item.action)"
              type="primary"
              @click="handleConfirm(item)"
              >{{ item.title }}</el-button
            >
          </template>
        </div>
      </template>
    </el-dialog>
    <message-handle-popup ref="messageHandlePopup" :detail="messageData.data"></message-handle-popup>
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import noticeHandle from '@/libs/noticeHandle.ts'
import { noticeMessageReadApi } from '@/api/user.ts'
import { messageListApi } from '@/api/public.ts'
import { roterPre } from '@/settings.ts'
import messageHandlePopup from '@/components/common/messageHandlePopup'
export default {
  emits: ['isOk'],
  name: 'MessageDetails',
  components: {
    messageHandlePopup
  },
  props: {
    messageData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  computed: {
    ...mapStores(useUserStore)
  },
  data() {
    return {
      dialogVisible: false,
      icon: 2,
      roterPre: roterPre,
      selectedType: ['delete', 'recall']
    }
  },
  watch: {
    messageData: {
      handler(nVal) {
        if (nVal.data.is_read === 0) {
          this.batchMessageRead(1, { ids: [nVal.data.id] })
        }
      },
      deep: true
    }
  },
  methods: {
    handleClose() {
      noticeHandle(this.messageData.data, 0)
      this.handleEmit()
      this.dialogVisible = false
    },
    beforeClose() {
      this.dialogVisible = false
    },
    handleOpen() {
      this.dialogVisible = true
    },
    handleConfirm(item) {
      this.dialogVisible = false
      if (this.selectedType.includes(item.action)) return false
      this.$refs.messageHandlePopup.openMessage(item, this.messageData.data)
    },
    noticeLink() {
      if (this.messageData.data.is_read === 0) {
        this.batchMessageRead(1, { ids: [this.messageData.data.id] })
      }
    },
    // 批量标记未已读
    batchMessageRead(status, data) {
      noticeMessageReadApi(status, data)
        .then((res) => {
          this.$emit('isOk')
          this.getMessage()
          // this.$message.success(res.message);
        })
        .catch((error) => {
          // this.$message.error(error.message);
        })
    },
    handleEmit() {
      this.$emit('isOk')
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
.details {
  .left {
    width: 72px;
    .image {
      width: 100%;
      height: 72px;
      display: inline-block;
    }
    .left-icon {
      width: 100%;
      height: 72px;
      text-align: center;
      line-height: 72px;
      border-radius: 50%;
      background-color: #e7f3ff;
      i {
        font-size: 36px;
        color: #1890ff;
      }
    }
  }
  .right {
    width: calc(100% - 72px);
    padding-left: 14px;
    :deep(.el-tag) {
      border-radius: 2px;
    }
    .content {
      font-size: 14px;
      font-weight: 600;
      color: #303133;
    }
    .bottom {
      font-size: 14px;
      color: #606266;
      line-height: 1.5;
    }
  }
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.message-details {
  .el-dialog__header {
    padding: 20px;
  }
  .el-dialog__headerbtn {
    position: absolute;
    top: 14px;
  }
}
</style>
