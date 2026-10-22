<template>
  <div class="pivot-table-widget"  v-loading="loading" @click.stop="setSelected">
    <div
      class="search-box"
      @dragenter.capture="onTabAreaDragEnter"
      @dragover.capture.prevent="onTabAreaDragOver"
    >
      <el-tabs v-model="field.options.activeValue" class="tab-custom" :class="'tab-style-' + (field.options.tabStyle || 'linear')">
        <template v-for="(item,y) in field.options.tabList">
          <el-tab-pane v-if="item.status === 1" :key="item.value" :label="item.name" :name="item.value">
            <div
              class="pt30"
              @dragenter.capture="onTabPaneDragEnter(item)"
              @dragover.capture.prevent="onTabPaneDragOver(item)"
            >
              <component
                :is="name"
                :widget="item"
                :designer="designer"
                :parent-list="item.widgetList"
                :index-of-parent-list="y"
                :parent-widget="field"
              />
            </div>
          </el-tab-pane>
        </template>
      </el-tabs>
    </div>
  </div>
</template>
<script>
import { queryChartData, } from '@/api/chart'
export default {
  name: 'tab-widget',
  props: {
    field: Object,
    designer: Object,
    layout: Array
  },
  components: {
  },
  data() {
    return {
      cutField: {},
      isNoData: true,
      loading: false,
      numericUnits: '',
      metricsNum: '',
      name:'dashboard-container-widget'
    }
  },
  computed: {
    activeTabLayout() {
      const tab = this.field.options.tabList.find(
        t => t.value === this.field.options.activeValue
      )
      return (tab && tab.options && tab.options.layout) || []
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
    activeTabLayout: {
      handler() {
        this.$nextTick(() => {
          this.syncHeight()
        })
      },
      deep: true,
      immediate: true
    }
  },
  mounted() {
    this.cutField = this.field
    this.initOption()
  },
  methods: {

     getWidgetName(o) {
      return o.type + '-widget'
    },
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
    syncHeight() {
      if (!this.layout || !this.field) return

      const innerLayout = this.activeTabLayout
      if (!innerLayout.length) return

      // 计算内部 grid 中所有 item 的最大底部位置（grid 单位）
      let maxBottom = 0
      innerLayout.forEach(item => {
        maxBottom = Math.max(maxBottom, item.y + item.h)
      })

      // 外层 h = 内部最大底部 + 2（tab 头部 + padding 开销）
      const newH = Math.max(maxBottom + 1.2, 5)

      const layoutItem = this.layout.find(item => item.i === this.field.id)
      if (layoutItem && layoutItem.h !== newH) {
        const oldH = this.toNumber(layoutItem.h, 5)
        this.$set(layoutItem, 'h', newH)
        if (newH > oldH) {
          this.reflowAfterResize(layoutItem)
        }
      }
    },
    toNumber(value, fallback = 0) {
      const num = Number(value)
      return Number.isFinite(num) ? num : fallback
    },
    isOverlapX(a, b) {
      const aX = this.toNumber(a.x, 0)
      const aW = this.toNumber(a.w, 1)
      const bX = this.toNumber(b.x, 0)
      const bW = this.toNumber(b.w, 1)
      return aX < bX + bW && aX + aW > bX
    },
    isOverlapY(a, b) {
      const aY = this.toNumber(a.y, 0)
      const aH = this.toNumber(a.h, 1)
      const bY = this.toNumber(b.y, 0)
      const bH = this.toNumber(b.h, 1)
      return aY < bY + bH && aY + aH > bY
    },
    reflowAfterResize(resizedItem) {
      if (!Array.isArray(this.layout) || !resizedItem) {
        return
      }

      // 只对与当前变高组件发生碰撞的项做链式下推，避免无关项跳动。
      const queue = [resizedItem]
      const visited = new Set()

      while (queue.length) {
        const anchor = queue.shift()
        const anchorId = anchor && anchor.i
        if (!anchorId || visited.has(anchorId)) {
          continue
        }
        visited.add(anchorId)

        const anchorBottom = this.toNumber(anchor.y, 0) + this.toNumber(anchor.h, 1)

        this.layout.forEach(item => {
          if (!item || item.i === anchorId) {
            return
          }

          if (!this.isOverlapX(anchor, item) || !this.isOverlapY(anchor, item)) {
            return
          }

          const nextY = Number(anchorBottom.toFixed(2))
          const itemY = this.toNumber(item.y, 0)
          if (itemY < nextY) {
            this.$set(item, 'y', nextY)
            queue.push(item)
          }
        })
      }
    },
    setSelected() {
      if (this.designer && this.designer.setSelected) {
        this.designer.setSelected(this.field)
      }
    },
    onTabAreaDragEnter() {
      this.updateTabDropTarget(this.field && this.field.options ? this.field.options.activeValue : null)
    },
    onTabAreaDragOver() {
      this.updateTabDropTarget(this.field && this.field.options ? this.field.options.activeValue : null)
    },
    onTabPaneDragEnter(tabPane) {
      this.updateTabDropTarget(tabPane && tabPane.value)
    },
    onTabPaneDragOver(tabPane) {
      this.updateTabDropTarget(tabPane && tabPane.value)
    },
    updateTabDropTarget(paneValue) {
      if (!this.designer || typeof this.designer.setDropTargetContext !== 'function') {
        return
      }
      if (!this.field || !this.field.id) {
        return
      }
      const activePaneValue = this.field.options ? this.field.options.activeValue : null
      this.designer.setDropTargetContext({
        type: 'tab-pane',
        tabId: this.field.id,
        paneValue: paneValue || activePaneValue || null
      })
    }
  }
}
</script>
<style lang="scss" scoped>
.pivot-table-widget {
  width: 100%;
  height: 100%;


::v-deep .el-tabs__item {
  height: 32px;
}
::v-deep .el-tabs__nav-wrap::after {
  width: 0;
}


}

.tab-custom ::v-deep .el-tabs__header.is-top {
  border-bottom: 1px solid #eee;
  margin-inline: 15px;
}

/* 矩形 */
.tab-style-rectangle ::v-deep {
  .el-tabs__active-bar {
    display: none;
  }

  .el-tabs__item {
    width: 88px;
    height: 38px;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;

    display: inline-block;
    text-align: center;
    vertical-align: bottom;
    overflow: hidden;
    text-overflow: ellipsis; //文本溢出显示省略号
    white-space: nowrap; //文本不会换行
    margin-right: 3px;
    font-size: 16px;
    padding: 8px 12px !important;
    line-height: 22px;
    color: #303133;
    background: #F7F7F7;
    border: 1px solid #E7E7E7;
    
    &.is-active {
      border: none;
      color: #fff;
      background: linear-gradient( 180deg, #1890FF 0%, #48CEF3 100%);
    }
  }
}

/* 梯形 */
.tab-style-trapezoid ::v-deep {
  .el-tabs__active-bar {
    display: none;
  }

  .el-tabs__item {
    width: 110px;
    height: 36px;
    position: relative;

    display: inline-block;
    text-align: center;
    vertical-align: bottom;
    overflow: hidden;
    text-overflow: ellipsis; //文本溢出显示省略号
    white-space: nowrap; //文本不会换行
    margin-right: 3px;
    font-size: 16px;
    padding: 8px 12px !important;
    line-height: 22px;
    color: #303133;
    
    &::after {
      content: "";
      position: absolute;
      top: 0;
      bottom: 0;
      left: 3px;
      right: 3px;
      z-index: -1;
      background: #F5F5F5;
      border-radius: 8px 8px 0 0;
      transform: perspective(10px) rotateX(2deg);
    }
    
    &.is-active {
      color: #fff;
      &::after {
        background: linear-gradient( 180deg, #1890FF 0%, #48CEF3 100%);
      }
    }
  }
}

.no-data {
  font-size: 14px;

  .lh {
    color: var(--el-color-primary);
  }
}
</style>
