<template>
  <div class="pivot-table-widget" @click.stop="setSelected" v-loading="loading">
    <div class="search-box"
      v-if="cutField.options && cutField.options.searchList && cutField.options.searchList.length > 0">
      <formList ref="formList" :list="cutField.options.searchList" @handleEmit="handleEmit" @resetSearch="resetSearch">
      </formList>
    </div>
    <div class="no-data" v-else>
      请通过右侧
      <span class="lh">筛选组件设置</span> 来添加数据
    </div>
  </div>
</template>
<script>
import formList from "@/components/common/formList.vue"
import { getDataList } from '@/api/chart.ts'
import { queryChartData } from '../chartQuery.ts'

export default {
  name: 'search-widget',
  props: {
    field: Object,
    designer: Object
  },
  components: {
    formList
  },
  data() {
    return {
      cutField: {},
      isNoData: true,
      loading: false,
      numericUnits: '',
      metricsNum: ''
    }
  },
  watch: {
    field: {
      handler(newVal) {
        this.cutField = this.field
      },
      deep: true,
      immediate: true
    },
    'field.options.searchList': {
      handler(newVal) {
        const list = Array.isArray(newVal) ? newVal : []
        list.forEach((item, index) => {
          this.$watch(
            () => item.option,
            (newValue, oldValue) => {
              // 数组变化也能触发
              this.$refs.formList && this.$refs.formList.setValue('option')
            },
            { deep: true }
          )
        })
      },
      deep: true,
      immediate: true
    }
  },
  mounted() {
    this.cutField = this.field
    this.initOption()
    if (Array.isArray(this.cutField?.options?.searchList) && this.cutField.options.searchList.length > 0) {
      this.$refs.formList.setValue('option')
    }
  },
  methods: {
    async initOption() {
      const { options, type } = this.cutField
      if (!options) {
        this.isNoData = true
        return
      }
      const { metrics = [] } = options.setDimensional || {}
      if (!metrics.length) {
        this.isNoData = true
        return
      }
      this.isNoData = false
      this.numericUnits = metrics[0].numericUnits === '无' ? '' : metrics[0].numericUnits
      await this.getChartData(options, type)
      this.getPreviewNum(metrics[0])
    },
    // 筛选
    async handleEmit(data) {
      // 将 key 中的“.”统一替换成“@”，避免后续路径解析问题
      Object.keys(data || {}).forEach(key => {
        if (key.includes('.')) {
          const newKey = key.replace(/\./g, '@');
          data[newKey] = data[key];
        }
      });
      // 去除 key 值为空的对象
      Object.keys(data).forEach(key => {
        if (data[key] === '' || data[key] == null) {
          delete data[key];
        }
      });
      const promises = []
      let chartId = ''
      const cutField = Array.isArray(this.cutField?.options?.entityIds) ? this.cutField.options.entityIds : []
      let chartData = ''
      if (this.$route.query && this.$route.query.chartId) {
        // 设计 - 只提取必要的核心数据，避免循环引用
        chartData = JSON.stringify({
          widgetList: this.designer.widgetList,
          formConfig: this.designer.formConfig
        })
        chartId = this.$route.query.chartId
      } else {
        // 查看
        chartData = ''
        const path = this.$route.path
        chartId = path.match(/\/(\d+)/)?.[1] || ''
      }
      const pushQueryTask = (widget) => {
        if (!widget || !widget.id || !cutField.includes(widget.id)) {
          return
        }

        if (!widget.options) {
          widget.options = {}
        }
        widget.options.search = data
        widget.options.chartId = chartId

        if (widget.type === 'listTable') {
          promises.push(getDataList(widget.options, chartData))
        } else {
          promises.push(queryChartData(widget.options, widget.type, chartData))
        }
      }

      const rootWidgets = Array.isArray(this.designer?.widgetList) ? this.designer.widgetList : []
      rootWidgets.forEach(item => {
        pushQueryTask(item)

        const childWidgets = Array.isArray(item?.widgetList) ? item.widgetList : []
        childWidgets.forEach(widget => {
          pushQueryTask(widget)

          if (widget.type === 'tab') {
            const tabList = Array.isArray(widget?.options?.tabList) ? widget.options.tabList : []
            tabList.forEach(tab => {
              const tabWidgets = Array.isArray(tab?.widgetList) ? tab.widgetList : []
              tabWidgets.forEach(el => {
                pushQueryTask(el)
              })
            })
          }
        })
      })
      // 并发执行所有请求
      await Promise.all(promises)
    },
    async resetSearch() {
      // const promises = []
      // this.designer.widgetList.forEach(item => {
      //   item.widgetList.forEach(widget => {
      //     if (this.field.options.entityIds.includes(widget.id)) {
      //       widget.options.search = {}
      //       // 收集异步请求
      //       promises.push(queryChartData(widget.options, widget.type))
      //     }
      //   })
      // })
      // // 并发执行所有请求
      // await Promise.all(promises)
    },
    async getChartData(options, type) {
      this.loading = true
      let res = await queryChartData(options, type)
      if (res && res.status === 200) {
        this.metricsNum = res.data.value || 0
      }
      this.loading = false
    },
    getPreviewNum(conf) {
      let { thousandsSeparator, showDecimalPlaces, decimalPlaces } = conf
      if (showDecimalPlaces) {
        this.metricsNum = Number(this.metricsNum).toFixed(decimalPlaces)
      }
      if (thousandsSeparator) {
        this.metricsNum = this.numberToCurrencyNo(this.metricsNum)
      }
    },
    numberToCurrencyNo(value) {
      if (!value) return 0
      const intPart = Math.trunc(value)
      const intPartFormat = intPart.toString().replace(/(\d)(?=(?:\d{3})+$)/g, '$1,')
      let floatPart = ''
      const valueArray = value.toString().split('.')
      if (valueArray.length === 2) {
        floatPart = valueArray[1].toString()
        return intPartFormat + '.' + floatPart
      }
      return intPartFormat + floatPart
    },
    setSelected() {
      if (this.designer && this.designer.setSelected) {
        this.designer.setSelected(this.field)
      }

      // localStorage.setItem("widget__list__selected", JSON.stringify(this.field));
    }
  }
}
</script>
<style lang="scss" scoped>
.pivot-table-widget {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;


  .search-box {
    height: 33px;
    margin: auto 0;
  }

}

.no-data {
  font-size: 14px;

  .lh {
    color: var(--el-color-primary);
  }
}
</style>
