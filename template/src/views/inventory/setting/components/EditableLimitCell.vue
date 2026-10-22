<template>
  <div class="editable-limit">
    <!-- 展示态：数值 + 编辑图标，点击进入编辑 -->
    <div v-if="!editing" class="limit-display" @click="startEdit">
      <span class="limit-value">{{ displayValue }}</span>
      <i class="el-icon-edit limit-edit" />
    </div>

    <!-- 编辑态：input 输入框，失焦自动保存，回车确认，Esc 取消 -->
    <el-input
      v-else
      ref="inputRef"
      v-model="draft"
      size="mini"
      class="limit-input"
      :disabled="saving"
      :suffix-icon="saving ? 'el-icon-loading' : ''"
      placeholder="请输入"
      @blur="handleSave"
      @keyup.enter.native="triggerBlur"
      @keyup.esc.native="cancelEdit"
    />
  </div>
</template>

<script>
import { ref, computed, nextTick } from "vue";
import { stockLimitSetApi } from "@/api/inventory";
import { useLoading } from "@/composables/useLoading";
import Tips from "@/utils/tips";

// 归一：空 / 非法 -> null，其余转数字
const toLimit = (val) => {
  if (val === null || val === undefined || val === "") return null;
  const n = Number(val);
  return Number.isFinite(n) ? n : null;
};

export default {
  name: "EditableLimitCell",
  props: {
    // 当前行记录（含 id / high_limit / low_limit）
    row: {
      type: Object,
      required: true
    },
    // 编辑的字段：high_limit | low_limit
    field: {
      type: String,
      required: true,
      validator: (v) => ["high_limit", "low_limit"].includes(v)
    },
    // 单位后缀（仅展示态使用）
    unit: {
      type: String,
      default: ""
    }
  },
  emits: ["saved"],
  setup(props, { emit }) {
    const editing = ref(false);
    const { isLoading: saving, startLoading, stopLoading } = useLoading();
    const canceling = ref(false);
    const draft = ref("");
    const inputRef = ref(null);

    const currentVal = computed(() => toLimit(props.row[props.field]));

    const displayValue = computed(() => {
      if (currentVal.value === null) return "--";
      return `${currentVal.value}${props.unit}`;
    });

    // 进入编辑态：回填当前值并聚焦选中，便于直接覆盖
    const startEdit = () => {
      draft.value = currentVal.value === null ? "" : String(currentVal.value);
      editing.value = true;
      nextTick(() => {
        inputRef.value?.focus?.();
        inputRef.value?.select?.();
      });
    };

    // 回车：主动失焦以触发保存
    const triggerBlur = () => {
      inputRef.value?.blur?.();
    };

    // Esc：标记取消，跳过随后的失焦保存
    const cancelEdit = () => {
      canceling.value = true;
      editing.value = false;
    };

    const handleSave = async () => {
      // Esc 取消 / 重复触发保护
      if (canceling.value) {
        canceling.value = false;
        return;
      }
      if (saving.value || !editing.value) return;

      // 解析输入：空 -> null（清空），否则要求 >= 0 的整数
      const raw = typeof draft.value === "string" ? draft.value.trim() : draft.value;
      let next;
      if (raw === "" || raw === null || raw === undefined) {
        next = null;
      } else {
        const n = Number(raw);
        if (!Number.isFinite(n) || n < 0 || !Number.isInteger(n)) {
          Tips.msgError("请输入大于等于 0 的整数");
          nextTick(() => inputRef.value?.focus?.());
          return; // 保持编辑态待修正
        }
        next = n;
      }

      // 无变化：直接退出
      if (next === currentVal.value) {
        editing.value = false;
        return;
      }

      // 校验库存上限 >= 下限（清空时跳过交叉校验）
      const isHigh = props.field === "high_limit";
      const otherVal = toLimit(isHigh ? props.row.low_limit : props.row.high_limit);
      const high = isHigh ? next : otherVal;
      const low = isHigh ? otherVal : next;
      if (high !== null && low !== null && high < low) {
        Tips.msgError("库存上限不能小于库存下限");
        nextTick(() => inputRef.value?.focus?.());
        return; // 保持编辑态待修正
      }

      startLoading();
      try {
        await stockLimitSetApi({
          id: props.row.id,
          low_limit: low,
          high_limit: high
        });
        props.row[props.field] = next; // 本地即时更新
        editing.value = false;
        emit("saved", { row: props.row, field: props.field, value: next });
      } catch (e) {
        // 失败还原并退出编辑态
        editing.value = false;
      } finally {
        stopLoading();
      }
    };

    return {
      editing,
      saving,
      draft,
      inputRef,
      displayValue,
      startEdit,
      triggerBlur,
      cancelEdit,
      handleSave
    };
  }
};
</script>

<style scoped lang="scss">
.editable-limit {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
}

.limit-display {
  display: inline-flex;
  align-items: center;
  cursor: pointer;

  .limit-value {
    color: #303133;
  }

  .limit-edit {
    margin-left: 8px;
    color: #1890ff;
  }
}

.limit-input {
  width: 110px;
}
</style>
