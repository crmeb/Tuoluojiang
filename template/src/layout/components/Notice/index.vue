<!-- 消息通知 -->
<template>
  <div class="notice">
    <div class="header-notice right-menu-item">
      <span class="el-dropdown-link">
        <el-badge
          v-if="userStore.messageCount > 0"
          @click="goMessageCenter"
          :is-dot="false"
          :value="userStore.messageCount"
          :max="99"
        >
          <el-tooltip effect="dark" content="消息通知" placement="bottom">
            <i class="iconfont iconxiaoxi2" />
          </el-tooltip>
        </el-badge>
        <span v-else @click="goMessageCenter">
          <el-tooltip effect="dark" content="消息通知" placement="bottom">
            <i class="iconfont iconxiaoxi2" style="margin-top: 2px;" />
          </el-tooltip>
        </span>
      </span>
      <!-- 消息列表 -->
      <noticeList ref="noticeList"></noticeList>
    </div>
  </div>
</template>
<script>
import { mapStores } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import { defineAsyncComponent } from 'vue'
import { roterPre } from '@/settings.ts'
export default {
  name: 'Notice',
  components: {
    noticeList: defineAsyncComponent(() => import('./noticeList'))
  },
  computed: {
    ...mapStores(useUserStore)
  },
  data() {
    return {
      count: 0,
      roterPre: roterPre,
      where:{
        page:1,
        limit:1,
      }
    }
  },
  methods: {
    goMessageCenter() {
      this.$refs.noticeList.openBox()
    }
  }
}
</script>

<style lang="scss" scoped>
.header-notice {
  position: relative;
  cursor: pointer;
  .iconxiaoxi2 {
    font-size: 18px;
    color: #606266;
  }
}
.el-badge {
  top: 1px;
}
:deep(.el-badge__content) {
  border: none;
  top: 4px;
}
.el-icon-message-solid {
  color: #2d8cf0;
  cursor: pointer;
  font-size: 16px;
  &:focus {
    outline: 0;
  }
}
</style>
