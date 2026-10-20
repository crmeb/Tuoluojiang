<template>
  <el-dialog v-model="visible" title="单据备注" :width="DIALOG_SIZE.SM" :close-on-click-modal="false" @close="reset">
    <el-form ref="formRef" :model="form" label-width="0">
      <el-form-item>
        <el-input v-model="form.remark" type="textarea" :rows="4" maxlength="250" show-word-limit
          placeholder="请输入备注信息，最多可输入250字" />
      </el-form-item>
    </el-form>
    <template #footer>
      <span>
        <el-button @click="visible = false">取 消</el-button>
        <el-button type="primary" :loading="loading" @click="submit">确 定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script>
import { ref, reactive } from "vue";
import { DIALOG_SIZE } from "@/constants/popupSize.ts";
import { checkRemarkUpdateApi } from "@/api/inventory.ts";
import { useLoading } from "@/composables/useLoading.ts";

export default {
  name: "StocktakingRemarkDialog",
  emits: ["ok"],
  setup(props, { emit, expose }) {
    const visible = ref(false);
    const { isLoading: loading, startLoading, stopLoading } = useLoading();
    const formRef = ref(null);
    const form = reactive({ id: 0, remark: "" });

    const open = (row) => {
      form.id = row.id;
      form.remark = row.remark || "";
      visible.value = true;
    };

    const reset = () => {
      form.id = 0;
      form.remark = "";
    };

    const submit = async () => {
      startLoading();
      try {
        const res = await checkRemarkUpdateApi(form.id, { remark: form.remark });
        if (res.status === 200) {
          visible.value = false;
          emit("ok");
        }
      } finally {
        stopLoading();
      }
    };

    expose({ open });

    return { DIALOG_SIZE, visible, loading, formRef, form, reset, submit };
  }
};
</script>
