<template>
  <div class="assistant-dialog">
    <div class="dialog-item">
      <div class="avatar">
        <img :src="logo" alt="assistant-avatar" />
      </div>
      <div class="content">
        <div class="title">陀螺匠应用搭建小助手</div>
        <div class="desc">您好，欢迎使用陀螺匠，我是陀螺匠应用搭建小助手，您可以向我提出字段设置相关问题。</div>
      </div>
    </div>

    <div class="box">
      <div v-for="(item, index) in list" :key="index">
        <!-- 用户消息，居右 -->
        <div class="user">
          <div class="user-content">
            <div class="desc">{{ item.user }}</div>
          </div>
        </div>
        <!-- 助手消息，居左 -->

        <div class="ai-content">
          <div v-if="!item.loading" class="desc"><span class="el-icon-success" /> {{ item.assistant }}</div>
          <img v-else src="../../../../assets/images/loading-ai.gif" alt="" style="width: 40px" />
        </div>
      </div>
    </div>
    <div>
      <el-input
        v-model="userMessage"
        class="parameter-input"
        type="textarea"
        maxlength="50"
        ref="userMessage"
        @keyup.enter="handleSend"
        placeholder="很高兴为您服务，有什么需要帮助的吗？"
      >
      </el-input>

      <div class="append">
        <img src="../../../../assets/images/sendLogo.png" alt="" class="img" @click="handleSend" />
      </div>
    </div>
  </div>
</template>

<script>
import { getStorageJson } from '@/utils/storage.ts'
export default {
  emits: ['handleSend'],
  name: 'AssistantDialog',
  data() {
    return {
      userMessage: '',
      logo: '',
      list: [],
      showAppendFormReply: false, // 控制是否显示“追加用户信息表单”的对话
      showCreateForm: false // 控制是否显示“创建收集表单”的对话
    }
  },
  mounted() {
    this.logo = getStorageJson('sitedata', {}).site_logo
    this.list = []
    if (this.$refs.userMessage) {
      this.$refs.userMessage.focus()
    }
  },
  methods: {
    sendSuccess(res) {
      if (res.status == 200) {
        setTimeout(() => {
          this.list[this.list.length - 1].loading = false
          this.list[this.list.length - 1].assistant = '已处理完毕！'
        }, 500)
      } else {
        this.list[this.list.length - 1].loading = false
        this.list[this.list.length - 1].assistant = res.message
      }
    },
    handleSend() {
      if (this.list.length > 0 && this.list[this.list.length - 1].loading) {
        this.$message.error('正在处理中，请稍后再试')
        return
      }
      if (this.userMessage) {
        this.list.push({ user: JSON.parse(JSON.stringify(this.userMessage)), assistant: '', loading: true })
        this.$emit('handleSend', this.userMessage)
        this.userMessage = ''
      }
      const boxElement = document.querySelector('.box')
      if (boxElement) {
        if (boxElement.scrollHeight >= boxElement.clientHeight) {
          requestAnimationFrame(() => {
            boxElement.scrollTo({
              top: boxElement.scrollHeight - boxElement.clientHeight,
              behavior: 'auto'
            })
            boxElement.scrollTop = boxElement.scrollHeight
          })
        }
      }
    }
  }
}
</script>

<style scoped lang="scss">
.assistant-dialog {
  position: relative;
  width: 100%;
  border: 1px solid #dcdfe6;
  border-radius: 8px;
  padding: 10px;
  background-color: #ffffff;
  height: 100%;
}
.dialog-item {
  display: flex;
  margin-bottom: 16px;
}

.avatar {
  width: 20px;
  height: 20px;
  margin-right: 10px;
}
.avatar img {
  width: 20px;
  height: 20px;
  border-radius: 50%;
}
.content {
  display: flex;
  flex-direction: column;
  .title {
    font-family: PingFang SC, PingFang SC;
    font-weight: 500;
    font-size: 13px;
    color: #303133;
  }
  .desc {
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 12px;
    color: #909399;
    line-height: 17px;
    margin-top: 7px;
  }
}

.user {
  display: flex;
  justify-content: flex-end;
}

.user-content {
  padding: 10px;
  background: linear-gradient(329deg, #f4f1ff 1%, #e4f1fd 94%);
  border-radius: 12px;
  font-size: 12px;
  color: #303133;
}
.ai-content {
  padding: 10px;
  border-radius: 12px;
  font-size: 12px;
  color: #1890ff;
}

.box {
  height: calc(100% - 170px);
  scrollbar-width: none; /* firefox */
  -ms-overflow-style: none; /* IE 10+ */
  overflow-y: auto;
}

.del-text {
  .el-icon-delete {
    font-size: 14px;
    margin-left: 8px;
    cursor: pointer;
  }
}
.parameter-input {
  position: absolute;
  bottom: 10px;
  left: 10px;
  right: 10px;
  width: calc(100% - 20px);

  :deep(.el-textarea__inner) {
    resize: none;
    padding-right: 20px;
    height: 70px;
    border-radius: 14px;
    border: 1px solid transparent;
    box-shadow: none;
    background-origin: border-box;
    background-clip: padding-box, border-box;
    background-image: linear-gradient(#fff, #fff),
      linear-gradient(140deg, rgba(24, 144, 255, 1), rgba(164, 150, 255, 1));
  }
}
.append {
  position: absolute;
  bottom: 20px;
  right: 20px;
  font-weight: 400;
  font-size: 12px;
  color: #606266;
  .img {
    cursor: pointer;
    width: 24px;
    height: 24px;
  }
}
</style>
