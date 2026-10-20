<template>
  <container-wrapper
    :designer="designer"
    :widget="widget"
    :parent-widget="parentWidget"
    :parent-list="parentList"
    :index-of-parent-list="indexOfParentList"
    :style="{ marginTop: widget.options.topMargin + 20 + 'px !important' || '', marginBottom: widget.options.bottomMargin + 'px !important' || ''}"
  >
    <div :key="widget.id" class="tab-container" :class="{ selected: selected }" @click.stop="selectWidget(widget)">
      <el-tabs :type="widget.displayType" v-model="activeTab" @tab-click="onTabClick">
        <el-tab-pane
          v-for="(tab, index) in widget.tabs"
          :key="index"
          :label="tab.options.label"
          :name="tab.options.name"
          @click.stop="selectWidget(widget)"
        >
          <draggable
            :list="tab.widgetList"
            v-bind="{ group: 'dragGroup', ghostClass: 'ghost', animation: 200 }"
            handle=".drag-handler"
            @add="(evt) => onContainerDragAdd(evt, tab.widgetList)"
            @update="onContainerDragUpdate"
            :move="checkContainerMove"
            tag="div"
            class="form-widget-list"
            :item-key="$draggableKey"
          >
            <template #item="{ element: subWidget, index: swIdx }">
              <component
                :is="subWidget.type === 'tab' ? 'form-tab-widget' : subWidget.type + '-widget'"
                v-bind="subWidget.category === 'container' ? { widget: subWidget } : { field: subWidget, designState: true }"
                :designer="designer"
                :parent-list="tab.widgetList"
                :index-of-parent-list="swIdx"
                :parent-widget="widget"
              ></component>
            </template>
          </draggable>
        </el-tab-pane>
      </el-tabs>
    </div>
  </container-wrapper>
</template>

<script>
import Draggable from 'vuedraggable'
import i18n from '@/utils/i18n.ts'
import containerMixin from '@/components/form-designer/form-widget/container-widget/containerMixin'
import ContainerWrapper from '@/components/form-designer/form-widget/container-widget/container-wrapper'
import FieldComponents from '@/components/form-designer/form-widget/field-widget/index'
import refMixinDesign from '@/components/form-designer/refMixinDesign'

export default {
  name: 'form-tab-widget',
  mixins: [i18n, containerMixin, refMixinDesign],
  inject: ['refList'],
  components: {
    ContainerWrapper,
    Draggable,

    ...FieldComponents
  },
  props: {
    widget: Object,
    parentWidget: Object,
    parentList: Array,
    indexOfParentList: Number,
    designer: Object,

  },
  data() {
    return {
      activeTab: 'tab1'
      //
    }
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
    //
  },
  created() {
    this.initRefList()
  },
  mounted() {
    //
  },
  methods: {
    onTabClick(evt) {
      let paneName = evt.paneName
      this.widget.tabs.forEach((tp) => {
        tp.options.active = tp.options.name === paneName
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.tab-container {
  //padding: 5px;
  margin: 2px;

  .form-widget-list {
    min-height: 28px;
  }
}

.tab-container.selected {
  outline: 2px solid #409eff !important;
}
</style>
