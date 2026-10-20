<template>
  <el-card body-style="padding:0" class="mt14">
    <el-row class="calendar_title">
      <el-col :span="12" class="row-middle">
        <div class="dynamics">常用功能</div>
      </el-col>
      <el-col :span="12" class="text-right calendar_title-left">
        <span class="display-align" @click="handleQuick">
          <i class="iconfont iconxitong-xitongshezhi-cebian"></i>
        </span>
      </el-col>
    </el-row>
    <div class="quick-content">
      <!-- 骨架：复刻 quick-list 一排入口，避免加载中闪一下空状态图 -->
      <div class="quick-list el-skeleton is-animated" v-if="loading">
        <div class="quick-list-item" v-for="i in 6" :key="'sk-quick' + i">
          <div>
            <el-skeleton-item variant="rect" class="sk-image" />
            <el-skeleton-item variant="text" class="sk-name" />
          </div>
        </div>
      </div>
      <div class="quick-list" v-else-if="quickVal.length > 0">
        <div
          class="quick-list-item"
          v-for="(item, index) in quickVal"
          @click="quickItem(item)"
          :key="'quick' + index"
        >
          <div class="pointer">
            <el-image :src="item.image" class="image"></el-image>
            <div class="name">{{ item.name }}</div>
          </div>
        </div>
      </div>
      <default-page v-else :min-height="47" :imgWidth="`65px`" :top="`30px`" :index="16" />
    </div>

    <!-- 快捷入口管理弹窗：append-to-body，挂在卡片内不影响布局 -->
    <quick-manage ref="quickManageRef" :config="manageConfig" @isSuccess="handleManageSuccess" />
  </el-card>
</template>

<script>
import { ref, onMounted, getCurrentInstance, defineAsyncComponent } from 'vue'
import { userWorkMenusApi } from '@/api/user.ts'
import { pageJumpTo } from '@/libs/public.ts'

export default {
  name: 'QuickCard',
  components: {
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage')),
    quickManage: defineAsyncComponent(() => import('../quickManage'))
  },
  setup() {
    const { proxy } = getCurrentInstance()

    const quickData = ref([])
    const quickVal = ref([])
    const manageConfig = ref({})
    const loading = ref(true)

    // 获取快捷入口列表
    async function getUserWorkMenus() {
      quickVal.value = []
      try {
        const result = await userWorkMenusApi()
        quickData.value = result.data
        quickVal.value = result.data.checkd
      } finally {
        loading.value = false
      }
    }
    // 点击快捷菜单跳转
    function quickItem(item) {
      pageJumpTo(item.pc_url)
    }
    // 打开快捷入口管理弹窗
    function handleQuick() {
      const config = {
        title: '快捷入口管理',
        width: '600px',
        data: JSON.parse(JSON.stringify(quickVal.value)),
        otherArr: quickData.value.cates
      }
      manageConfig.value = config
      proxy.$refs.quickManageRef.handleOpen(config)
    }
    // 保存成功后刷新自身列表
    function handleManageSuccess() {
      getUserWorkMenus()
    }

    onMounted(() => {
      getUserWorkMenus()
    })

    return { quickData, quickVal, manageConfig, loading, quickItem, handleQuick, handleManageSuccess }
  }
}
</script>

<style lang="scss" scoped>
@import './card-common';

.quick-content {
  width: 100%;

  .quick-list {
    display: flex;
    padding-bottom: 20px;
    .quick-list-item {
      flex: 1;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      .image {
        width: 38px;
        height: 38px;
        display: inline-block;
      }
      .name {
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 13px;
        color: #606266;
        margin-top: 10px;
      }
    }
  }
}
.iconxitong-xitongshezhi-cebian {
  color: #606266;
  cursor: pointer;
  font-size: 20px;
}

// 骨架块尺寸对齐真实的 .image / .name
.sk-image {
  display: block;
  width: 38px;
  height: 38px;
  border-radius: 6px;
  margin: 0 auto;
}
.sk-name {
  display: block;
  width: 48px;
  height: 18px;
  margin: 10px auto 0;
}
</style>
