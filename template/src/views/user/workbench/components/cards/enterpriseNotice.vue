<template>
  <el-card body-style="padding:0" class="notice-card" style="height: 386px">
    <div class="calendar_title clearfix">
      <div class="pull-left acea-row row-middle">
        <div class="dynamics">企业动态</div>
      </div>
      <div class="pull-right acea-row row-middle">
        <div class="notice-more" @click="handleNoticeMore('')">
          更多
          <i class="el-icon-arrow-right"></i>
        </div>
      </div>
    </div>
    <!-- 骨架：复刻动态条目，撑满固定高度的卡片 -->
    <ul class="news-content el-skeleton is-animated" v-if="loading">
      <li v-for="i in 4" :key="'sk-news' + i">
        <div class="notice-left">
          <el-skeleton-item variant="text" class="sk-title" />
          <el-skeleton-item variant="text" class="sk-meta" />
        </div>
        <div class="notice-right">
          <el-skeleton-item variant="image" class="sk-cover" />
        </div>
      </li>
    </ul>
    <ul class="news-content" v-else-if="nweNoticeData.length > 0">
      <li v-for="item in nweNoticeData.slice(0, 5)" :key="'new' + item.id" @click="handleNoticeMore(item.id)">
        <div class="notice-left" :class="{ width100: !item.cover }">
          <p class="title over-text">{{ item.title }}</p>
          <div class="bottom">
            <span>
              <i class="iconfont iconyiyuedu"></i>
              {{ item.visit }}
            </span>
            <span>
              <i class="iconfont iconriqishijian"></i>
              {{ item.push_time.split(' ')[0] }}
            </span>
          </div>
        </div>
        <div class="notice-right" v-if="item.cover">
          <img v-if="item.cover" class="img" :src="item.cover" alt="" />
        </div>
      </li>
    </ul>
    <default-page v-else :min-height="386" :index="14" />
  </el-card>
</template>

<script>
import { ref, onMounted } from 'vue'
import { noticeListApi } from '@/api/administration'
import { pageJumpTo } from '@/libs/public'

export default {
  name: 'EnterpriseNoticeCard',
  components: {
    defaultPage: () => import('@/components/common/defaultPage')
  },
  setup(_, { emit }) {
    const nweNoticeData = ref([])
    const loading = ref(true)

    // 获取企业动态列表
    async function getNewTableData() {
      try {
        const result = await noticeListApi({ is_new: 1, status: 1 })
        nweNoticeData.value = result.data.list || []
      } finally {
        loading.value = false
        // 上报可见性，供根组件动态分配第三行 span 与 v-show
        emit('news-ready', nweNoticeData.value.length > 0)
      }
    }
    // 跳转到企业动态
    function handleNoticeMore(id = '') {
      const data = id ? { id: id } : {}
      pageJumpTo('/user/notice/index', data)
    }

    onMounted(() => {
      getNewTableData()
    })

    return { nweNoticeData, loading, getNewTableData, handleNoticeMore }
  }
}
</script>

<style lang="scss" scoped>
@import './card-common';

// 企业动态卡片：内容区自适应撑满卡片剩余高度，避免底部大片留白
.notice-card {
  ::v-deep .el-card__body {
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  .news-content {
    flex: 1;
    height: auto;
    min-height: 0;
  }
}
.news-content {
  height: 200px;
  overflow: auto;
  margin: 0;
  padding: 0 20px;
  list-style: none;
  li {
    width: 100%;
    margin-top: 18px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    &:last-of-type {
      padding-bottom: 22px;
    }
    .notice-left {
      width: calc(100% - 110px);
      margin-right: 28px;
      .title {
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 13px;
        color: #303133;
        line-height: 21px;
      }
      .bottom {
        margin-top: 10px;
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 13px;
        color: #999999;
        i {
          font-size: 12px;
        }
        span:last-of-type {
          padding-left: 10px;
        }
      }
    }

    &:first-of-type {
      margin-top: 0;
    }
  }
}
.notice-right {
  width: 78px;
  flex-shrink: 0;
  .img {
    width: 78px;
    height: 52px;
    border-radius: 4px;
    object-fit: cover;
  }
}

// 骨架条目：沿用 li 的间距，尺寸对齐 .title / .bottom / .img
.sk-title {
  display: block;
  width: 100%;
  height: 21px;
}
.sk-meta {
  display: block;
  width: 55%;
  height: 18px;
  margin-top: 10px;
}
.sk-cover {
  width: 78px;
  height: 52px;
  border-radius: 4px;
}
</style>
