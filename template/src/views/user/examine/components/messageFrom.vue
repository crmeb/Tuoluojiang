<template>
  <div>
    <div class="title">
      <span class="shu mr10"></span>
      留言
    </div>
    <div v-for="(item, index) in reply" :key="index" class="acea-row mb10">
      <div style="width: 100%">
        <div class="mb15 flex">
          <div class="pic mr10">
            <img :src="item.card.avatar" />
          </div>
          <div style="width: 100%">
            <div class="between-box">
              <div>
                <div class="fonts">
                  <span>{{ item.card.name }}</span>

                  <span class="time">{{ item.created_at }}</span>
                </div>

                <div class="fonts content">{{ item.content }}</div>
              </div>
              <div class="del">
                <div
                  v-if="$store.state.user.userInfo.uid === item.card.uid"
                  @click="del(item.id)"
                  class="el-icon-delete mr5 mt10"
                >
                  删除
                </div>
              </div>
            </div>

            <div class="mt10" v-if="item.files.length > 0">
              <upload-list :file-list="item.files"></upload-list>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { approveReplyApi, approveReplyDelApi } from '@/api/business'
export default {
  name: 'MessageFrom',
  components: {
    uploadList: () => import('@/components/form-common/oa-uploadList')
  },
  props: {
    examineData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      textarea: '',
      reply: []
    }
  },
  watch: {
    examineData: {
      handler(nVal) {
        this.reply = nVal.reply
      },
      immediate: true
    }
  },
  methods: {
    async add() {
      if (this.textarea == '') {
        return this.$message.error('请输入留言')
      }
      await approveReplyApi({
        apply_id: this.examineData.id,
        content: this.textarea
      })
      this.textarea = ''
      await this.$emit('upDate', this.examineData.id)
    },
    async del(id) {
      await approveReplyDelApi(id)
      await this.$emit('upDate', this.examineData.id)
    }
  }
}
</script>

<style scoped lang="scss">
.title {
  display: flex;
  align-items: center;
  font-family: PingFang SC, PingFang SC;
  font-weight: 500;
  font-size: 14px;
  color: #303133;
  margin-bottom: 20px;
}
.shu {
  width: 3px;
  height: 16px;
  background: #1890ff;
  display: inline-block;
}

.clear {
  clear: both;
}
.between-box {
  display: flex;
  justify-content: space-between;
}
.add {
  float: right;
}
.acea-row {
  display: flex;
  justify-content: space-between;
}
.inpBox {
  background-color: #f9f9f9;
  ::v-deep .el-textarea__inner {
    border: none;
    resize: none;
    background-color: #f9f9f9;
  }
}
.pic {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  overflow: hidden;
  img {
    width: 100%;
    height: 100%;
  }
}
.fonts {
  max-width: 400px;
  font-family: PingFang SC, PingFang SC;
  font-weight: 500;
  font-size: 14px;
  color: #303133;
}
.content {
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #303133;
  margin-top: 8px;
}
.time {
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 12px;
  color: #909399;
  margin-left: 4px;
}
.del {
  cursor: pointer;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #606266;
  margin-left: 8px;
}
</style>
