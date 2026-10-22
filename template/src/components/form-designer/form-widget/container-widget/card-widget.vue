<template>
  <container-wrapper
    :designer="designer"
    :widget="widget"
    :parent-widget="parentWidget"
    :parent-list="parentList"
    :index-of-parent-list="indexOfParentList"
    :style="{
      marginTop: widget.options.topMargin + 20 + 'px !important' || '',
      marginBottom: widget.options.bottomMargin + 'px !important' || ''
    }"
  >
    <el-card
      :key="widget.id"
      class="card-container"
      @click.native.stop="selectWidget(widget)"
      :shadow="widget.options.shadow"
      :style="{ width: widget.options.cardWidth + '!important' || '' }"
      :class="[selected ? 'selected' : '', !!widget.options.folded ? 'folded' : '', customClass]"
    >
      <template #header>
        <div class="header clear-fix pointer" v-if="widget.options.cardHeader" >
          <span>{{ widget.options.label }}</span>

          <i
          @click="toggleCard"
            v-if="widget.options.showFold"
            class="float-right"
            :class="[!widget.options.folded ? 'el-icon-arrow-down' : 'el-icon-arrow-up']"
          ></i>
        </div>
      </template>
      <draggable
        :list="widget.widgetList"
        v-bind="{ group: 'dragGroup', ghostClass: 'ghost', animation: 200 }"
        handle=".drag-handler"
        @add="(evt) => onContainerDragAdd(evt, widget.widgetList)"
        @update="onContainerDragUpdate"
        :move="checkContainerMove"
      >
        <transition-group name="fade" tag="div" class="form-widget-list">
          <template v-for="(subWidget, swIdx) in widget.widgetList">
            <template v-if="'container' === subWidget.category">
              <component
                :is="subWidget.type + '-widget'"
                :widget="subWidget"
                :designer="designer"
                :key="subWidget.id"
                :parent-list="widget.widgetList"
                :index-of-parent-list="swIdx"
                :parent-widget="widget"
                @fieldShowFn="fieldShowFn"
              ></component>
            </template>
            <template v-else>
              <component
                :is="subWidget.type + '-widget'"
                :field="subWidget"
                :designer="designer"
                :key="subWidget.id"
                :parent-list="widget.widgetList"
                :index-of-parent-list="swIdx"
                :parent-widget="widget"
                :design-state="true"
                @fieldShowFn="fieldShowFn"
              ></component>
            </template>
          </template>
        </transition-group>
      </draggable>
    </el-card>
  </container-wrapper>
</template>

<script>
import i18n from '@/utils/i18n'
import containerMixin from '@/components/form-designer/form-widget/container-widget/containerMixin'
import Draggable from 'vuedraggable'
import ContainerWrapper from '@/components/form-designer/form-widget/container-widget/container-wrapper'
import FieldComponents from '@/components/form-designer/form-widget/field-widget/index'
import refMixinDesign from '@/components/form-designer/refMixinDesign'

export default {
  name: 'card-widget',
  componentName: 'ContainerWidget',
  mixins: [i18n, containerMixin, refMixinDesign],
  inject: ['refList'],
  components: {
    Draggable,
    ContainerWrapper,
    ...FieldComponents
  },
  props: {
    widget: Object,
    parentWidget: Object,
    parentList: Array,
    indexOfParentList: Number,
    designer: Object
  },
  computed: {
    selected() {
      return this.widget.id === this.designer.selectedId
    },

    customClass() {
      return this.widget.options.customClass || ''
    }
  },
  watch: {
    'widget.options': {
      handler(newVal) {
      },
      deep: true,
      immediate: true
    },
    'widget.options.showFold': {
      handler(newVal, oldVal) {
       if(!newVal){
        this.widget.options.folded = false
       }
      },
      immediate: true
    }
  },
  created() {
    this.initRefList()
  },
  methods: {
    /**
     * 检查接收哪些组件拖放，如不接受某些组件拖放，则根据组件类型判断后返回false
     * @param evt
     * @returns {boolean}
     */
    checkContainerMove(evt) {
      return true
    },
    fieldShowFn(val) {
      this.widget.widgetList.forEach((item) => {
        this.$set(item, 'isShow', item.id == val.id)
      })
    },

    toggleCard() {
      this.widget.options.folded = !this.widget.options.folded
    },

    /**
     * 设置折叠/打开状态
     * @param folded
     */
    setFolded(folded) {
      this.widget.options.folded = !!folded
    },

    /**
     * 设置是否显示折叠按钮
     * @param showFold
     */
    setShowFold(showFold) {
      this.$set(this.widget.options, 'showFold', showFold)
    }
  }
}
</script>

<style lang="scss" scoped>
.card-container.selected {
  outline: 2px solid #1890ff !important;
}

.card-container {
  margin: 3px;

  .form-widget-list {
    min-height: 28px;
  }
}

::v-deep .el-card__header {
  padding: 0;
}
.header {
  padding: 10px 12px;
}
.folded ::v-deep .el-card__body {
  display: none;
}

.clear-fix:before,
.clear-fix:after {
  display: table;
  content: '';
}

.clear-fix:after {
  clear: both;
}

.float-right {
  float: right;
}
</style>
