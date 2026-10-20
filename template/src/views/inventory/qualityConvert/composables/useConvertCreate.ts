import { reactive, computed, watch, getCurrentInstance } from "vue";
import type { FormInstance } from "../../types";
import { useRouter } from "vue-router";
import { roterPre } from "@/settings";
import { convertCreateApi } from "@/api/inventory";
import { useLoading } from "@/composables/useLoading";
import { useConvertGoods } from "./useConvertGoods";
import {
  CONVERT_TYPE,
  CONVERT_TYPE_OPTIONS,
  CONVERT_TYPE_MAP,
  CONVERT_TYPE_DESC,
  CONVERT_ATTACH_MAX
} from "../constants";

const toNumber = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

export const useConvertCreate = () => {
  const proxy = getCurrentInstance()!.proxy!;
  const router = useRouter();

  // 表单数据（提交字段对齐「新增正次转换」接口，缺失字段以「新增入库」为准）
  const form = reactive({
    warehouse_id: "", // 转换仓库 ID
    convert_type: CONVERT_TYPE.GOOD_TO_DEFECT, // 转换类型：1 良品转残次 / 2 残次转良品
    remark: "" // 单据备注
  });

  const rules = {
    warehouse_id: [{ required: true, message: "请选择转换仓库", trigger: "change" }],
    convert_type: [{ required: true, message: "请选择转换类型", trigger: "change" }]
  };

  // 转换商品明细（拆分到独立 composable）
  const goods = useConvertGoods();

  // 剩余库存列名随转换类型切换（良品剩余库存 / 残次剩余库存），故列配置需为 computed
  const goodsColumns = computed(() => [
    { field: "goods", name: "商品信息", minWidth: 180, slot: "goods" },
    { field: "spec", name: "规格", minWidth: 150, slot: "spec" },
    { field: "cost_price", name: "成本价", minWidth: 80, slot: "cost_price" },
    {
      field: "stock",
      name: CONVERT_TYPE_MAP[form.convert_type]?.stockLabel || "剩余库存",
      minWidth: 100,
      slot: "stock"
    },
    { field: "quantity", name: "转换数量", minWidth: 110, slot: "quantity" },
    { field: "subtotal", name: "金额小计", minWidth: 120, slot: "subtotal" },
    { field: "images", name: "图片附件", minWidth: 200, slot: "images" },
    { field: "action", name: "操作", minWidth: 70, fixed: "right", slot: "action" }
  ]);

  // 切换转换仓库：可选商品与库存随仓库变化，清空已选商品
  watch(() => form.warehouse_id, () => {
    goods.clearGoods();
  });

  // 切换转换类型：剩余库存来源随之切换，已输入的转换数量若超过新库存则钳制到上限
  watch(
    () => form.convert_type,
    (type) => {
      goods.goodsList.value.forEach((row) => {
        const stock = goods.getRowStock(row, type);
        if (toNumber(row.quantity) > stock) {
          row.quantity = stock > 0 ? stock : 1;
        }
      });
    }
  );

  // 行内图片附件转接口字段：details[].images 为附件 ID 数组，去重后取前 N 条
  const toDetailImages = (row: { images?: Array<{ id?: number | string } | null> }) => {
    const ids: Array<number | string> = [];
    (row.images || []).forEach((file) => {
      const id = file?.id;
      if (id != null && id !== "" && !ids.includes(id)) ids.push(id);
    });
    return ids.slice(0, CONVERT_ATTACH_MAX);
  };

  // 提交
  const {
    isLoading: submitting,
    startLoading: startSubmit,
    stopLoading: stopSubmit
  } = useLoading();
  const submit = async (formRef: FormInstance) => {
    try {
      await formRef.validate();
    } catch (e) {
      return;
    }

    if (!goods.goodsList.value.length) {
      proxy.$message.warning("请先添加转换商品");
      return;
    }

    // 转换数量不能大于剩余库存数量
    const overStockRow = goods.goodsList.value.find(
      (row) => toNumber(row.quantity) > goods.getRowStock(row, form.convert_type)
    );
    if (overStockRow) {
      proxy.$message.warning(`${overStockRow.name}转换数量不能大于剩余库存`);
      return;
    }

    const payload = {
      warehouse_id: form.warehouse_id,
      convert_type: form.convert_type,
      remark: form.remark,
      details: goods.goodsList.value.map((row) => ({
        sku_id: row.sku_id,
        quantity: toNumber(row.quantity),
        remark: row.remark || "",
        images: toDetailImages(row)
      }))
    };

    startSubmit();
    try {
      const res = await convertCreateApi(payload);
      if (res.status === 200) {
        router.push({ path: roterPre + "/inventory/qualityConvert/index" });
      }
    } catch (e) {
      proxy.$message.error((e as Error)?.message);
    } finally {
      stopSubmit();
    }
  };

  const cancel = () => {
    router.back();
  };

  return {
    form,
    rules,
    convertTypes: CONVERT_TYPE_OPTIONS,
    convertTypeDesc: CONVERT_TYPE_DESC,
    goodsColumns,
    submitting,
    submit,
    cancel,
    ...goods
  };
};
