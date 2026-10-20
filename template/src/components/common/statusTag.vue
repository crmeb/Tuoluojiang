<template>
  <!-- 命中主题：按面性 / 纯文字变体渲染；theme 缺失或非法时退化为普通文本 -->
  <span v-if="themeStyle" class="status-tag" :class="`status-tag--${variant}`" :style="themeStyle">
    <slot />
  </span>
  <span v-else>
    <slot />
  </span>
</template>

<script>
import { computed } from "vue";

// 标签主题色板：状态/类型标签唯一的颜色来源，业务侧只传主题 key，不再各自维护色值
// filled（面性）用 color + bgColor，text（纯文字）仅用 color
const TAG_THEMES = {
  primary: { color: "#1890FF", bgColor: "rgba(24, 144, 255, 0.1)" },
  success: { color: "#19BE6B", bgColor: "rgba(25, 190, 107, 0.1)" },
  warning: { color: "#FF9900", bgColor: "rgba(255, 153, 0, 0.1)" },
  danger: { color: "#F56C6C", bgColor: "rgba(245, 108, 108, 0.1)" },
  pink: { color: "#F95C96", bgColor: "rgba(249, 92, 150, 0.1)" },
  gray: { color: "#909399", bgColor: "rgba(144, 147, 153, 0.1)" }
};

// 标签变体：filled 面性（文字 + 背景色，无边框）/ text 纯文字（仅文字色）
const TAG_VARIANTS = ["filled", "text"];

export default {
  name: "StatusTag",
  props: {
    // 主题 key，决定标签配色
    theme: {
      type: String,
      default: "",
      validator: (val) => !val || Object.keys(TAG_THEMES).includes(val)
    },
    variant: {
      type: String,
      default: "filled",
      validator: (val) => TAG_VARIANTS.includes(val)
    }
  },
  setup(props) {
    const themeStyle = computed(() => {
      const themeConf = TAG_THEMES[props.theme];
      if (!themeConf) return null;
      return {
        "--tag-color": themeConf.color,
        "--tag-bg-color": themeConf.bgColor
      };
    });

    return {
      themeStyle
    };
  }
};
</script>

<style scoped lang="scss">
.status-tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 24px;
  font-size: 12px;
  line-height: 1;
  color: var(--tag-color);

  // 面性：文字 + 浅色背景，边框随文字色
  &--filled {
    padding-inline: 10px;
    border-radius: 4px;
    background-color: var(--tag-bg-color);
    border: 1px solid currentColor;
  }
}
</style>
