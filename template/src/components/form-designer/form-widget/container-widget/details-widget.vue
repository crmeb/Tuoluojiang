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
      <draggable
        :list="widget.widgetList"
        v-bind="{ group: 'dragGroup', ghostClass: 'ghost', animation: 200 }"
        :move="checkBContainerValid"
        handle=".drag-handler"
        @add="(evt) => onContainerDragAdd(evt, widget.widgetList, 'details')"
      >
        <!-- @update="onContainerDragUpdate" -->
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
                style="flex-shrink: 0"
              ></component>
            </template>
            <template v-else>
              <component
                :is="subWidget.type + '-widget'"
                :field="subWidget"
                :designer="designer"
                :key="subWidget.id"
                :parentList="widget.widgetList"
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
  name: 'details-widget',
  componentName: 'DetailsWidget',
  mixins: [i18n, containerMixin, refMixinDesign],
  inject: ['refList'],
  components: {
    Draggable,
    ContainerWrapper,
    ...FieldComponents
  },
  props: {
    widget: {
      type: Object,
      required: true,
      validator: (value) => {
        return value && typeof value.id !== 'undefined'
      }
    },

    parentWidget: {
      type: Object,
      default: () => ({})
    },

    parentList: {
      type: Array,
      default: () => []
    },

    // 在父列表中的索引（可选，默认 -1 表示无索引）
    indexOfParentList: {
      type: Number,
      default: -1,
      // 校验索引合法性（可选）
      validator: (value) => value >= -1 // 索引不能为负数（-1 表示未在列表中）
    },

    // 设计器实例（可选，默认 null）
    designer: {
      type: Object,
      default: null
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
  created() {
    this.initRefList()
  },
  methods: {
    fieldShowFn(val) {
      this.widget.widgetList.forEach((item) => {
        this.$set(item, 'isShow', item.id == val.id)
      })
    },
    checkBContainerValid(evt) {
      const sourceList = evt.fromContext
    },

    toggleCard() {
      this.widget.options.folded = !this.widget.options.folded
    },

    setFolded(folded) {
      this.widget.options.folded = !!folded
    }
  }
}
</script>

<style lang="scss" scoped>
.card-container.selected {
  outline: 2px solid #1890ff !important;
}

.card-container {
  width: 100%;
  margin: 3px;
  .form-widget-list {
    width: 100%;
    display: flex;
    min-height: 100px;
    overflow-x: scroll;
  }
}

::v-deep .el-card__body {
  padding-bottom: 0;
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

::v-deep .el-form-item__label {
  display: block !important; /* 强制块级元素，独占一行 */
  vertical-align: baseline !important;
  margin-bottom: 8px !important; /* 与输入框间距 */
  text-align: left !important; /* 文字左对齐（按需调整） */
  padding-right: 0 !important; /* 清除默认右内边距（关键） */
  line-height: 1.5 !important; /* 修复文字行高异常 */
}

/* 2. 调整输入框容器的布局（避免受 label 影响） */
::v-deep .el-form-item__content {
  width: 200px;
  flex-shrink: 0;
  margin-left: 0 !important; /* 清除默认左外边距（关键） */
  padding-left: 0 !important;
}
::v-deep .el-form-item {
  display: flex;
  flex-direction: column;
}
</style>
