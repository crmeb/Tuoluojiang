<template>
  <el-card class="mt14">
    <div class="v-height-flag">
      <div class="plan-tabs-content">
        <el-tabs v-model="tabCur" @tab-click="handleClick">
          <el-tab-pane :label="$t('access.selfassessment01')" name="0" />
          <el-tab-pane :label="$t('access.mydepartment')" name="1" />
        </el-tabs>
      </div>
      <div class="mt20">
        <assess-statistics ref="assessStatistics" />
      </div>
    </div>
  </el-card>
</template>

<script>
export default {
  name: 'WorkStatistics',
  components: {
    assessStatistics: () => import('./components/assessStatistics')
  },
  data() {
    return {
      tabCur: 0,
      checkBtn: true
    }
  },
  mounted() {},
  methods: {
    handleClick(number) {
      this.tabCur = number.index
      this.$refs.assessStatistics.type = number.name
      this.$refs.assessStatistics.getTableData()
    }
  }
}
</script>

<style lang="scss" scoped>
::v-deep .el-tabs__header {
  margin-bottom: 0;
}
::v-deep .el-tabs__nav-wrap::after {
  display: none;
}
.plan-tabs-content {
  margin: 0 -20px 14px -20px;
  border-bottom: 2px solid #f0f2f5;
  ::v-deep .el-tabs {
    padding: 0 20px;
  }
  ::v-deep .el-tabs__item {
    line-height: 26px;
  }
}
</style>
