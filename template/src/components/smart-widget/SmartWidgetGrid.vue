<!--
  vue-smart-widget@0.6.2（MIT）的 SmartWidgetGrid 移植版：原包基于 vue-grid-layout，只支持 Vue 2；
  这里改用 grid-layout-plus。保留原 DOM 类名（vue-grid-layout / vue-grid-item），项目里的样式覆盖不用改。
-->
<template>
  <GridLayout
    v-model:layout="innerLayout"
    class="vue-grid-layout smart-widget-grid"
    :col-num="colNum"
    :row-height="rowHeight"
    :max-rows="maxRows"
    :margin="margin"
    :is-draggable="draggable"
    :is-resizable="resizable"
    :use-css-transforms="false"
    v-bind="$attrs"
  >
    <GridItem
      v-for="item in innerLayout"
      :key="item.i"
      class="vue-grid-item"
      v-bind="item"
      drag-ignore-from=".widget-body"
      :static="isStatic"
      @move="(i, newX, newY) => $emit('move', { i, newX, newY })"
      @moved="(i, newX, newY) => $emit('moved', { i, newX, newY })"
      @resize="(i, newH, newW, newHPx, newWPx) => $emit('resize', { i, newH, newW, newHPx, newWPx })"
      @resized="(i, newH, newW, newHPx, newWPx) => $emit('resized', { i, newH, newW, newHPx, newWPx })"
    >
      <SmartWidgetItemContext :item="item">
        <slot :name="item.i" />
      </SmartWidgetItemContext>
    </GridItem>
  </GridLayout>
</template>

<script>
import { defineComponent, provide, reactive, toRef } from 'vue'
import { GridLayout, GridItem } from 'grid-layout-plus'
import { SMART_WIDGET_GRID, SMART_WIDGET_ITEM } from './context.ts'

// 给每个格子里的 SmartWidget 提供所在格子的布局信息（原实现通过 $parent 读取 vue-grid-layout 内部状态）
const SmartWidgetItemContext = defineComponent({
  name: 'SmartWidgetItemContext',
  props: { item: { type: Object, required: true } },
  setup(props, { slots }) {
    provide(SMART_WIDGET_ITEM, toRef(props, 'item'))
    return () => (slots.default ? slots.default() : null)
  }
})

export default {
  name: 'SmartWidgetGrid',
  components: { GridLayout, GridItem, SmartWidgetItemContext },
  inheritAttrs: false,
  props: {
    layout: { type: Array, required: true },
    colNum: { type: Number, default: 12 },
    maxRows: { type: Number, default: Infinity },
    rowHeight: { type: Number, default: 48 },
    margin: { type: Array, default: () => [10, 10] },
    draggable: { type: Boolean, default: true },
    resizable: { type: Boolean, default: true },
    isStatic: { type: Boolean, default: false }
  },
  emits: ['update:layout', 'move', 'moved', 'resize', 'resized'],
  setup(props) {
    provide(
      SMART_WIDGET_GRID,
      reactive({
        rowHeight: toRef(props, 'rowHeight'),
        margin: toRef(props, 'margin')
      })
    )
  },
  computed: {
    // 原组件直接修改 layout 数组（父组件传入的同一引用），这里保持同样的原地修改语义
    innerLayout: {
      get() {
        return this.layout
      },
      set(val) {
        if (val !== this.layout) {
          this.layout.splice(0, this.layout.length, ...val)
        }
        this.$emit('update:layout', this.layout)
      }
    }
  }
}
</script>

<style>
.smart-widget-grid {
  --vgl-placeholder-bg: #7cbeff;
  background: transparent;
}
.smart-widget-grid .smartwidget {
  height: inherit;
  width: inherit;
}
.smart-widget-grid .smartwidget.smartwidget-fullscreen {
  height: 100%;
  width: 100%;
}
</style>
