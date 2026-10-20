<template>
  <div class="base-table-wrapper" :style="selectionCountStyle">
    <!-- height 必须传：el-table 只有拿到 height / max-height 才会计算表体高度，
         固定列与表体的滚动位置才会同步。
         例外是 autoHeight（表单页内嵌明细表）：容器高度由行数撑开，此时传 height 会把
         body 锁死在首次布局的高度，新增行只能靠内部滚动才看得见 -->
    <el-table ref="tableRef" class="base-table" :height="autoHeight ? undefined : '100%'" :data="data"
      v-loading="loading" tooltip-effect="dark" :row-key="rowKey || undefined" :cell-class-name="cellPaddingClass"
      :header-cell-class-name="cellPaddingClass" :row-class-name="rowClassName || undefined"
      @selection-change="handleSelectionChange" @row-click="handleRowClick"
      :class="{ 'transparent-bg': transparentBg }">
      <el-table-column v-if="selection" type="selection" width="50" :fixed="selectionFixed" :selectable="selectable" />
      <!-- show-overflow-tooltip 的气泡限宽由全局 styles/element-ui.scss 的 tooltip 规则统一控制 -->
      <el-table-column v-for="col of tableColumns" :key="col.field" :label="col.name" :prop="col.field"
        :min-width="col.minWidth" :width="col.width" :fixed="col.fixed" :class-name="col.className"
        :show-overflow-tooltip="col.showOverflowTooltip">
        <template v-if="col.headerSlot" #header="scope">
          <slot :name="col.headerSlot" :column="scope.column" :index="scope.$index" />
        </template>
        <template #default="scope">
          <!-- Element Plus 注册列时会用 $index=-1、row={} 预调用插槽；不要把占位行传给业务组件 -->
          <template v-if="scope.$index >= 0">
            <slot v-if="col.slot" :name="col.slot" :row="scope.row" :index="scope.$index" :column="col" />
            <template v-else>{{ getCellText(scope.row, col.field) }}</template>
          </template>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination v-if="showPagination" class="base-pagination el-pagination-reset" :current-page="page"
      :page-size="pageSize" :page-sizes="[15, 20, 30]" :total="total" layout="total, sizes,prev, pager, next, jumper"
      @size-change="handlePageSizeChange" @current-change="handlePageChange" />
  </div>
</template>

<script>
import { ref, computed } from "vue";

