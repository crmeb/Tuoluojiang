<template>
  <el-dialog :visible.sync="visible" :title="isBatch ? '批量设置库存限制' : '设置库存限制'" :width="DIALOG_SIZE.XS"
    :close-on-click-modal="false" @close="reset">
    <el-form ref="formRef" :model="form" label-width="80px" label-suffix="：" size="small">
      <el-form-item label="库存上限">
        <el-input-number v-model="form.high_limit" :min="0" :controls="false" :precision="0"
          placeholder="请输入库存上限" class="full-input" />
      </el-form-item>
      <el-form-item label="库存下限">
        <el-input-number v-model="form.low_limit" :min="0" :controls="false" :precision="0"
          placeholder="请输入库存下限" class="full-input" />
      </el-form-item>
      <div v-if="isBatch" class="form-note">未填写的项将保留各商品的原有设置</div>
    </el-form>

    <template #footer>
      <span>
        <el-button size="small" @click="visible = false">取 消</el-button>
        <el-button size="small" type="primary" :loading="loading" @click="submit">确 定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script>
import { ref, reactive, computed } from "vue";
import { DIALOG_SIZE } from "@/constants/popupSize";
import { stockLimitSetApi, stockLimitBatchSetApi } from "@/api/inventory";
import { useLoading } from "@/composables/useLoading";
import Tips from "@/utils/tips";

// 提交归一：空 / 非法 -> null，其余转数字
const toLimit = (val) => {
  if (val === null || val === undefined || val === "") return null;
  const n = Number(val);
  return Number.isFinite(n) ? n : null;
};

// 输入框归一：空 / 非法 -> undefined（el-input-number 遇 null 会被 Number(null) 转成 0，须用 undefined 才显示空）
const toInputVal = (val) => {
  if (val === null || val === undefined || val === "") return undefined;
  const n = Number(val);
  return Number.isFinite(n) ? n : undefined;
};

export default {
  name: "StockLimitDialog",
  emits: ["ok"],
  setup(props, { emit, expose }) {
    const visible = ref(false);
    const { isLoading: loading, startLoading, stopLoading } = useLoading();
    const formRef = ref(null);
    const targets = ref([]);
    const form = reactive({ low_limit: undefined, high_limit: undefined });

    const isBatch = computed(() => targets.value.length > 1);

    const open = (rows) => {
      reset();
      targets.value = Array.isArray(rows) ? rows : [rows];
      // 单个设置时回填原有上下限
      if (targets.value.length === 1) {
        const row = targets.value[0];
        form.low_limit = toInputVal(row.low_limit);
        form.high_limit = toInputVal(row.high_limit);
      }
      visible.value = true;
    };

    const reset = () => {
      form.low_limit = undefined;
      form.high_limit = undefined;
      targets.value = [];
    };

    const submit = async () => {
      const low = toLimit(form.low_limit);
      const high = toLimit(form.high_limit);

      // 未填写任何项时不必提交
      if (low === null && high === null) {
        Tips.msgError("请至少填写库存上限或库存下限");
        return;
      }
      // 同时填写时校验上限 >= 下限
      if (low !== null && high !== null && high < low) {
        Tips.msgError("库存上限不能小于库存下限");
        return;
      }

      // 仅提交已填写的上下限，未填写的字段不下发，由后端保留原有设置
      const limitFields = {};
      if (low !== null) limitFields.low_limit = low;
      if (high !== null) limitFields.high_limit = high;

      startLoading();
      try {
        if (isBatch.value) {
          // 批量：按库存记录 ID 数组一次性提交
          await stockLimitBatchSetApi({
            ids: targets.value.map((row) => row.id),
            ...limitFields
          });
        } else {
          // 单个：按库存记录 ID 提交，仓库与 SKU 由后端推导
          await stockLimitSetApi({
            id: targets.value[0].id,
            ...limitFields
          });
        }
        visible.value = false;
        emit("ok");
      } finally {
        stopLoading();
      }
    };

    expose({ open });

    return {
      DIALOG_SIZE,
      visible,
      loading,
      formRef,
      form,
      targets,
      isBatch,
      open,
      reset,
      submit
    };
  }
};
</script>

<style scoped lang="scss">
.full-input {
  width: 100%;

  :deep(.el-input__inner) {
    text-align: left;
  }
}

.form-note {
  padding-left: 80px;
  font-size: 12px;
  color: #909399;
}
</style>
