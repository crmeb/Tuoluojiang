<template>
  <div class="p_box">
    <!-- 日程评论组件 -->
    <div v-for="(item, index) in commentList" :key="index">
      <div class="flex">
        <div class="p_left"><img :src="item.from_user.avatar" alt="" /></div>

        <div class="p_right">
          <div class="p_name">
            <span>{{ item.from_user.name }}</span>

            <div>
              <span class="iconfont iconhuifu" @click="replyFn(item)"></span>
              <span class="el-icon-delete" v-if="item.from_user.id == userId" @click="commentDel(item)"></span>
            </div>
          </div>
          <div class="p_content">
            {{ item.content }}
          </div>
          <div class="mt10" v-if="item.files.length > 0">
            <upload-list :file-list="item.files"></upload-list>
          </div>
          <div class="p_time">{{ item.created_at }}</div>

          <template v-if="item.children.length !== 0">
            <div class="flex mt14" v-for="(per, v) in item.children" :key="v">
              <div class="p_left"><img :src="per.from_user.avatar" alt="" /></div>
              <div class="p_right">
                <div class="p_name">
                  <span>{{ per.from_user.name }}</span>
                  <div>
                    <span class="iconfont iconhuifu" @click="replyFn(item, per)"></span>
                    <span class="el-icon-delete" v-if="per.from_user.id == userId" @click="commentDel(per)"></span>
                  </div>
                </div>
                <div class="p_content">
                  <span class="p_time"> 回复{{ per.to_user.name }} : </span> {{ per.content }}
                </div>
                <div class="p_time">
                  {{ per.created_at }}
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
      <div class="splitLine"></div>
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { getStorageJson } from '@/utils/storage.ts'
export default {
  emits: ['replyFn', 'commentDel'],
  name: 'CrmebOaEntCommentList',
  components: {
    uploadList: defineAsyncComponent(() => import('@/components/form-common/oa-uploadList'))
  },
  props: ['commentList'],
  data() {
    return {
      userId: getStorageJson('userInfo', {}).id
    }
  },
  methods: {
    replyFn(data, row) {
      this.$emit('replyFn', data, row)
    },
    commentDel(data) {
      this.$emit('commentDel', data)
    }
  }
}
</script>

<style lang="scss" scoped>
.splitLine {
  margin: 14px 0;
  border-color: #eeeeee;
}
.el-icon-delete {
  color: #909399;
  font-size: 14px;
  cursor: pointer;
}
.iconhuifu {
  color: #909399;
  font-size: 14px;
  cursor: pointer;
  margin-right: 10px;
}

.reply {
  color: #909399;
  cursor: pointer;
  font-size: 13px;
  margin-right: 8px;
}
.p_box {
  margin-top: 10px;
  width: 100%;
  // height: 246px;
  background: #f9f9f9;
  border-radius: 2px 2px 2px 2px;
  padding: 10px;
  .p_left {
    margin-right: 10px;
    img {
      display: block;
      width: 32px;
      height: 32px;
      border-radius: 50%;
    }
  }
  .p_right {
    width: 100%;
    font-family: PingFang SC-Medium, PingFang SC;
    font-size: 12px;
    .p_name {
      width: 100%;
      display: flex;
      justify-content: space-between;
      font-weight: 500;
      color: #303133;

      margin-bottom: 4px;
    }
    .iconshanchu1 {
      color: #c0c4cc;
    }
    .p_content {
      font-size: 12px;
      font-weight: 400;
      color: #303133;
      line-height: 16px;
    }
    .p_time {
      margin-top: 6px;
      font-weight: 400;
      color: #c0c4cc;
    }
  }
}
</style>
