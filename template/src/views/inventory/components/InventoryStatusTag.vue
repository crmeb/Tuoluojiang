<template>
  <!-- 查表命中取 label / theme / variant；未知枚举值时 StatusTag 退化为普通文本展示原始值 -->
  <StatusTag :theme="conf?.theme" :variant="conf?.variant">
    <slot>
      {{ conf ? conf.label : value }}
    </slot>
  </StatusTag>
</template>

<script>
import { computed } from "vue";
import StatusTag from "@/components/common/statusTag.vue";

export default {
  name: "InventoryStatusTag",
  components: { StatusTag },
  props: {
    // 状态 / 类型枚举值
    value: {
      type: [String, Number],
      default: undefined
    },
    // value -> { label, theme, variant } 配置表（库存模块各业务 constants 的 XXX_MAP）
    options: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const conf = computed(() => props.options[props.value] || null);

    return {
      conf
    };
  }
};
</script>
