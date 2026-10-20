---
name: create-echarts-dashboard
description: 新建统计页/数据看板，复用项目通用图表组件 @/components/common/echarts（echartBox），按 optionData + styles 约定渲染折线/柱/饼/漏斗等，配合 el-card 栅格与 count-to 数字卡。当用户说"加图表/做统计页/加个看板/数据总览/加饼图柱图"时触发。
---

# create-echarts-dashboard

为本项目（Vue 3 + Element Plus + echarts）生成业务统计页 / 数据看板。

## 用哪个图表组件（关键，别选错）

| 组件 | 路径 | 用途 |
|---|---|---|
| ✅ `echartBox`（首选） | `@/components/common/echarts` | **通用业务统计页**。props 简单：`option-data` + `styles`，内部自管 `init/setOption/resize/dispose` |
| ❌ `scEcharts` | `@/components/scEcharts` | **仅供报表设计器**用，依赖 `inject(['previewState','getScreenWidth'])` 与 EventBus，脱离设计器上下文会报错，**不要**在普通统计页用 |

真实参考页：`src/views/customer/turnover/index.vue`、`src/views/administration/material/chart/index.vue`。

## echartBox 用法约定（见 `src/components/common/echarts.vue`）

- props：
  - `:option-data` —— 标准 ECharts option 对象；组件 `deep watch`，数据变了图自动重绘（`setOption(option, true)`）。
  - `:styles` —— 容器样式对象，**必须给高度**，如 `{ width: '100%', height: '300px' }`，否则图表不显示。
  - `:type` —— 可选，传 `'fd'` 时关闭图例联动逻辑。
- 事件：
  - `@pieChange` —— 点击图元（含饼图）回调，参数是 `param.data`，用于下钻/联动筛选。
- 组件已处理 `window resize` 自适应与 `beforeUnmount` 销毁，**页面层不要再手写** `echarts.init` / `addEventListener('resize')`。

## 页面骨架（`<script setup lang="ts">`）

```vue
<template>
  <div class="divBox">
    <!-- 顶部筛选（时间/部门等），可复用业务里的 statisticsBox 或自建 oaFromBox -->
    <el-card><statisticsBox ref="formBoxRef" @confirmData="confirmData" /></el-card>

    <el-row :gutter="14" class="mt14">
      <el-col :span="12">
        <el-card>
          <div class="statistics-title">资产金额</div>
          <echartBox :option-data="amountOption" :styles="chartStyle" />
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card>
          <div class="statistics-title">分布占比</div>
          <echartBox :option-data="pieOption" :styles="chartStyle" @pieChange="onPieChange" />
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>


<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import echartBox from '@/components/common/echarts'
import statisticsBox from '@/views/customer/components/statisticsBox' // 按实际筛选组件调整
import { xxxStatApi } from '@/api/<scope>'

interface StatItem {
  name: string
  value: number
}

const chartStyle = { width: '100%', height: '300px' }
const where = reactive<Record<string, any>>({})
const amountOption = ref<Record<string, any>>({})
const pieOption = ref<Record<string, any>>({})
const formBoxRef = ref<any>(null)

function buildBarOption(list: StatItem[] = []) {
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: 40, right: 16, top: 30, bottom: 30 },
    xAxis: { type: 'category', data: list.map((i) => i.name) },
    yAxis: { type: 'value' },
    series: [{ type: 'bar', data: list.map((i) => i.value) }]
  }
}

function buildPieOption(list: StatItem[] = []) {
  return {
    tooltip: { trigger: 'item' },
    legend: { bottom: 0 },
    series: [{ type: 'pie', radius: ['40%', '70%'], data: list.map((i) => ({ name: i.name, value: i.value })) }]
  }
}

async function getData() {
  const res = await xxxStatApi(where)
  amountOption.value = buildBarOption(res.data.amount)
  pieOption.value = buildPieOption(res.data.dist)
}

function confirmData(val: Record<string, any>) {
  Object.assign(where, val)
  getData()
}

function onPieChange(data: StatItem) { /* 下钻/联动筛选 */ }

onMounted(getData)
</script>
```

## 约定要点

1. **空数据态**：图表无数据时显示空状态，复用项目最近新增的空状态组件（`feat(admin-empty-state)`），不要画一张空白图。给图表外层包 `v-if="list.length"`，否则渲染空状态。
2. **栅格**：多图用 `el-row :gutter` + `el-col`，外层 `el-card`，标题用 `.statistics-title`，与现有统计页一致。
3. **数字指标卡**：纯数字指标用 `count-to`（项目已用，见 material/chart）做滚动动画，不必上图表。
4. **option 拆函数**：每类图一个 `buildXxxOption(data)` 纯函数，数据与配置分离，便于联动刷新。
5. **API**：统计接口走 `@/api/<scope>`（参考 `create-api-module`），不在页面直接 `axios`。
6. **筛选刷新**：筛选条件变化 `Object.assign(where, val)` 后调 `getData()`，让 `optionData` 引用更新触发 deep watch 重绘。

## 不要做的事

- 不要在普通统计页引入 `scEcharts`（会因缺少 inject provider 报错）。
- 不要在页面里自己 `echarts.init` / 手动监听 resize / 手动 dispose——`echartBox` 已封装。
- 不要忘记给 `:styles` 设高度，否则图表区域塌陷不显示。
- 不要把后端原始返回直接塞进 `series.data`，先用 `buildXxxOption` 整形。
