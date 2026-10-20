import { reactive, computed, watch, nextTick, getCurrentInstance } from "vue";
import type { FormInstance } from "../../types";
import { useRoute, useRouter } from "vue-router";
import { roterPre } from "@/settings";
import { transferCreateApi, transferDetailApi, transferResubmitApi } from "@/api/inventory";
import { useLoading } from "@/composables/useLoading";
import { useTransferGoods } from "./useTransferGoods";
import { TRANSFER_TYPE, TRANSFER_TYPE_OPTIONS } from "../constants";

const toNumber = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

export const useTransferCreate = () => {
  const proxy = getCurrentInstance()!.proxy!;
  const route = useRoute();
  const router = useRouter();

  // 编辑态：路由携带 id 时为「已作废调拨单重新提交」，回填原单据后走重新提交接口
  const transferId = Number(route.query.id) || 0;
  const isEdit = computed(() => !!transferId);
  const pageTitle = computed(() => (isEdit.value ? "编辑调拨单" : "新增调拨单"));

  // 表单数据（提交字段对齐「新增调拨」接口，缺失字段以「新增入库」为准）
  const form = reactive({
    out_warehouse_id: "", // 调出仓库 ID
    in_warehouse_id: "", // 调入仓库 ID（不能与调出相同）
    transfer_type: TRANSFER_TYPE.DIRECT, // 调拨方式：1 直接调拨 / 2 物流调拨
    remark: "" // 单据备注
  });

  const rules = {
    out_warehouse_id: [{ required: true, message: "请选择调出仓库", trigger: "change" }],
    in_warehouse_id: [{ required: true, message: "请选择调入仓库", trigger: "change" }],
    transfer_type: [{ required: true, message: "请选择调拨方式", trigger: "change" }]
  };

  // 调拨商品明细（拆分到独立 composable）
  const goods = useTransferGoods();

  const goodsColumns = [
    { field: "goods", name: "商品信息", minWidth: 180, slot: "goods" },
    { field: "spec", name: "规格", minWidth: 150, slot: "spec" },
    { field: "stock_quality", name: "库存属性", minWidth: 120, slot: "stock_quality" },
    { field: "stock", name: "剩余库存", minWidth: 80, slot: "stock" },
    { field: "quantity", name: "调拨数量", minWidth: 110, slot: "quantity" },
    { field: "price", name: "售价", minWidth: 80, slot: "price" },
    { field: "subtotal", name: "金额小计", minWidth: 120, slot: "subtotal" },
    { field: "action", name: "操作", minWidth: 70, fixed: "right", slot: "action" }
  ];

  // 切换调出仓库：可选商品与剩余库存随仓库变化，清空已选商品。
  // 详情回填赋值同样会触发 watch，回填期间跳过，避免清空刚回填的明细
  let filling = false;
  watch(() => form.out_warehouse_id, () => {
    if (filling) return;
    goods.clearGoods();
  });

  // 编辑态回填：拉取调拨详情，回填基本信息与商品明细，并按调出仓库补齐剩余库存
  const { isLoading: detailLoading, startLoading: startDetail, stopLoading: stopDetail } = useLoading();
  const loadDetail = async () => {
    if (!transferId) return;
    startDetail();
    filling = true;
    try {
      const res = await transferDetailApi(transferId);
      // http 库业务失败（status 400 等）仍会 resolve，需显式判定
      if (res.status !== 200) return;
      const data = res.data || {};
      form.out_warehouse_id = data.out_warehouse_id ?? "";
      form.in_warehouse_id = data.in_warehouse_id ?? "";
      form.transfer_type = data.transfer_type ?? TRANSFER_TYPE.DIRECT;
      form.remark = data.remark || "";
      goods.setGoodsFromDetail(data.details);
      // 等 watch（pre flush）执行完再放开，避免回填赋值触发的清空
      await nextTick();
      filling = false;
      await goods.fillStockFromWarehouse(form.out_warehouse_id);
    } catch (e) {
      // 错误提示由 request 统一处理，这里静默
    } finally {
      filling = false;
      stopDetail();
    }
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

    // 调出仓库与调入仓库不能相同（选择器已禁用同仓，这里再做一次兜底校验）
    if (form.out_warehouse_id === form.in_warehouse_id) {
      proxy.$message.warning("调出仓库与调入仓库不能相同");
      return;
    }

    if (!goods.goodsList.value.length) {
      proxy.$message.warning("请先添加调拨商品");
      return;
    }

    // 调拨数量不能大于剩余库存数量（按库存属性区分正次品，库存未知时不校验）
    const overStockRow = goods.goodsList.value.find((row) => {
      const stock = goods.getRowStock(row);
      return stock !== null && toNumber(row.quantity) > stock;
    });
    if (overStockRow) {
      proxy.$message.warning(`${overStockRow.name}调拨数量不能大于剩余库存`);
      return;
    }

    const payload = {
      out_warehouse_id: form.out_warehouse_id,
      in_warehouse_id: form.in_warehouse_id,
      transfer_type: form.transfer_type,
      remark: form.remark,
      details: goods.goodsList.value.map((row) => ({
        sku_id: row.sku_id,
        stock_quality: row.stock_quality,
        quantity: toNumber(row.quantity),
        remark: row.remark || ""
      }))
    };

    startSubmit();
    try {
      // 编辑态走「重新提交」接口，请求体与新建调拨一致
      const res = isEdit.value
        ? await transferResubmitApi(transferId, payload)
        : await transferCreateApi(payload);
      if (res.status === 200) {
        router.push({ path: roterPre + "/inventory/transfer/index" });
      } else {
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
    isEdit,
    pageTitle,
    transferTypes: TRANSFER_TYPE_OPTIONS,
    goodsColumns,
    detailLoading,
    submitting,
    loadDetail,
    submit,
    cancel,
    ...goods
  };
};
