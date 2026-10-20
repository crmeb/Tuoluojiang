<template>
  <smart-widget-grid
    :layout="gridLayout"
    v-if="widget.widgetList.length > 0"
    :row-height="48"
    :margin="[15, 15]"
    :is-static="true"
    :auto-size="true"
  >
    <template v-for="(item, index) in widget.widgetList" :key="index" #[item.id]>
      <smart-widget
        :class="{ 'dashboard-first-chart': isFirstChart(item) }"
        :simple="!item.options.showHeader"
        :title="item.options.label"
        :refresh="item.options.showRefresh"
        :collapse="item.options.showCollapse"
        :fullscreen="item.options.showFullscreen"
        @on-refresh="onRefresh(item)"
      >
        <template #title>
          <div class="title-box">
            <span>{{ item.options.label }}</span>
            <el-popover placement="top-start" width="200" trigger="hover">
              <div>{{ item.options.tips || '设置文字提示内容' }}</div>

              <template #reference>
                <span v-show="item.options.isShowTips" class="el-icon-warning-outline"></span>
              </template>
            </el-popover>
          </div>
        </template>
        <div class="container-com">
          <template v-if="'container' === item.category">
            <component
              :layout="gridLayout"
              :is="item.type + '-item'"
              :widget="item"
              :key="item.id"
              :designer="designer"
              :parent-list="widget.widgetList"
              :index-of-parent-list="index"
              :parent-widget="widget"
            ></component>
          </template>
          <template v-else>
            <component
              :layout="gridLayout"
              :is="item.type + '-widget'"
              :field="item"
              :key="item.id"
              :designer="designer"
              :parent-list="widget.widgetList"
              :index-of-parent-list="index"
              :parent-widget="widget"
              :design-state="true"
            ></component>
          </template>
        </div>
      </smart-widget>
    </template>
  </smart-widget-grid>
</template>

<script>
export default {
  name: 'dashboard-container-item',
  inject: {
    firstChartWidgetId: { default: () => null }
  },
  props: {
    widget: Object,
    parentWidget: Object,
    parentList: Array,
    indexOfParentList: Number,
    designer: Object
  },
  data() {
    return {}
  },
  computed: {
    gridLayout() {
      if (this.widget.widgetList.length <= 0) {
        return [{ x: 0, y: 0, w: 4, h: 4, i: '0' }]
      } else {
        return this.widget.options.layout
      }
    }
  },
  methods: {
    isFirstChart(item) {
      const chartId =
        typeof this.firstChartWidgetId === 'function' ? this.firstChartWidgetId() : this.firstChartWidgetId
      return !!chartId && item.id === chartId
    },
    onRefresh(item) {
      item.options.isRefresh = !item.options.isRefresh
    }
  }
}
</script>

<style scoped lang="scss">
.container-com {
  width: 100%;
  height: 100%;
}
.title-box {
  padding: 0 20px;
  .el-icon-warning-outline {
    cursor: pointer;
    margin-left: 5px;
    color: #606266;
  }
}
</style>
