<template>
  <div class="warehouse-type-select">
    <!-- 左侧仓库类型：调出 / 调入，值对齐接口 warehouse_type（out / in） -->
    <el-select :value="innerType" class="warehouse-type-select__type" size="small" @change="handleTypeChange">
      <el-option v-for="item in typeOptions" :key="item.value" :label="item.label" :value="item.value" />
    </el-select>
    <!-- 右侧仓库选择：完整透传 WareHouseSelect 的 props（multiple / defaultFirst / permission 等） -->
    <WareHouseSelect class="warehouse-type-select__warehouse" :value="value" :placeholder="placeholder"
      v-bind="$attrs" @change="handleChange" @select="handleSelect" />
  </div>
</template>

<script>
import { ref, watch } from "vue";
import WareHouseSelect from "./WareHouseSelect.vue";
import { WAREHOUSE_TYPE, WAREHOUSE_TYPE_OPTIONS } from "../constants";

// WareHouseSelect 的高阶组件：左侧内嵌「调出仓库 / 调入仓库」类型下拉，
// 用于按仓库维度筛选调拨类单据（类型值通过 :type.sync 回传，随查询参数透传接口）
export default {
  name: "WareHouseTypeSelect",
  components: { WareHouseSelect },
  inheritAttrs: false,
  model: {
    prop: "value",
    event: "change"
  },
  props: {
    // 仓库选中值，含义与 WareHouseSelect 的 value 一致（多选时为 ID 数组）
    value: {
      type: [String, Number, Array],
      default: ""
    },
    // 仓库类型（WAREHOUSE_TYPE：out=调出仓库 in=调入仓库），支持 :type.sync；
    // 为空时自动回填默认值「调出仓库」，保证查询参数始终携带有效类型
    type: {
      type: String,
      default: ""
    },
    placeholder: {
      type: String,
      default: "请输入选择仓库"
    }
  },
  setup(props, { emit }) {
    // 类型展示由内部状态驱动：父级（如 formList 的 ruleForm）动态新增 key 时可能不具备响应式，
    // 依赖内部 ref 保证切换后 UI 必定刷新
    const innerType = ref(props.type || WAREHOUSE_TYPE.OUT);

    // 外部显式传入类型时同步内部状态；为空（初始化 / 被重置）时把默认值回填给父级
    watch(() => props.type, (val) => {
      if (val) {
        innerType.value = val;
        return;
      }
      emit("update:type", innerType.value);
    }, { immediate: true });

    const handleTypeChange = (val) => {
      innerType.value = val;
      emit("update:type", val);
      emit("type-change", val);
    };

    // 仓库值 / 选中项原样向上透传，保持与直接使用 WareHouseSelect 一致的事件契约
    const handleChange = (val) => emit("change", val);
    const handleSelect = (val) => emit("select", val);

    return {
      typeOptions: WAREHOUSE_TYPE_OPTIONS,
      innerType,
      handleTypeChange,
      handleChange,
      handleSelect
    };
  }
};
</script>

<style scoped lang="scss">
// 组合下拉：左右两个 select 拼接为一个整体（中缝共用 1px 边框，仅外侧圆角）
.warehouse-type-select {
  display: flex;
  align-items: center;

  &__type {
    width: 100px;
    flex-shrink: 0;

    :deep(.el-input__inner) {
      position: relative;
      padding-left: 10px;
      padding-right: 26px;
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
    }

    // 聚焦时抬升层级，避免高亮右边框被右侧仓库输入框压住。
    // 仅左侧需要：右侧输入框天然绘制在上层，且多选 tags 浮层会被抬升的输入框背景盖住
    :deep(.el-input.is-focus .el-input__inner) {
      z-index: 1;
    }
  }

  &__warehouse {
    flex: 1;
    min-width: 0;
    margin-left: -1px;

    :deep(.el-input__inner) {
      position: relative;
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
    }
  }
}
</style>
