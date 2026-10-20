<template>
  <span>
    <EmptyPlaceholder style="height: 240px" v-if="widget.widgetList.length <= 0" />
    <smart-widget-grid
      :layout="gridLayout"
      v-else
      :row-height="48"
      :margin="[15, 15]"
      :is-static="false"
      :auto-size="true"
    >
      <template v-for="(item, index) in widget.widgetList" :key="index" #[item.id]>
        <smart-widget
          :padding="getGridPadding(item)"
          :simple="!item.options.showHeader"
          :title="item.options.label"
          :refresh="item.options.showRefresh"
          :fullscreen="item.options.showFullscreen"
          :isActived="designer.selectedWidget && designer.selectedWidget.id == item.id"
          @on-refresh="onRefresh(item)"
          @on-fullscreen="onFullscreen(item)"
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
                :is="item.type + '-widget'"
                :widget="item"
                :designer="designer"
                :key="item.id"
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
                :designer="designer"
                :key="item.id"
                :parent-list="widget.widgetList"
                :index-of-parent-list="index"
                :parent-widget="widget"
                :design-state="true"
              ></component>
            </template>
            <span
              v-if="designer.selectedWidget && designer.selectedWidget.id == item.id"
              class="del-span"
              :class="{ 'in-tab': isTabContainer(item) }"
              @click.stop="removeSelectedChart(index, item)"
            >
              <el-icon class="el-icon-delete" size="20"></el-icon>
            </span>
          </div>
        </smart-widget>
      </template>
    </smart-widget-grid>
  </span>
</template>

<script>
import { traverseWidgetsOfContainer } from '../../utils'
import EmptyPlaceholder from '../components/EmptyPlaceholder.vue'

export default {
  name: 'dashboard-container-widget',
  components: {
    EmptyPlaceholder
  },
  props: {
    widget: Object,
    parentWidget: Object,
    parentList: Array,
    indexOfParentList: Number,
    designer: Object
  },
  data() {
    return {
      defaultSlotName: '0'
    }
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
    isTabContainer(widget) {
      return widget.type === 'tab'
    },
    getGridPadding(widget) {
      if (this.isTabContainer(widget)) {
        return [12, 0]
      }
      return [12, 20]
    },
    onRefresh(item) {
      item.options.isRefresh = !item.options.isRefresh
    },
    onFullscreen(item) {
      setTimeout(() => {
        item.options.isRefresh = !item.options.isRefresh
      }, 200)
    },
    removeSelectedChart(chartIndex, chartId) {
      let delInx
      if (chartId && chartId.type === 'tab') {
        this.designer.tabIsShow = ''
      }
      this.widget.options.layout.forEach((el, inx) => {
        if (el.i == chartId.id) {
          delInx = inx
        }
      })
      this.widget.options.layout.splice(delInx, 1)
      const chartList = this.widget.widgetList
      if (chartList.length > 0) {
        const widgetRefName = this.designer.selectedWidgetName
        const childrenRefNames = []
        const fwHandler = (fw) => {
          childrenRefNames.push(fw.options.name)
        }
        const cwHandler = (cw) => {
          childrenRefNames.push(cw.options.name)
        }
        traverseWidgetsOfContainer(this.designer.selectedWidget, fwHandler, cwHandler)

        let nextSelected = null
        if (chartList.length === 1) {
          //
        } else if (chartList.length === 1 + chartIndex) {
          nextSelected = chartList[chartIndex - 1]
        } else {
          nextSelected = chartList[chartIndex + 1]
        }

        this.$nextTick(() => {
          chartList.splice(chartIndex, 1)
          this.designer.setSelected(nextSelected)

          if (this.designer.formWidget) {
            this.designer.formWidget.deleteWidgetRef(widgetRefName)
            this.designer.formWidget.deletedChildrenRef(childrenRefNames)
          }

          this.designer.emitHistoryChange()
        })
      }
    }
  }
}
</script>

<style scoped lang="scss">
.container-com {
  width: 100%;
  height: 100%;
  position: relative;
  .del-span {
    position: absolute;
    width: 20px;
    height: 20px;
    right: -11px;
    bottom: -2px;
    color: #909399;
    cursor: pointer;

    &.in-tab {
      right: 7px;
    }

    &:hover {
      color: #606266;
    }
  }
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
