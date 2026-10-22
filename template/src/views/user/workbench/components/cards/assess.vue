<template>
  <el-card body-style="padding:0" style="height: 386px">
    <div class="calendar_title clearfix">
      <div class="pull-left acea-row row-middle">
        <div class="dynamics">当前考核</div>
      </div>
      <div class="pull-right acea-row row-middle">
        <div class="notice-more" @click="handleAssessMore()">
          更多
          <i class="el-icon-arrow-right"></i>
        </div>
      </div>
    </div>
    <echarts v-if="assessNowData.length > 0" :id="assessNowData[0].id" />
  </el-card>
</template>

<script>
import { ref, onMounted } from 'vue'
import { userAssessSubord } from '@/api/user'
import { pageJumpTo } from '@/libs/public'

export default {
  name: 'AssessCard',
  components: {
    echarts: () => import('../echart')
  },
  setup(_, { emit }) {
    const assessNowData = ref([])
    const assessLoading = ref(false)

    // 业绩考核
    async function getAssessMine() {
      let data = {
        handle: 1,
        time: '',
        status: 1
      }
      const result = await userAssessSubord(data)
      assessLoading.value = true
      assessNowData.value = result.data.list ? result.data.list : []
      // 上报可见性，供根组件动态分配第三行 span 与 v-show
      emit('assess-ready', assessLoading.value && assessNowData.value.length > 0)
    }
    // 跳转到绩效考核
    function handleAssessMore() {
      pageJumpTo('/user/assessment/my')
    }

    onMounted(() => {
      getAssessMine()
    })

    return { assessNowData, assessLoading, getAssessMine, handleAssessMore }
  }
}
</script>

<style lang="scss" scoped>
@import './card-common';
</style>
