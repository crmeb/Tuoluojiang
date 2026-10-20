<template>
  <el-card body-style="padding:20px;">
    <el-row class="calendar_title achievementPb">
      <el-col :span="12" class="row-middle">
        <div class="dynamics">业绩统计</div>
      </el-col>
      <el-col :span="12" class="text-right calendar_title-left">
        <span @click="workStatisticsList(0)" :class="achievementTypes === 0 ? 'active' : ''">部门业绩</span>
        <span class="line">|</span>
        <span @click="workStatisticsList(1)" :class="achievementTypes === 1 ? 'active' : ''">本人业绩</span>
      </el-col>
    </el-row>
    <!-- 骨架：复刻真实内容的两排栅格，保证加载前后高度一致 -->
    <template v-if="loading">
      <div class="achievementContent el-skeleton is-animated">
        <div class="item" v-for="i in 4" :key="'sk-top' + i">
          <el-skeleton-item variant="text" class="sk-text" />
          <el-skeleton-item variant="text" class="sk-num" />
        </div>
      </div>
      <div class="achievementContent activeContent el-skeleton is-animated">
        <div class="item" v-for="i in 4" :key="'sk-bottom' + i">
          <el-skeleton-item variant="text" class="sk-text" />
          <el-skeleton-item variant="text" class="sk-num" />
        </div>
      </div>
    </template>
    <template v-else>
      <div class="achievementContent">
        <div class="item" v-for="(item, index) in achievementList.slice(0, 4)" :key="index">
          <span class="text">{{ item.title }}</span>
          <span class="num">{{ item.value }}</span>
        </div>
      </div>
      <div class="achievementContent activeContent">
        <div class="item" v-for="(item, index) in achievementList.slice(4)" :key="index">
          <span class="text">{{ item.title }}</span>
          <span class="num">{{ item.value }}</span>
        </div>
      </div>
    </template>

    <!-- 业绩统计管理弹窗：append-to-body，挂在卡片内不影响布局 -->
    <quick-manage ref="quickManageRef" :config="manageConfig" @isSuccess="handleManageSuccess" />
  </el-card>
</template>

<script>
import { ref, onMounted, getCurrentInstance, defineAsyncComponent } from 'vue'
import { workStatisticsApi, workStatisticsApiAll } from '@/api/user.ts'

export default {
  name: 'AchievementCard',
  components: {
    quickManage: defineAsyncComponent(() => import('../quickManage'))
  },
  setup() {
    const { proxy } = getCurrentInstance()

    const achievementList = ref([])
    const achievementTypes = ref(0)
    const manageConfig = ref({})
    const loading = ref(true)

    // 业绩统计
    async function workStatisticsList(type) {
      achievementTypes.value = type

      try {
        const result = await workStatisticsApi(type)
        achievementList.value = result.data
      } finally {
        loading.value = false
      }
    }
    // 打开业绩统计管理弹窗；当前无模板入口，保留原逻辑
    async function handlePerformance() {
      const result = await workStatisticsApiAll()
      const config = {
        title: '业绩统计管理',
        type: 'statistics',
        width: '600px',
        data: result.data.select,
        otherArr: [
          {
            cate_name: '业绩简报',
            fast_entry: result.data.list
          }
        ]
      }
      manageConfig.value = config
      proxy.$refs.quickManageRef.handleOpen(config)
    }
    // 保存成功后按当前所选维度刷新自身统计
    function handleManageSuccess() {
      workStatisticsList(achievementTypes.value)
    }

    onMounted(() => {
      workStatisticsList(0).finally(() => {})
    })

    return {
      achievementList,
      achievementTypes,
      manageConfig,
      loading,
      workStatisticsList,
      handlePerformance,
      handleManageSuccess
    }
  }
}
</script>

<style lang="scss" scoped>
@import './card-common';

.achievementContent {
  margin-top: 20px;
  display: flex;
  flex-wrap: wrap;
  padding: 0 38px;
  justify-content: space-between;
  gap: 20px;
  .item {
    flex: 0 0 calc(18% - 38px);
    display: flex;
    flex-direction: column;

    .num {
      font-family: D-DIN-PRO, D-DIN-PRO;
      font-weight: 600;
      font-size: 24px;
      color: #303133;
      margin: 10px 0;
    }
    .text {
      font-family: PingFang SC, PingFang SC;
      font-weight: 400;
      font-size: 13px;
      color: #606266;
    }
  }
}
.activeContent {
  background: #f7fbff;
  padding: 14px 38px;
  display: flex;
  align-items: center;
}
.achievementPb {
  padding: 0;
}

// 骨架条尺寸对齐真实的 .text / .num，避免加载前后高度跳动
.sk-text {
  width: 60%;
  height: 18px;
}
.sk-num {
  width: 80%;
  height: 24px;
  margin: 10px 0;
}
</style>
