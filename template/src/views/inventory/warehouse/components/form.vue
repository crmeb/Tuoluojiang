<template>
  <el-dialog :visible.sync="visible" :title="form.id ? '编辑仓库' : '新增仓库'" :width="DIALOG_SIZE.MD"
    :close-on-click-modal="false" custom-class="warehouse-form-dialog" @close="reset">
    <el-form ref="formRef" v-loading="detailLoading" :model="form" :rules="rules" label-width="90px" label-suffix="：">
      <el-form-item label="仓库名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入仓库名称" maxlength="50" show-word-limit />
      </el-form-item>

      <el-form-item label="联系人" prop="contact">
        <el-input v-model="form.contact" placeholder="请输入联系人" maxlength="50" />
      </el-form-item>

      <el-form-item label="联系电话" prop="phone">
        <el-input v-model="form.phone" placeholder="请输入联系电话" maxlength="20" />
      </el-form-item>

      <el-form-item label="仓库地址" prop="address">
        <el-input v-model="form.address" placeholder="请输入仓库地址" maxlength="100" />
      </el-form-item>

      <el-form-item label="备注" prop="remark">
        <el-input v-model="form.remark" type="textarea" :rows="4" placeholder="请输入备注" maxlength="200"
          show-word-limit />
      </el-form-item>

      <el-form-item label="排序" prop="sort">
        <el-input v-model="form.sort" placeholder="请输入排序" />
        <div class="form-tip">数字越大越靠前</div>
      </el-form-item>

      <el-form-item label="开启状态" prop="status">
        <el-switch v-model="form.status" :active-value="1" :inactive-value="0" active-text="开启"
          inactive-text="关闭" />
        <div class="form-tip">关闭后，该仓库禁止使用</div>
      </el-form-item>
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
import { ref, reactive } from "vue";
import { DIALOG_SIZE } from "@/constants/popupSize";
import { warehouseCreateApi, warehouseUpdateApi, warehouseDetailApi } from "@/api/inventory";
import { useLoading } from "@/composables/useLoading";

export default {
  name: "WarehouseForm",
  emits: ["ok"],
  setup(props, { emit, expose }) {
    const visible = ref(false);
    const { isLoading: loading, startLoading, stopLoading } = useLoading();
    const { isLoading: detailLoading, startLoading: startDetailLoading, stopLoading: stopDetailLoading } = useLoading();
    const formRef = ref(null);

    const form = reactive({
      id: 0,
      name: "",
      contact: "",
      phone: "",
      address: "",
      remark: "",
      sort: "",
      status: 1
    });

    const rules = {
      name: [{ required: true, message: "请输入仓库名称", trigger: "blur" }],
      sort: [{ pattern: /^\d*$/, message: "排序只能为非负整数", trigger: "blur" }]
    };

    const reset = () => {
      form.id = 0;
      form.name = "";
      form.contact = "";
      form.phone = "";
      form.address = "";
      form.remark = "";
      form.sort = "";
      form.status = 1;
      formRef.value && formRef.value.clearValidate();
    };

    // 回填表单（编辑场景：列表行 row 或详情接口返回的对象）
    const fillForm = (data) => {
      form.name = data.name ?? "";
      form.contact = data.contact ?? "";
      form.phone = data.phone ?? "";
      form.address = data.address ?? "";
      form.remark = data.remark ?? "";
      form.sort = data.sort ?? "";
      form.status = data.status ?? 1;
    };

    const openBox = async (row) => {
      reset();
      visible.value = true;
      if (!row) return;

      // 编辑：拉取详情回填，避免依赖列表行字段是否完整
      form.id = row.id;
      startDetailLoading();
      try {
        const res = await warehouseDetailApi(row.id);
        fillForm(res.data || {});
      } finally {
        stopDetailLoading();
      }
    };

    const submit = () => {
      formRef.value.validate(async (valid) => {
        if (!valid) return;
        startLoading();
        try {
          const payload = {
            name: form.name,
            contact: form.contact,
            phone: form.phone,
            address: form.address,
            remark: form.remark,
            sort: Number(form.sort) || 0,
            status: form.status
          };
          const res = form.id
            ? await warehouseUpdateApi(form.id, payload)
            : await warehouseCreateApi(payload);
          // 业务校验失败时拦截器以 status=400 resolve（已弹错误提示），保持弹窗打开供修改
          if (res && res.status === 200) {
            visible.value = false;
            emit("ok");
          }
        } finally {
          stopLoading();
        }
      });
    };

    expose({ openBox });

    return { DIALOG_SIZE, visible, loading, detailLoading, formRef, form, rules, openBox, reset, submit };
  }
};
</script>

<style scoped lang="scss">
.form-tip {
  margin-top: 4px;
  font-size: 12px;
  line-height: 18px;
  color: #909399;
}
</style>