export default {
  name: "BaseTable",
  props: {
    tableColumns: {
      type: Array,
      default: () => []
    },
    data: {
      type: Array,
      default: () => []
    },
    total: {
      type: Number,
      default: 0
    },
    page: {
      type: Number,
      default: 0
    },
    pageSize: {
      type: Number,
      default: 0
    },
    loading: {
      type: Boolean,
      default: false
    },
    showPagination: {
      type: Boolean,
      default: true
    },
    // 是否显示多选列
    selection: {
      type: Boolean,
      default: false
    },
    // 行唯一标识字段，开启多选时建议传入（如 "sku_id"）
    rowKey: {
      type: String,
      default: ""
    },
    // 多选列是否可勾选的判定函数 (row, index) => boolean
    selectable: {
      type: Function,
      default: undefined
    },
    transparentBg: {
      type: Boolean,
      default: true
    },
    // 表格高度是否随行数自适应（表单页内嵌明细表用），关闭表头固定与 body 内部滚动
    autoHeight: {
      type: Boolean,
      default: false
    },
    // 行类名，透传给 el-table 的 row-class-name（如按选中行加高亮）
    rowClassName: {
      type: [String, Function],
      default: ""
    }
  },
  setup(props, ctx) {
    const tableRef = ref();
    // 当前多选选中数量，用于表头选中框角标
    const selectedCount = ref(0);

    // el-table 要求固定列必须连续排在最左/最右，只要有列固定在左侧，
    // 位于其前面的多选列也必须一起固定，否则固定列区会错位
    const selectionFixed = computed(() =>
      props.tableColumns.some((col) => col.fixed === true || col.fixed === "left")
    );

    // 给首/末列的 th、td 打类名以便加大表格两侧留白；
    // 不用 CSS :last-child 是因为表头末尾可能渲染 th.gutter（滚动条占位），会选错列
    const totalColumnCount = computed(() => props.tableColumns.length + (props.selection ? 1 : 0));
    const cellPaddingClass = ({ columnIndex }) => {
      const classes = [];
      if (columnIndex === 0) classes.push("is-first-col");
      if (columnIndex === totalColumnCount.value - 1) classes.push("is-last-col");
      return classes.join(" ");
    };

    // 通过 CSS 变量把选中数量传给表头选中框的 ::after 伪元素（0 时置 none 隐藏角标）
    const selectionCountStyle = computed(() => {
      const count = selectedCount.value;
      const text = count > 99 ? "99+" : count;
      return { "--selection-count": count > 0 ? `'${text}'` : "none" };
    });

    const handlePageChange = (v) => {
      ctx.emit("update:page", v);
    };
    const handlePageSizeChange = (v) => {
      ctx.emit("update:pageSize", v);
    }
    const handleSelectionChange = (rows) => {
      selectedCount.value = rows.length;
      ctx.emit("selection-change", rows);
    };
    // 透传行点击，供「点行选中」这类交互使用
    const handleRowClick = (row, column, event) => {
      ctx.emit("row-click", row, column, event);
    };

    // 透传 el-table 多选操作能力，供父组件通过 ref 调用
    const clearSelection = () => tableRef.value?.clearSelection();
    const toggleRowSelection = (row, selected) => tableRef.value?.toggleRowSelection(row, selected);
    const toggleAllSelection = () => tableRef.value?.toggleAllSelection();

    const isEmpty = (val) => typeof val === "undefined" || val === null || val === "";

    // 支持 field 写成 "warehouse.name" 这类点语法路径（与 el-table prop 的取值规则一致）；
    // 路径中途取到 null / undefined 时直接返回 undefined，交给 isEmpty 兜底为 "--"
    const getFieldValue = (row, field) => {
      if (!row || !field) return undefined;
      if (field.indexOf(".") === -1) return row[field];
      return field.split(".").reduce((val, key) => (isEmpty(val) ? undefined : val[key]), row);
    };

    const getCellText = (row, field) => {
      const val = getFieldValue(row, field);
      return isEmpty(val) ? "--" : val;
    };

    return {
      tableRef,
      selectionFixed,
      selectionCountStyle,
      cellPaddingClass,
      handlePageChange,
      handlePageSizeChange,
      handleSelectionChange,
      handleRowClick,
      clearSelection,
      toggleRowSelection,
      toggleAllSelection,

      getCellText
    };
  }
}
</script>

<style scoped lang="scss">
.base-table-wrapper {
  flex: 1;
  // flex 子项最小尺寸默认为内容高度（min-height: auto），
  // 置 0 才允许收缩到父容器分配的剩余高度，滚动才能落在表格内部
  min-height: 0;
  display: flex;
  flex-flow: column nowrap;

  // 由外层 flex 链把剩余高度分配给 el-table，配合模板上的 height="100%"，
  // 由 el-table 自身接管表头固定 + body 内部滚动（勿改回手写 overflow，会破坏固定列滚动同步）
  .base-table {
    flex: 1;
    min-height: 0;

    // 透明背景：仅去掉表体（含展开行）的默认白底，透出页面背景；
    // 表头底色保留（由全局 styles/index.scss 的 `.el-table th` 控制）。
    // td 背景继承自 tr，固定列影子表格复用同一套 tr 结构，选择器一并覆盖
    &.transparent-bg {
      background-color: transparent;

      :deep(.el-table__body tr),
      :deep(.el-table__expanded-cell) {
        background-color: transparent;
      }
    }
  }

  .base-pagination {
    justify-content: flex-end;
    margin-top: 14px;
  }

  .empty-text {
    text-align: center;
    padding: 40px 0;
    /* 增加上下间距，使视觉效果更好 */
    color: #606266;
    /* 保持与Element默认文本颜色一致 */
  }

  // 表格两侧留白：首列左 / 末列右边距 20px（类名由 cellPaddingClass 注入）
  :deep(.is-first-col .cell) {
    padding-left: 20px;
  }

  :deep(.is-last-col .cell) {
    padding-right: 20px;
  }

  // 多选表头选中框右上角数量角标：内容取自 --selection-count（0 时为 none 自动隐藏）
  :deep(.el-table__header .el-table-column--selection .cell) {
    position: relative;
    overflow: visible;

    &::after {
      content: var(--selection-count);
      position: absolute;
      top: -8px;
      right: 0px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 16px;
      height: 16px;
      padding: 0 3px;
      box-sizing: border-box;
      font-size: 11px;
      line-height: 16px;
      color: #fff;
      background-color: #1890ff;
      border-radius: 8px;
      pointer-events: none;
    }
  }
}
</style>
