/**
 * 看板图表数据查询：设计器配置 → 接口查询参数
 */
import type { Id } from '@/types/api'
import { queryChartDataApi } from '@/api/chart'
import { getStorageJson } from '@/utils/storage'

const ChartTypes: Record<string, string> = {
  // 统计数值
  statistic: 'statistic_numeric',
  // 进度条
  progressbar: 'progress_bar',
  // 柱状图
  barChart: 'bar_chart',
  // 条形图
  barXChart: 'column_chart',
  // 折线图
  lineChart: 'line_chart',
  // 漏斗图
  funnelChart: 'funnel_plot',
  // 漏斗图
  pieChart: 'pie_chart',
  // 雷达图
  radarChart: 'radar_chart',
  // 数据列表
  listTable: 'table',
  // 透视表
  pivotTable: 'table'
}

/** 看板维度/指标字段（设计器里的配置项） */
export interface ChartFieldConfig {
  field_name_en?: string
  alias?: string
  sort?: unknown
  value?: unknown
  calcMode?: string
}

interface ChartQueryField {
  fieldNameEn?: string
  fieldName?: string
  sort?: unknown
  value?: unknown
  operator?: string
  columns?: boolean
}

/** 看板过滤条件项 */
export interface ChartFilterItem {
  value?: string
  field?: string
  option?: any
  category?: number
  type?: string
  min?: unknown
  max?: unknown
  obj?: unknown
  options: {
    userList: Array<{ value: unknown }>
    depList: Array<{ id: unknown }>
  }
}

interface ChartFilterQuery {
  operator?: string
  form_field?: string
  value: unknown
  obj: ChartFilterItem
}

/** 图表设计器的表单模型 */
export interface ChartFormModel {
  name?: string
  chartId?: Id
  dataEntity: string
  search?: object
  setDimensional?: {
    dimension?: ChartFieldConfig[]
    metrics?: ChartFieldConfig[]
    dimensionRow?: ChartFieldConfig[]
    dimensionCol?: ChartFieldConfig[]
  }
  setChartConf: { useAllData?: boolean }
  setChartFilter: {
    list?: Array<ChartFilterItem | ChartFilterQuery>
    additional_search_boolean?: string
  }
}

const formatItem = (list: ChartFieldConfig[], target: string) => {
  let items = list.map((el) => {
    let newItem: ChartQueryField = {
      fieldNameEn: el.field_name_en,
      fieldName: el.alias,
      sort: el.sort,
      value: el.value
    }
    if (target == 'longitude') {
      newItem.operator = el.calcMode
      // newItem.axisFormat = {
      //   thousandsSeparator: el.thousandsSeparator,
      //   decimalPlaces: el.showDecimalPlaces ? el.decimalPlaces : 0,
      //   numericUnits: el.showNumericUnits && el.numericUnits != '无' ? el.numericUnits : ''
      // }
    }
    return { ...newItem }
  })
  return items
}

/**
 * 把设计器表单模型组装成图表查询参数。
 * 注意：设计器原始的过滤条件会被原地转换为查询结构并写回 formModel.setChartFilter.list，
 * 后续再次查询时首项已带 obj，不会重复转换。
 */
export function buildChartQueryParams(formModel: ChartFormModel, type: string, chartData?: unknown) {
  const allEntityName: Record<string, string> = getStorageJson('allEntityName', {})
  let latitude = formatItem((formModel.setDimensional && formModel.setDimensional.dimension) || [], 'latitude')
  // 指标
  let longitude = formatItem((formModel.setDimensional && formModel.setDimensional.metrics) || [], 'longitude')

  if (type == 'pivotTable') {
    latitude = formatItem((formModel.setDimensional && formModel.setDimensional.dimensionRow) || [], 'latitude')
    let dimensionCol = formatItem((formModel.setDimensional && formModel.setDimensional.dimensionCol) || [], 'latitude')
    dimensionCol.forEach((el) => {
      el.columns = true
      latitude.push(el)
    })
  }

  let listArr: ChartFilterQuery[] = []
  if (formModel.setChartFilter.list && formModel.setChartFilter.list.length > 0&&!formModel.setChartFilter.list[0].obj) {
 
    // 首项没有 obj 说明还是设计器原始配置，需要转换成查询结构
    ;(formModel.setChartFilter.list as ChartFilterItem[]).map((item) => {
      let obj: ChartFilterQuery = {
        operator: item.value,
        form_field: item.field,
        value: item.option,
        obj: item
      }
      if (!item.option && item.category === 2) {
        const values: unknown[] = []
        item.options.userList.map((i) => {
          values.push(i.value)
        })
        obj.value = values
      } else if (!item.option && item.category === 1) {
        const values: unknown[] = []
        item.options.depList.map((i) => {
          values.push(i.id)
        })
        obj.value = values
      }
      if (obj.operator === 'between') {
        if (item.type == 'date_time_picker' || item.type == 'date_picker') {
          let data = item.option[0] + '-' + item.option[1]
          obj.value = data
        } else {
          let data = {
            min: item.min,
            max: item.max
          }
          obj.value = data
        }
      
      }
      listArr.push(obj)
    })
    formModel.setChartFilter.list = listArr
  }
   
  let param = {
    uniqued: formModel.name, // 唯一标识
    chart_id: formModel.chartId, // 图表id
    type: ChartTypes[type], // 图表类型
    tableNameEn: allEntityName[formModel.dataEntity] || '', // 表名
    dimensionList: latitude, // 维度
    indicatorList: longitude, // 指标
    chartData: chartData || '', // 图表数据
    search: formModel.search || {}, // 筛选条件
    noPrivileges: formModel.setDimensional && formModel.setChartConf.useAllData, // 是否使用全部数据
    additionalSearch: formModel.setChartFilter.list || [], // 过滤条件
    additionalSearchBoolean: formModel.setChartFilter.additional_search_boolean || '' // 布尔过滤条件
  }

  return param
}

/** 按设计器配置查询图表数据 */
export function queryChartData(formModel: ChartFormModel, type: string, chartData?: unknown) {
  return queryChartDataApi(buildChartQueryParams(formModel, type, chartData))
}
