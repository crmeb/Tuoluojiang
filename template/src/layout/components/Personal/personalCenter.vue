<template>
  <div class="box-container">
    <el-drawer
      class="personal-center-drawer"
      title="个人中心"
      v-model="drawer"
      direction="rtl"
      :before-close="handleClose"
      size="650px"
      :append-to-body="true"
      :modal="true"
    >
      <room ref="room" @isOk="handleClose"></room>
    </el-drawer>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
export default {
  name: 'User',
  components: {
    room: defineAsyncComponent(() => import('@/views/setting/user/room/index'))
  },
  data() {
    return {
      drawer: false
    }
  },
  methods: {
    openBox() {
      this.drawer = true
    },
    handleClose() {
      this.drawer = false
    }
  }
}
</script>

<style lang="scss" scoped>
.tab-box {
  display: flex;
  align-items: center;
  .tab-item {
    margin-right: 30px;
    font-size: 14px;
    color: #000000;
    cursor: pointer;
    &.on {
      color: #1890ff;
    }
  }
}
.form-wrapper {
  padding-right: 63px;
  padding-bottom: 100px;
  .form-box {
    display: flex;
    flex-wrap: wrap;
    margin-top: 40px;
    .form-item {
      width: 50%;
    }
  }
}
.tips-txt {
  font-size: 13px;
  color: #999999;
}
.table-box {
  padding: 0 13px;
  .table-item {
    padding-bottom: 29px;
    border-bottom: 1px dashed rgba(102, 102, 102, 0.3);
    .title {
      margin: 20px 0 14px;
      padding-left: 10px;
      border-left: 2px solid #1890ff;
      font-size: 14px;
    }
  }
}
.add-btn {
  margin-top: 15px;
}
.item-box {
  display: flex;
  flex-wrap: wrap;
  .item {
    margin-left: 0 !important;
    margin-right: 10px;
    margin-bottom: 10px;
  }
}
:deep(.el-popover) {
  min-width: 166px;
}
.prop-txt {
  width: 166px;
  margin-right: 10px;
  height: 30px;
  line-height: 30px;
  font-size: 13px;
  cursor: pointer;
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.personal-center-drawer .el-drawer__header {
  border-bottom: 1px solid #d8d8d8;
  padding-bottom: 20px;
  margin-bottom: 0;
}
.personal-center-drawer .el-drawer__body {
  padding-bottom: 0;
  overflow-y: auto;
}
</style>
