<!-- 自动拉群-渠道码数据分析（独立路由，渠道码 ID 由 query.id 驱动） -->
<template>
  <BaseContainer class="group-code-statistics none-padding none-bg">
    <!-- 顶部：返回 + 标题 -->
    <FormPageHeader title="渠道码数据分析" class="page-header" />

    <div v-loading="loading" class="group-code-statistics__body">
      <!-- 筛选：渠道码名称 + 统计日期范围 -->
      <div class="common-card">
        <div class="filter-bar">
          <el-select v-model="channelCodeId" filterable remote reserve-keyword :remote-method="remoteSearchCode"
            :loading="codeLoading" size="small" placeholder="请输入渠道码名称搜索" class="filter-bar__code"
            @change="handleCodeChange">
            <el-option v-for="item in codeOptions" :key="item.id" :label="item.name" :value="item.id" />
          </el-select>
          <el-date-picker v-model="dateRange" type="daterange" size="small" value-format="yyyy-MM-dd"
            range-separator="至" start-placeholder="开始时间" end-placeholder="结束时间" :picker-options="rangePickerOptions"
            @change="handleDateChange" class="filter-bar__time" />
          <el-tooltip content="重置搜索条件" effect="dark" placement="top">
            <div class="reset" @click="resetSearch"><i class="iconfont iconqingchu"></i></div>
          </el-tooltip>
        </div>

        <!-- 数据指标卡 -->
        <div class="metric-cards">
          <div v-for="item in metrics" :key="item.label" class="metric-card">
            <span class="metric-card__value">{{ item.value }}</span>
            <span class="metric-card__label">{{ item.label }}</span>
          </div>
        </div>
      </div>

      <!-- 统计图表 -->
      <el-row :gutter="14" class="chart-row">
        <el-col :span="12">
          <div class="chart-panel common-card">
            <div class="chart-panel__title">客户加客服数统计</div>
            <div class="chart-panel__body">
              <echartBox v-if="hasCustomerService" :option-data="customerServiceOption" :styles="chartStyle" />
              <el-empty v-else description="暂无数据" :image-size="90" />
            </div>
          </div>
        </el-col>
        <el-col :span="12">
          <div class="chart-panel common-card">
            <div class="chart-panel__title">客户加群数统计</div>
            <div class="chart-panel__body">
              <echartBox v-if="hasGroupChat" :option-data="groupChatOption" :styles="chartStyle" />
              <el-empty v-else description="暂无数据" :image-size="90" />
            </div>
          </div>
        </el-col>
      </el-row>
    </div>
  </BaseContainer>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router/composables'
import BaseContainer from '@/components/BaseContainer/index.vue'
import FormPageHeader from '@/components/FormPageHeader/index.vue'
import moment from 'moment'
import echartBox from '@/components/common/echarts.vue'
import pickerOptions from '@/libs/pickerOptions'
import { getChannelCodeApi, getChannelCodeStatisticsApi } from '@/api/weCom'

// 一年的毫秒数，用于限制日期范围最多可选一年
const ONE_YEAR = 3600 * 1000 * 24 * 365

