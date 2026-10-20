import { reactive, computed, getCurrentInstance, watch } from "vue";
import type { Id } from "@/types/api";
import type { FormInstance } from "../../types";
import { useRoute, useRouter } from "vue-router";
import { roterPre } from "@/settings";
import { inboundCreateApi } from "@/api/inventory";
import { contractEditCreateApi } from "@/api/enterprise";
import { useLoading } from "@/composables/useLoading";
import { useInboundGoods } from "./useInboundGoods";
import { INBOUND_TYPE, INBOUND_TYPE_OPTIONS, INBOUND_TYPE_MAP } from "../constants";

const toNumber = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

export const useInboundCreate = () => {
  const proxy = getCurrentInstance()!.proxy!;
  const router = useRouter();
  const route = useRoute();

  // 订单列表「添加入库单」入口带入的预填参数：仓库 + 订单（按退货入库回填订单商品）
  const presetWarehouseId = toNumber(route.query.warehouse_id);
  const presetOrderId = toNumber(route.query.order_id);
  const presetContractNo = presetOrderId ? String(route.query.contract_no || "") : "";

  // 表单数据（提交字段对齐「新增入库」接口）；
  // 预填值在 reactive 初始化时赋入，避免触发入库类型联动清空；订单商品回填由 source_id watch（immediate）统一处理
  const form = reactive({
    warehouse_id: presetWarehouseId || "", // 仓库 ID
    inbound_type: presetOrderId ? INBOUND_TYPE.RETURN : INBOUND_TYPE.PURCHASE, // 入库类型：1 采购 / 2 退货 / 3 其他
    // 订单接口返回的 id 为字符串，预填保持字符串类型，保证与 OrderSelect 选项值严格相等以正确回显编号
    source_id: presetOrderId ? String(presetOrderId) : null, // 选中订单 ID：退货入库提交并用于拉取订单商品回填
    source_no: presetContractNo, // 关联订单编号：退货入库提交字段
    remark: "" // 备注
  });

  const rules = {
    warehouse_id: [{ required: true, message: "请选择入库仓库", trigger: "change" }],
    inbound_type: [{ required: true, message: "请选择入库类型", trigger: "change" }]
  };

  // 当前入库类型说明
  const currentTypeDesc = computed(() => INBOUND_TYPE_MAP[form.inbound_type]?.desc);

  // 明细表头按入库类型分流：
  // 采购入库展示全部列；退货入库仅保留基础列 + 关联订单销量；调拨入库额外去掉库存属性与明细备注
  const isPurchase = computed(() => form.inbound_type === INBOUND_TYPE.PURCHASE);
  const isReturn = computed(() => form.inbound_type === INBOUND_TYPE.RETURN);

  // 入库商品明细（拆分到独立 composable）
  const goods = useInboundGoods();

  // 退货入库：关联订单后，按订单商品回填明细
  const {
    isLoading: orderLoading,
    startLoading: startOrderLoading,
    stopLoading: stopOrderLoading
  } = useLoading();
  const fillGoodsFromOrder = (orderId: Id) => {
    startOrderLoading();
    contractEditCreateApi(orderId)
      .then((res) => {
        goods.setGoodsFromOrder(res?.data?.product || []);
      })
      .catch((e) => {
        proxy.$message.error((e as Error)?.message || "获取订单商品失败");
      })
      .finally(() => {
        stopOrderLoading();
      });
  };

  // 切换入库类型：清空关联订单与已选商品（不同类型表头/数据来源不一致）
  watch(() => form.inbound_type, () => {
    form.source_id = null;
    form.source_no = "";
    goods.clearGoods();
  });

  // 仅退货入库联动：选中订单则回填，清空订单则清空商品；
  // immediate 使入口预填的订单在初始化时同样走该回填逻辑
  watch(() => form.source_id, (orderId) => {
    if (form.inbound_type !== INBOUND_TYPE.RETURN) return;
    if (orderId) {
      fillGoodsFromOrder(orderId);
    } else {
      goods.clearGoods();
    }
  }, { immediate: true });

  // OrderSelect 选中项回调：记录订单编号供提交
  const onOrderChange = (item?: { contract_no?: string } | null) => {
    form.source_no = item?.contract_no || "";
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
      proxy.$message.warning("请先选择入库商品");
      return;
    }

    // 调拨入库无入库单价 / 赠送数量，提交时统一清零
    const isOther = form.inbound_type === INBOUND_TYPE.OTHER;
    const isReturn = form.inbound_type === INBOUND_TYPE.RETURN;
    const payload = {
      warehouse_id: form.warehouse_id,
      inbound_type: form.inbound_type,
      source_id: isReturn ? form.source_id : 0,
      source_no: isReturn ? form.source_no : "",
      remark: form.remark,
      details: goods.goodsList.value.map((row) => ({
        sku_id: row.sku_id,
        stock_quality: row.stock_quality,
        unit_price: isOther ? 0 : toNumber(row.unit_price),
        quantity: toNumber(row.quantity),
        gift_quantity: isOther ? 0 : toNumber(row.gift_quantity),
        remark: row.remark || ""
      }))
    };

    startSubmit();
    try {
      const res = await inboundCreateApi(payload);
      if (res.status === 200) {
        router.push({ path: roterPre + "/inventory/inbound/index" });
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
    presetContractNo,
    inboundTypes: INBOUND_TYPE_OPTIONS.filter(item => item.value !== INBOUND_TYPE.OTHER), // 其他类型的出库单只能由系统生成，不能手动添加
    currentTypeDesc,
    isPurchase,
    isReturn,
    orderLoading,
    onOrderChange,
    submitting,
    submit,
    cancel,
    ...goods
  };
};
