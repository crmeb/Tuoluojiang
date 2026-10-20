<template>
  <div class="divBox">
    <div class="v-height-flag">
      <el-card>
        <consume ref="consume"></consume>
      </el-card>
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
export default {
  name: 'ConsumeManage',
  components: {
    consume: defineAsyncComponent(() => import('../fixed/components/consume'))
  }
}
</script>

<style lang="scss" scoped>
.list-header {
  padding: 0;
}

.table-box {
  :deep(.el-table th) {
    background-color: rgba(247, 251, 255, 1);
  }
}
</style>