// 将 Date 格式化为 yyyy-MM-dd，与 el-date-picker 的 value-format 保持一致
const formatDate = (date) => {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

// 默认统计范围：最近 30 天
const getDefaultRange = () => {
  const end = new Date()
  const start = new Date()
  start.setTime(start.getTime() - 3600 * 1000 * 24 * 30)
  return [formatDate(start), formatDate(end)]
}

// 十六进制色值转 rgba，用于折线面积渐变
const hexToRgba = (hex, alpha) => {
  const val = hex.replace('#', '')
  const r = parseInt(val.slice(0, 2), 16)
  const g = parseInt(val.slice(2, 4), 16)
  const b = parseInt(val.slice(4, 6), 16)
  return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

export default {
  name: 'GroupCodeStatistics',
  components: {
    BaseContainer,
    FormPageHeader,
    echartBox
  },
  setup() {
    const route = useRoute()

    const loading = ref(false)
    const channelCodeId = ref(route.query.id ? Number(route.query.id) : '')
    // 默认选中最近 30 天
    const dateRange = ref(getDefaultRange())
    const codeOptions = ref([])
    const codeLoading = ref(false)
    // 当前选中渠道码（远程搜索会替换选项列表，用它兜底回显名称）
    const currentCode = ref(
      route.query.id ? { id: Number(route.query.id), name: route.query.name || '' } : { id: '', name: '' }
    )
    // 进入页面时的初始渠道码，重置搜索条件时据此还原
    const initialCode = { ...currentCode.value }

    // 指标卡数据
    const summary = reactive({
      add_customer_count: 0,
      loss_customer_count: 0,
      join_customer_count: 0,
      quit_group_customer_count: 0
    })
    const metrics = computed(() => [
      { label: '添加客户数(人)', value: summary.add_customer_count },
      { label: '流失客户数(人)', value: summary.loss_customer_count },
      { label: '加群客户数(人)', value: summary.join_customer_count },
      { label: '退群客户数(人)', value: summary.quit_group_customer_count }
    ])

    // 用户在日历上点选的第一个日期，用于把可选范围限制在其前后一年内
    const pickedMinTime = ref(null)
    // 日期范围选择器配置：引入快捷选项，并限制最多可选一年区间
    const rangePickerOptions = {
      shortcuts: pickerOptions.shortcuts,
      onPick: ({ minDate, maxDate }) => {
        // 只选中起始日期（尚未选终止日期）时记录基准，用于 disabledDate 计算
        pickedMinTime.value = minDate && !maxDate ? minDate.getTime() : null
      },
      disabledDate: (time) => {
        if (pickedMinTime.value === null) return false
        return Math.abs(time.getTime() - pickedMinTime.value) > ONE_YEAR
      }
    }

    const chartStyle = { width: '100%', height: '100%' }
    const customerServiceOption = ref({})
    const groupChatOption = ref({})
    const hasCustomerService = ref(false)
    const hasGroupChat = ref(false)

    // 折线图（含面积渐变），数据与配置分离
    const buildLineOption = (chart, colors) => {
      const xAxis = (chart && chart.xAxis) || []
      const series = (chart && chart.series) || []
      return {
        color: colors,
        tooltip: {
          trigger: 'axis',
          axisPointer: { type: 'line', lineStyle: { color: '#DCDFE6' } }
        },
        legend: {
          show: true,
          top: 0,
          right: 0,
          icon: 'circle',
          itemWidth: 8,
          itemHeight: 8,
          itemGap: 24,
          textStyle: { color: '#303133', fontSize: 12 }
        },
        grid: { left: 8, right: 16, top: 44, bottom: 8, containLabel: true },
        xAxis: {
          type: 'category',
          boundaryGap: false,
          data: xAxis,
          axisLine: { lineStyle: { color: '#E4E7ED' } },
          axisTick: { show: false },
          axisLabel: { color: '#909399' }
        },
        yAxis: {
          type: 'value',
          name: '(人)',
          minInterval: 1,
          nameTextStyle: { color: '#909399', padding: [0, 0, 0, -28] },
          axisLine: { show: false },
          axisTick: { show: false },
          axisLabel: { color: '#909399' },
          splitLine: { lineStyle: { type: 'dashed', color: '#EBEEF5' } }
        },
        series: series.map((item, index) => {
          const color = colors[index % colors.length]
          return {
            name: item.name,
            type: 'line',
            smooth: true,
            symbol: 'circle',
            symbolSize: 6,
            showSymbol: false,
            lineStyle: { width: 2, color },
            itemStyle: { color },
            areaStyle: {
              color: {
                type: 'linear',
                x: 0,
                y: 0,
                x2: 0,
                y2: 1,
                colorStops: [
                  { offset: 0, color: hexToRgba(color, 0.18) },
                  { offset: 1, color: hexToRgba(color, 0) }
                ]
              }
            },
            data: item.data || []
          }
        })
      }
    }

    // 保证当前选中项始终在选项中，避免远程搜索后回显丢失
    const buildCodeOptions = (list) => {
      const options = Array.isArray(list) ? [...list] : []
      const cur = currentCode.value
      if (cur.id && !options.some((item) => item.id === cur.id)) {
        options.unshift({ ...cur })
      }
      codeOptions.value = options
    }

    // 渠道码下拉：默认 10 条，支持按名称关键字远程搜索
    const remoteSearchCode = async (keyword = '') => {
      codeLoading.value = true
      try {
        const res = await getChannelCodeApi({ page: 1, limit: 10, types: '0', name: (keyword || '').trim() })
        buildCodeOptions((res && res.data && res.data.list) || [])
      } finally {
        codeLoading.value = false
      }
    }

    // 切换渠道码：记录选中项用于回显兜底，并刷新统计
    const handleCodeChange = () => {
      const found = codeOptions.value.find((item) => item.id === channelCodeId.value)
      if (found) currentCode.value = { id: found.id, name: found.name }
      getData()
    }

    // 重置搜索条件：渠道码还原为进入页面时的选择，日期恢复为默认最近 30 天后重新查询
    const resetSearch = () => {
      channelCodeId.value = initialCode.id
      currentCode.value = { ...initialCode }
      dateRange.value = getDefaultRange()
      remoteSearchCode()
      getData()
    }

    const getData = async () => {
      if (!channelCodeId.value) return
      loading.value = true
      try {
        const params = {}
        if (dateRange.value && dateRange.value.length === 2) {
          params.time = `${dateRange.value[0]} - ${dateRange.value[1]}`
        }
        const res = await getChannelCodeStatisticsApi(channelCodeId.value, params)
        const data = (res && res.data) || {}

        summary.add_customer_count = data.add_customer_count || 0
        summary.loss_customer_count = data.loss_customer_count || 0
        summary.join_customer_count = data.join_customer_count || 0
        summary.quit_group_customer_count = data.quit_group_customer_count || 0

        const customerService = data.customer_service || {}
        const groupChat = data.group_chat || {}
        hasCustomerService.value = !!(customerService.xAxis && customerService.xAxis.length)
        hasGroupChat.value = !!(groupChat.xAxis && groupChat.xAxis.length)
        customerServiceOption.value = buildLineOption(customerService, ['#1890FF', '#4BCAD5'])
        groupChatOption.value = buildLineOption(groupChat, ['#1890FF', '#4BCAD5'])
      } finally {
        loading.value = false
      }
    }

    // 快捷选项（本年/去年/上月）emit 的是带时分秒的斜杠字符串，value-format 不会对其二次格式化，
    // 会导致 v-model 存入非 yyyy-MM-dd 值而无法回显。这里统一归一化为 yyyy-MM-dd，保证回显与请求一致。
    const handleDateChange = (val) => {
      // 显式传入可能的来源格式，避免斜杠字符串触发 moment 的非 ISO 弃用告警
      const acceptFormats = ['YYYY-MM-DD', 'YYYY/MM/DD HH:mm:ss', 'YYYY/MM/DD']
      dateRange.value = Array.isArray(val) && val.length === 2
        ? val.map((d) => moment(d, acceptFormats).format('YYYY-MM-DD'))
        : []
      getData()
    }

    onMounted(() => {
      remoteSearchCode()
      getData()
    })

    return {
      loading,
      channelCodeId,
      dateRange,
      codeOptions,
      codeLoading,
      metrics,
      rangePickerOptions,
      chartStyle,
      customerServiceOption,
      groupChatOption,
      hasCustomerService,
      hasGroupChat,
      remoteSearchCode,
      handleCodeChange,
      resetSearch,
      handleDateChange,
      getData
    }
  }
}
</script>

<style lang="scss" scoped>
.group-code-statistics {
  gap: 14px;

  &__body {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden auto;
  }
}

.page-header {
  height: 65px;
  border-bottom: none;
  background: linear-gradient(to right, rgba(255, 255, 255, 0.23) 0%, #FFFFFF 10%);
  border-radius: 8px;
}

.common-card {
  background-color: #fff;
  border-radius: 8px;
  padding: 20px;
}

.filter-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;

  &__code {
    width: 160px;
  }

  &__time {
    width: 250px;
  }
}

.metric-cards {
  display: flex;
  gap: 14px;
  margin-bottom: 14px;
}

.metric-card {
  flex: 1;
  height: 102px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #f7f8fa;
  border-radius: 6px;

  &__value {
    font-size: 24px;
    font-weight: 600;
    line-height: 1;
    color: #303133;
    font-family: 'D-DIN-PRO', 'PingFang SC', sans-serif;
  }

  &__label {
    font-size: 13px;
    color: #606266;
  }
}

.chart-row {
  display: flex;
  flex: 1;
  min-height: 320px; // 兜底最小高度，保证小屏/低缩放下图表不被压扁
  margin-top: 14px;
}

.chart-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 16px 20px 20px;
  border: 1px solid #ebeef5;
  border-radius: 8px;

  &__title {
    padding-left: 10px;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    line-height: 20px;
    color: #303133;
    border-left: 2px solid #1890ff;
  }

  // 图表区占满标题以下的剩余高度，echartBox 内层 100% 才有可依据的父高
  &__body {
    flex: 1;
    min-height: 0;

    ::v-deep > div {
      height: 100%;
    }
  }

  ::v-deep .el-empty {
    padding: 40px 0;
  }
}
</style>
