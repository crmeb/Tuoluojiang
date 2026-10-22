<template>
  <el-card
    body-style="padding:0;"
    style="
      background: linear-gradient(165deg, rgba(255, 255, 255, 0.23) 0%, #ffffff 40%);
      border: 1px solid #ffffff;
    "
  >
    <div class="header-need">
      <div class="header-need-left">
        <span class="name">{{ realName }},{{ greetingText }}!</span>

        <div class="header-need-left-info mt14">
          <img :src="getWeatherIcon()" alt="" />
          <div>
            <div class="top">{{ currentWeather.temperature }}℃</div>
            <div class="bottom">{{ currentDateStr }}</div>
          </div>
        </div>
      </div>
      <div class="header-need-right">
        <div
          class="header-need-right-item"
          v-for="(item, index) in needInfo"
          :key="'needInfo' + index"
          @click="needInfoItem(item)"
        >
          <p class="num">{{ item.num }}</p>
          <p class="text">{{ item.text }}</p>
        </div>
      </div>
    </div>
  </el-card>
</template>

<script>
import moment from 'moment'
import { ref, reactive, onMounted } from 'vue'
import { getWeather, userWorkCountApi } from '@/api/user'
import { getStorageJson } from '@/utils/storage'
import { pageJumpTo } from '@/libs/public'
import weatherQing from '@/assets/images/tianqi/qing.png'
import weatherDuoyun from '@/assets/images/tianqi/duoyun.png'
import weatherXiaoyu from '@/assets/images/tianqi/xiaoyu.png'
import weatherDayu from '@/assets/images/tianqi/dayu.png'
import weatherXue from '@/assets/images/tianqi/xue.png'

export default {
  name: 'HeaderNeedCard',
  setup() {
    const currentDate = new Date()
    const weekDayNames = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六']

    const realName = ref('')
    const greetingText = ref(getGreeting())
    const currentWeather = reactive({
      temperature: 0,
      text: ''
    })
    const currentDateStr = ref(
      moment(currentDate).format('YYYY-MM-DD') + ' ' + weekDayNames[currentDate.getDay()]
    )
    const needInfo = ref([
      {
        text: '待办任务',
        num: 0,
        path: '/user/todo',
        type: -1
      },
      {
        text: '我申请的',
        num: 0,
        path: '/user/examine/mine',
        type: 1,
        // 从工作台进入我的申请时，默认筛选「审核中」
        query: { status: 0 }
      },
      {
        text: '我审批的',
        num: 0,
        path: '/user/examine/approval',
        type: 0
      },
      {
        text: '企业动态',
        num: 0,
        path: '/user/notice/index',
        type: 2
      }
    ])

    // 根据当前时间获取问候语
    function getGreeting() {
      const hour = new Date().getHours()
      if (hour >= 5 && hour < 9) {
        return '早上好'
      } else if (hour >= 9 && hour < 12) {
        return '上午好'
      } else if (hour >= 12 && hour < 14) {
        return '中午好'
      } else if (hour >= 14 && hour < 18) {
        return '下午好'
      } else if (hour >= 18 && hour < 22) {
        return '晚上好'
      } else {
        return '夜深了'
      }
    }
    // 根据天气获取对应的图片
    function getWeatherIcon() {
      const weatherMap = {
        晴: weatherQing,
        多云: weatherDuoyun,
        小雨: weatherXiaoyu,
        大雨: weatherDayu,
        雪: weatherXue
      }
      return weatherMap[currentWeather.text] || weatherDuoyun
    }
    function getCurrentWeather(cityName) {
      return getWeather()
        .then((res) => {
          const now = res.data
          currentWeather.temperature = now.temp
          let str = ''
          if (now.weathercode >= 1) {
            str = '晴'
          } else if (2 <= now.weathercode <= 48) {
            str = '多云'
          } else if (51 <= now.weathercode <= 61) {
            str = '小雨'
          } else if (63 <= now.weathercode <= 67) {
            str = '大雨'
          } else if (71 <= now.weathercode <= 86) {
            str = '雪'
          }
          currentWeather.text = str
        })
        .catch((error) => {
          console.error('天气数据获取失败:', error)
        })
    }
    // 获取顶部四列数据
    async function getUserWorkCount() {
      const result = await userWorkCountApi()
      const data = result.data
      needInfo.value[0].num = data.scheduleCount
      needInfo.value[1].num = data.applyCount
      needInfo.value[2].num = data.approveCount
      if (data.noticeCount > 0) {
        needInfo.value[3].num = data.noticeCount
      } else {
        needInfo.value.splice(3, 1)
      }
    }
    // 顶部页面跳转
    function needInfoItem(item) {
      if (!item.path) return
      pageJumpTo(item.path, item.query || {})
    }

    onMounted(() => {
      const userInfo = getStorageJson('userInfo')
      if (userInfo) {
        realName.value = userInfo.name
      }
      // 天气数据不阻塞工作台首屏加载，与顶部四列统计并行拉取
      Promise.all([getCurrentWeather(), getUserWorkCount()])
    })

    return { realName, greetingText, currentWeather, currentDateStr, needInfo, getWeatherIcon, needInfoItem }
  }
}
</script>

<style lang="scss" scoped>
@import './card-common';

.header-need {
  width: 100%;
  height: 140px;
  display: flex;
  padding: 20px;
  justify-content: space-between;
  align-items: center;
  .header-need-left {
    width: 35%;
    .name {
      font-family: PingFang SC, PingFang SC;
      font-weight: 500;
      font-size: 16px;
      color: #303133;
    }
    .header-need-left-info {
      display: flex;
      height: 64px;
      img {
        display: block;
        width: 64px;
        height: 64px;
      }
      .top {
        font-family: D-DIN-PRO, D-DIN-PRO;
        font-weight: 600;
        font-size: 22px;
        color: #303133;
        margin-bottom: 8px;
        margin-top: 7px;
        margin-left: 12px;
      }
      .bottom {
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 13px;
        color: #909399;
        margin-left: 12px;
      }
    }
  }
  .header-need-right {
    flex: 1;
    display: flex;
    justify-content: space-between;
    .header-need-right-item {
      position: relative;
      float: left;
      cursor: pointer;
      width: 100%;
      text-align: center;
      margin-top: 6px;
      &::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 1px;
        height: 30px;
        background-color: #eeeeee;
      }
      &:last-child::after {
        display: none;
      }
      .num {
        font-family: D-DIN-PRO, D-DIN-PRO;
        font-weight: 600;
        font-size: 32px;
        color: #303133;
      }
      .text {
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 13px;
        color: #606266;
        margin-top: 6px;
      }
    }
    .header-need-right-item:last-of-type {
      border-right: none;
    }
  }
}
</style>
