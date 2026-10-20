<template>
  <div class="divBox" :class="{ 'none-bg': !checkBtn }">
    <!-- 我的考核 -->
    <div v-if="checkBtn">
      <el-card class="employees-card-bottom assessment-card">
        <div class="assessment-card-body">
          <div class="plan-tabs-content mb20">
            <el-tabs v-model="tabCur">
              <el-tab-pane label="当前考核" name="0" />
              <el-tab-pane label="我的自评" name="1" />
              <el-tab-pane label="考核统计" name="2" />
            </el-tabs>
          </div>
          <div class="assessment-scroll">
            <!-- 当前考核   -->
            <current v-if="tabCur == 0" :id="id" />
            <!-- 我的自评   -->
            <self v-if="tabCur == 1" ref="self" @selfHandleCheck="selfHandleCheck" />
            <!-- 自评统计   -->
            <assess-statistics v-if="tabCur == 2" ref="assessStatistics" :is-mine="true" />
          </div>
        </div>
      </el-card>
    </div>

    <!-- 绩效审核详情 -->
    <audit-process
      v-if="!checkBtn"
      :id="id"
      ref="auditProcess"
      :is-tips="isTips"
      :rowData="rowData"
      :process-index="status"
      :strType="strType"
      :preset-info="presetInfo"
      @saveOk="saveOk"
      @auditProcess="auditProcess"
    />
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import { userAssessInfo } from '@/api/user.ts'

export default {
  name: '',
  components: {
    current: defineAsyncComponent(() => import('./components/current')),
    self: defineAsyncComponent(() => import('./components/self')),
    assessStatistics: defineAsyncComponent(() => import('@/views/user/workStatistics/components/assessStatistics')),
    auditProcess: defineAsyncComponent(() => import('./components/auditProcess'))
  },
  props: {},
  data() {
    return {
      tabCur: 0,
      id: 0,
      checkBtn: true,
      isTips: true,
      status: 0,
      strType: '',
      rowData: {},
      // 由路由 id 预取的考核详情，透传给 auditProcess 复用，避免重复请求
      presetInfo: null
    }
  },
  watch: {
    tabCur: function (newVal, old) {
      this.getList()
    }
  },
  mounted() {
    const { tab, id } = this.$route.query
    if (id) {
      // 路由携带 id 时，直接进入「我的自评」下的考核详情
      this.openAssessDetail(id)
    } else {
      this.tabCur = tab || '0'
    }
  },

  methods: {
    // 先取详情拿到考核所处阶段，再复用列表点击的打开逻辑
    async openAssessDetail(id) {
      try {
        const result = await userAssessInfo(id)
        const assessInfo = (result.data && result.data.assessInfo) || {}
        this.selfHandleCheck(
          { status: Number(assessInfo.status) || 0, id: Number(id), tabIndex: '1' },
          'check',
          assessInfo,
          result
        )
      } catch (e) {
        // 详情获取失败时退回「我的自评」列表
        this.tabCur = '1'
      }
    },
    selfHandleCheck(data, str, row, presetInfo = null) {
      if (row) {
        this.rowData = row
      }
      this.presetInfo = presetInfo
      this.checkBtn = false
      this.status = data.status
      this.id = data.id
      this.tabCur = data.tabIndex
      this.strType = str
    },
    auditProcess(data) {
      if (data.return == 1) {
        this.checkBtn = true
        this.getList()
      }
    },
    saveOk() {
      this.checkBtn = true
      this.getList()
    },
    getList() {
      this.$nextTick(() => {
        switch (parseInt(this.tabCur)) {
          case 1:
            setTimeout(() => {
              // 详情态下 self 未渲染，避免取不到 ref 报错
              this.$refs.self && this.$refs.self.getTableData()
            }, 300)
            break
          case 2:
            setTimeout(() => {
              if (!this.$refs.assessStatistics) return
              this.$refs.assessStatistics.typeLocal = 0
              this.$refs.assessStatistics.getTableData()
            }, 300)
            break
        }
      })
    }
  }
}
</script>
<style>
.none-bg {
  background: none !important;
  border: none !important;
}
</style>
<style scoped lang="scss">
.plan-tabs-content {
  :deep(.el-tabs__header) {
    margin-bottom: 0;
    .el-tabs__nav-wrap::after {
      height: 1px;
      background-color: #eee;
    }
  }
}

.assessment-card {
  height: calc(100vh - 82px);
  display: flex;
  flex-direction: column;
  overflow: hidden;

  :deep(.el-card__body) {
    flex: 1;
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
}

.assessment-card-body {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.assessment-scroll {
  flex: 1;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
}
</style>
