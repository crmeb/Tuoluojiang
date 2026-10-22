<template>
  <el-card body-style="padding:0">
    <div class="header-pic">
      <div class="header-pic-content">
        <img class="image" :src="personalIcon" alt="" />
        <div class="text">
          <div class="over-text1">欢迎登录陀螺匠</div>
          <div class="info over-text2">{{ enterprise.culture || '--' }}</div>
        </div>
      </div>
    </div>
  </el-card>
</template>

<script>
import { ref, onMounted } from 'vue'
import { getStorageJson } from '@/utils/storage'

import personalIcon from '@/assets/images/personal-icon.png'

export default {
  name: 'HeaderPicCard',
  setup() {
    const enterprise = ref({})

    onMounted(() => {
      enterprise.value = getStorageJson('enterprise')
    })

    return { personalIcon, enterprise }
  }
}
</script>

<style lang="scss" scoped>
@import './card-common';

.header-pic {
  width: 100%;
  height: 140px;
  padding: 16px;

  .header-pic-content {
    width: 100%;
    height: 100%;
    position: relative;
    background-image: url('../../../../../assets/images/personal-logo.png');
    background-repeat: no-repeat;
    background-position: top center;
    background-size: 100% 100%;
  }
  .image {
    position: absolute;
    top: 15px;
    left: 24px;
    width: 96px;
    height: 69px;
    object-fit: cover;
  }
  .text {
    position: absolute;
    top: 33px;
    left: 141px;

    padding-right: 14px;
    font-family: PingFang SC, PingFang SC;
    font-weight: 500;
    font-size: 16px;
    color: #303133;
    .time {
      margin-left: 6px;
    }
    .info {
      font-size: 12px;
      margin-top: 10px;
      color: #909399;
      line-height: 1.5;
    }
  }
}
</style>
