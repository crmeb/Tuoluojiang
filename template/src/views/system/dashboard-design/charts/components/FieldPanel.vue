<template>
  <!-- 左侧字段面板 -->
  <div class="main" :style="{ height: scrollerHeight }">
    <div class="title mb10">图表组件</div>
    <draggable
      class="chart-container-widget-list"
      :list="designer.chartWidgets"
      item-key="key"
      :group="{ name: 'dragGroup', pull: 'clone', put: false }"
      :clone="handleContainerWidgetClone"
      ghost-class="ghost"
      :sort="false"
      :disabled="false"
      :move="checkContainerMove"
      @end="onContainerDragEnd"
    >
      <div
        v-for="element in designer.chartWidgets"
        :key="element.key"
        class="chart-container-widget-item"
        :title="element.displayName"
        @click="addChartContainerByDbClick(element)"
      >
        <svg-icon :icon-class="element.icon" class-name="color-svg-icon" />
        <span>{{ getWidgetLabel(element) }} </span>
      </div>
    </draggable>


    <!-- framework widgets -->
    <div class="title mt10 mb10 ">框架组件</div>
    <draggable
      class="chart-container-widget-list"
      :list="designer.chartContainers"
      item-key="key"
      :group="{ name: 'dragGroup', pull: 'clone', put: false }"
      :clone="handleContainerWidgetClone"
      ghost-class="ghost"
      :sort="false"
      :disabled="false"
      :move="checkContainerMove"
      @end="onContainerDragEnd"
    >
      <div
        v-for="element in designer.chartContainers"
        :key="element.key"
        v-show="designer.tabIsShow !== element.type"
        class="chart-container-widget-item"
        :title="element.displayName"
        @click="addChartContainerByDbClick(element)"
      >
        <svg-icon :icon-class="element.icon" class-name="color-svg-icon" />
        <span>{{ element.options.label }}</span>
      </div>
    </draggable>
  </div>
</template>

<script>
import draggable from 'vuedraggable'
import i18n from '@/utils/i18n'
import axios from 'axios'
import SvgIcon from '@/components/svg-icon-nc'
import { ext_charts_widgets, dashboard_container_schema } from '../charts-schema.js'
import { ext_chart_containers as EC_CONS, ext_charts_widgets as EC_WS } from '../charts-schema.js'

import { generateId, addWindowResizeHandler, deepClone } from '@/utils/formDesignerUtils'
import {
  containers,
  advancedFields,
  basicFields,
  customFields
} from '@/components/form-designer/widget-panel/widgetsConfig.js'

var Ze = Object.defineProperty,
  Ge = Object.defineProperties
var Xe = Object.getOwnPropertyDescriptors
var Qe = Object.getOwnPropertySymbols
var Ke = Object.prototype.hasOwnProperty,
  Je = Object.prototype.propertyIsEnumerable
var ze = (o, e, n) =>
    e in o
      ? Ze(o, e, {
          enumerable: !0,
          configurable: !0,
          writable: !0,
          value: n
        })
      : (o[e] = n),
  Ie = (o, e) => {
    for (var n in e || (e = {})) Ke.call(e, n) && ze(o, n, e[n])
    if (Qe) for (var n of Qe(e)) Je.call(e, n) && ze(o, n, e[n])
    return o
  },
  We = (o, e) => Ge(o, Xe(e))
