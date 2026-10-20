<template>
  <div class="info-selection" :class="{ 'info-selection--fill': fill }">
    <!-- 区块标题：蓝色竖条 + 标题文字，右侧可放扩展内容 -->
    <div class="info-selection__head">
      <span class="info-selection__bar" />
      <span class="info-selection__title">{{ title }}</span>
      <slot name="head-extra" />
    </div>

    <!-- 字段网格：传入 fields 时按「标签：值」成对展示，可选标签样式 / 跨列 -->
    <ul v-if="fields.length" class="info-selection__grid" :style="gridStyle">
      <li v-for="(item, index) of fields" :key="index" class="info-selection__item"
        :style="item.span ? { gridColumn: `span ${item.span}` } : null">
        <span class="info-selection__label" :class="generateCss(item)" :style="labelStyles[index]">{{ item.label }}</span>
        <span class="info-selection__value">
          <StatusTag v-if="item.type === 'tag'" :theme="item.theme || 'primary'" :variant="item.variant">
            {{ item.value }}
          </StatusTag>
          <template v-else>{{ isEmpty(item.value) ? '-' : item.value }}</template>
        </span>
      </li>
    </ul>

    <!-- 默认插槽：放表格、汇总等自定义内容 -->
    <slot />
  </div>
</template>

<script>
import { computed } from "vue";
import StatusTag from "@/components/common/statusTag.vue";

export default {
  name: "InfoSelection",
  components: { StatusTag },
  props: {
    // 区块标题
    title: {
      type: String,
      default: ""
    },
    // 字段列表：{ label, value, type?: 'tag', theme?, variant?, span?, align? }
    fields: {
      type: Array,
      default: () => []
    },
    // 字段网格列数
    columns: {
      type: Number,
      default: 2
    },
    // 是否吃掉父容器剩余高度（父容器需为纵向 flex），供插槽内的表格等撑满高度
    fill: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const gridStyle = computed(() => ({
      gridTemplateColumns: `repeat(${props.columns}, minmax(0, 1fr))`
    }));

    // 标签最小宽度按列独立计算：模拟 grid 行优先自动布局推算每个字段的落点列，
    // 各列取本列最长 label 的字符数（中文 1 字 ≈ 1em）作为该列统一最小宽度，
    // 保证同列标签冒号右对齐，且某列出现长标签时不影响其他列；70px 为底线
    const labelStyles = computed(() => {
      const maxLengthByCol = [];
      const colOfField = [];
      let col = 0;
      props.fields.forEach((item, index) => {
        const span = Math.min(item.span || 1, props.columns);
        if (col + span > props.columns) col = 0; // 当前行剩余列放不下，换行
        colOfField[index] = col;
        maxLengthByCol[col] = Math.max(maxLengthByCol[col] || 0, String(item.label || "").length);
        col = (col + span) % props.columns;
      });
      return props.fields.map((item, index) => {
        const maxLength = maxLengthByCol[colOfField[index]];
        return maxLength ? { minWidth: `max(70px, ${maxLength}em)` } : null;
      });
    });

    const isEmpty = (value) => value === undefined || value === null || value === "";

    const generateCss = item => {
      const css = [];
      if (item.align) css.push(`align-${item.align}`);
      return css;
    }

    return {
      gridStyle,
      labelStyles,
      isEmpty,
      generateCss
    };
  }
};
</script>

<style scoped lang="scss">
.info-selection {
  // 置身纵向 flex 容器时不参与压缩，避免内容超高时字段区、图片区被挤扁（fill 变体除外）
  flex-shrink: 0;

  & + & {
    margin-top: 24px;
  }

  // 由外层纵向 flex 分配剩余高度，min-height: 0 允许收缩，
  // 自身再转纵向 flex，把高度继续传给插槽内的 BaseTable（其 flex: 1 才能生效）
  &--fill {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-flow: column nowrap;
  }

  &__head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
  }

  &__bar {
    width: 3px;
    height: 14px;
    background: #1890ff;
    border-radius: 1px;
  }

  &__title {
    font-size: 14px;
    font-weight: 500;
    color: #303133;
    line-height: 20px;
  }

  &__grid {
    display: grid;
    gap: 16px 24px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__item {
    display: flex;
    align-items: center;
    font-size: 13px;
    line-height: 18px;
  }

  &__label {
    flex-shrink: 0;
    min-width: 70px;
    text-align: right;
    color: #606266;

    &.align-top {
      align-self: flex-start;
    }
  }

  &__value {
    flex: 1;
    margin-left: 6px;
    color: #303133;
    word-break: break-all;
  }
}
</style>