var je = (o, e, n) => (ze(o, typeof e != 'symbol' ? e + '' : e, n), n)
export default {
  name: 'FieldPanel',
  mixins: [i18n],
  components: { SvgIcon, draggable },
  props: { designer: Object },
  inject: ['getBannedWidgets', 'getDesignerConfig'],
  data() {
    return {
      chartContainers: ext_charts_widgets,
      designerConfig: this.getDesignerConfig(),
      firstTab: 'componentLib',
      containers: [],
      commonContainers: [],
      basicFields: [],
      advancedFields: [],
      customFields: [],
      chartWidgets: [],
      metaFields: {
        main: {
          entityName: '',
          entityLabel: '',
          fieldList: [
            {
              type: '',
              icon: '',
              displayName: '',
              options: {}
            }
          ]
        },
        detail: [
          {
            entityName: '',
            entityLabel: '',
            fieldList: [
              {
                type: '',
                icon: '',
                displayName: '',
                options: {}
              }
            ]
          }
        ]
      },
      ftcKey: 'ftc' + generateId(),
      scrollerHeight: 0,
      selectedWidget: []
    }
  },

 
  computed: {
    isSelectWidget() {
      let arr = []
      outer: for (const item of this.designer.widgetList) {
        if (item.widgetList) {
          for (const item2 of item.widgetList) {
            if (item2.type === 'tab') {
              arr.push(item2.type)
              break outer
            }
          }
        }
      }
      return arr
    },
    showComponentLib() {
      return this.designerConfig.componentLib === void 0 ? !0 : !!this.designerConfig.componentLib
    },
    showFormTemplates() {
      return this.designerConfig.formTemplates === void 0 ? !0 : !!this.designerConfig.formTemplates
    },
    showChartLib() {
      return this.designerConfig.chartLib === void 0 ? !1 : !!this.designerConfig.chartLib
    },
    showMetadataLib() {
      return this.designerConfig.metadataLib === void 0 ? !1 : !!this.designerConfig.metadataLib
    }
  },

 
  created() {
    this.designerConfig.chartLib && (this.firstTab = 'chartLib'),
      this.designerConfig.metadataLib && (this.firstTab = 'metadataLib'),
      this.loadWidgets(),
      this.designer.handleEvent('refresh-form-templates', () => {
        this.ftcKey = 'ftc' + generateId()
      })
    EC_CONS.forEach((con) => {
      this.designer.addChartContainerSchema(con)
    })
    EC_WS.forEach((wgt) => {
      this.designer.addChartSchema(wgt)
    })

  },
  mounted() {
    this.scrollerHeight = window.innerHeight - 54 + 'px'
    addWindowResizeHandler(() => {
      this.$nextTick(() => {
        this.scrollerHeight = window.innerHeight - 54 + 'px'
      })
    })
  },
  methods: {
    getMetaFieldLabel(o) {
      const e = o.displayName
      return e.substring(e.indexOf('.') + 1, e.length)
    },
    getWidgetLabel(o) {
      return this.i18nt(`extension.widgetLabel.${o.type}`)
    },
    isBanned(o) {
      return this.getBannedWidgets().indexOf(o) > -1
    },
    getFTTitle(o) {
      const e = getLocale()
      return (!!o.i18n && !!o.i18n[e] && o.i18n[e].title) || o.title
    },
    isShowWidget(type){
      // 根据 type 判断是否显示组件
    },
    loadWidgets() {
      ;(this.containers = containers
        .map((o) =>
          We(Ie({ key: generateId() }, o), {
            displayName: this.i18nt(`designer.widgetLabel.${o.type}`, `extension.widgetLabel.${o.type}`)
          })
        )
        .filter((o) => !o.internal && !this.isBanned(o.type))),
        (this.commonContainers = this.containers.filter((o) => !!o.commonFlag)),
        (this.basicFields = basicFields
          .map((o) =>
            We(Ie({ key: generateId() }, o), {
              displayName: this.i18nt(`designer.widgetLabel.${o.type}`, `extension.widgetLabel.${o.type}`)
            })
          )
          .filter((o) => !this.isBanned(o.type))),
        (this.advancedFields = advancedFields
          .map((o) =>
            We(Ie({ key: generateId() }, o), {
              displayName: this.i18nt(`designer.widgetLabel.${o.type}`, `extension.widgetLabel.${o.type}`)
            })
          )
          .filter((o) => !this.isBanned(o.type))),
        (this.customFields = customFields
          .map((o) =>
            We(Ie({ key: generateId() }, o), {
              displayName: this.i18nt(`designer.widgetLabel.${o.type}`, `extension.widgetLabel.${o.type}`)
            })
          )
          .filter((o) => !this.isBanned(o.type))),
        (this.chartContainers = this.designer.chartContainers
          .map((o) =>
            We(Ie({ key: generateId() }, o), {
              displayName: this.i18nt(`designer.widgetLabel.${o.type}`, `extension.widgetLabel.${o.type}`)
            })
          )
          .filter((o) => !o.internal && !this.isBanned(o.type))),
        (this.chartWidgets = this.designer.chartWidgets
          .map((o) =>
            We(Ie({ key: generateId() }, o), {
              displayName: this.i18nt(`designer.widgetLabel.${o.type}`, `extension.widgetLabel.${o.type}`)
            })
          )
          .filter((o) => !this.isBanned(o.type)))
    },
    handleContainerWidgetClone(o) {
      const cloned = deepClone(o)
      delete cloned.key
      delete cloned.displayName
      return cloned
    },
    handleFieldWidgetClone(o) {
      return this.designer.copyNewFieldWidget(o)
    },
    checkContainerMove(o) {
      return this.designer.checkWidgetMove(o)
    },
    checkFieldMove(o) {
      return this.designer.checkFieldMove(o)
    },
    onContainerDragEnd(o) {},
    addContainerByDbClick(o) {
      this.designer.addContainerByDbClick(o)
    },
    addFieldByDbClick(o) {
      this.designer.addFieldByDbClick(o)
    },
    addChartContainerByDbClick(o) {
      this.designer.addChartContainerByDbClick(o)
    },
    addChartByDbClick(o) {
      this.designer.addChartByDbClick(o)
    },
    loadFormTemplate(o, e) {
      this.$confirm(this.i18nt('designer.hint.loadFormTemplateHint'), this.i18nt('render.hint.prompt'), {
        confirmButtonText: this.i18nt('render.hint.confirm'),
        cancelButtonText: this.i18nt('render.hint.cancel')
      })
        .then(() => {
          if (e) {
            this.designer.loadFormJson(JSON.parse(e)),
              this.$message.success(this.i18nt('designer.hint.loadFormTemplateSuccess'))
            return
          }
          axios
            .get(o)
            .then((n) => {
              let l = !1
              typeof n.data == 'string'
                ? (l = this.designer.loadFormJson(JSON.parse(n.data)))
                : n.data.constructor === Object && (l = this.designer.loadFormJson(n.data)),
                l && this.designer.emitHistoryChange(),
                this.$message.success(this.i18nt('designer.hint.loadFormTemplateSuccess'))
            })
            .catch((n) => {
              this.$message.error(this.i18nt('designer.hint.loadFormTemplateFailed') + ':' + n)
            })
        })
        .catch((n) => {
          console.error(n)
        })
    },
    setMetaFields(o) {
      this.metaFields = o
    }
  }
}
</script>
<style lang="scss" scoped>
.main {
  display: flex;
  flex-direction: column;
  padding: 20px;
  border-right: 1px solid #f2f6fc;
}
.main ul {
  display: flex;
  flex-wrap: wrap;
}
.title {
  font-weight: 500;
  font-size: 13px;
  color: #303133;
 
}
.chart-container-widget-list {
  display: flex;
  flex-wrap: wrap;
  .chart-container-widget-item {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    line-height: 32px;
    width: 128px;
    height: 88px;
    margin: 2px 6px 6px 0;
    cursor: pointer;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    background: #fff;
    padding: 0 8px;
    background: #ffffff;
    box-shadow: 0px 1px 7px 0px rgba(0, 0, 0, 0.07);
    border-radius: 4px 4px 4px 4px;
    font-weight: 400;
    font-size: 13px;
    color: #303133;
    line-height: 18px;

    .container-widget-item:hover,
    .field-widget-item:hover {
      background: #f1f2f3;
      border-color: #1890ff;
    }
    .color-svg-icon {
      width: 30px !important;
      height: 30px !important;
      margin-bottom: 9px;
    }

    .drag-handler {
      position: absolute;
      top: 0;
      left: 160px;
      background-color: #dddddd;
      border-radius: 5px;
      padding-right: 5px;
      font-size: 11px;
      color: #666666;
    }
  }
}
</style>
