import { reactive, computed, getCurrentInstance, watch } from "vue";
import { useRoute, useRouter } from "vue-router/composables";
import { roterPre } from "@/settings";
import { outboundCreateApi } from "@/api/inventory";
import { contractEditCreateApi } from "@/api/enterprise";
import { useLoading } from "@/composables/useLoading";
import { useOutboundGoods } from "./useOutboundGoods";
import { OUTBOUND_TYPE, OUTBOUND_TYPE_OPTIONS, OUTBOUND_TYPE_MAP } from "../constants";

const toNumber = (val) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

export const useOutboundCreate = () => {
  const { proxy } = getCurrentInstance();
  const router = useRouter();
  const route = useRoute();

  // 订单列表「添加出库单」入口带入的预填参数：仓库 + 订单（按销售出库回填订单商品）
  const presetWarehouseId = toNumber(route.query.warehouse_id);
  const presetOrderId = toNumber(route.query.order_id);
  const presetContractNo = presetOrderId ? String(route.query.contract_no || "") : "";

  // 表单数据（提交字段对齐「新增出库」接口，缺失字段以「新增入库」为准）；
  // 预填值在 reactive 初始化时赋入，避免触发仓库/类型联动清空；订单商品回填由 source_id watch（immediate）统一处理
  const form = reactive({
    warehouse_id: presetWarehouseId || "", // 仓库 ID
    outbound_type: OUTBOUND_TYPE.SALE, // 出库类型：1 销售 / 2 过期退货 / 3 试用 / 4 其他
    // 订单接口返回的 id 为字符串，预填保持字符串类型，保证与 OrderSelect 选项值严格相等以正确回显编号
    source_id: presetOrderId ? String(presetOrderId) : null, // 关联订单 ID：销售出库回填订单商品并提交为来源 ID
    source_no: presetContractNo, // 关联订单编号：提交字段
    remark: "" // 备注
  });

  const rules = {
    warehouse_id: [{ required: true, message: "请选择出库仓库", trigger: "change" }],
    outbound_type: [{ required: true, message: "请选择出库类型", trigger: "change" }]
  };

  // 当前出库类型说明
  const currentTypeDesc = computed(() => OUTBOUND_TYPE_MAP[form.outbound_type]?.desc);

  // 出库商品明细（拆分到独立 composable）
  const goods = useOutboundGoods();

  // 商品明细表格列：调拨出库无单价与金额概念，隐藏「出库单价」「金额小计」两列。
  // 各列 minWidth 合计（含多选列 50）控制在 1080 以内，避免常规视口下出现横向滚动
  // 导致右侧固定的操作列压住「金额小计」
  const goodsColumns = computed(() => {
    const isOther = form.outbound_type === OUTBOUND_TYPE.OTHER;
    return [
      { field: "goods", name: "商品信息", minWidth: 180, slot: "goods" },
      { field: "spec", name: "规格", minWidth: 150, slot: "spec" },
      { field: "price", name: "售价", minWidth: 80, slot: "price" },
      { field: "stock_quality", name: "库存属性", minWidth: 120, slot: "stock_quality" },
      !isOther && { field: "unit_price", name: "出库单价", minWidth: 120, slot: "unit_price" },
      { field: "stock", name: "剩余库存", minWidth: 80, slot: "stock" },
      { field: "quantity", name: "出库数量", minWidth: 110, slot: "quantity" },
      !isOther && { field: "subtotal", name: "金额小计", minWidth: 120, slot: "subtotal" },
      { field: "action", name: "操作", minWidth: 70, fixed: "right", slot: "action" }
    ].filter(Boolean);
  });

  // 销售出库：关联订单后，按订单商品回填明细
  const {
    isLoading: orderLoading,
    startLoading: startOrderLoading,
    stopLoading: stopOrderLoading
  } = useLoading();
  const fillGoodsFromOrder = (orderId) => {
    startOrderLoading();
    contractEditCreateApi(orderId)
      .then((res) => {
        goods.setGoodsFromOrder(res?.data?.product || []);
      })
      .catch((e) => {
        proxy.$message.error(e?.message || "获取订单商品失败");
      })
      .finally(() => {
        stopOrderLoading();
      });
  };

  // 切换出库仓库：可选商品与库存随仓库变化，清空出库类型 / 关联订单 / 已选商品
  watch(() => form.warehouse_id, () => {
    form.source_id = null;
    form.source_no = "";
    goods.clearGoods();
  });

  // 切换出库类型：清空关联订单与已选商品（不同类型表头/数据来源不一致）
  watch(() => form.outbound_type, () => {
    form.source_id = null;
    form.source_no = "";
    goods.clearGoods();
  });

  // 仅销售出库联动：选中订单则回填，清空订单则清空商品；
  // immediate 使入口预填的订单在初始化时同样走该回填逻辑
  watch(() => form.source_id, (orderId) => {
    if (form.outbound_type !== OUTBOUND_TYPE.SALE) return;
    if (orderId) {
      fillGoodsFromOrder(orderId);
    } else {
      goods.clearGoods();
    }
  }, { immediate: true });

  // OrderSelect 选中项回调：记录订单编号供提交
  const onOrderChange = (item) => {
    form.source_no = item?.contract_no || "";
  };

  // 提交
  const {
    isLoading: submitting,
    startLoading: startSubmit,
    stopLoading: stopSubmit
  } = useLoading();
  const submit = async (formRef) => {
    try {
      await formRef.validate();
    } catch (e) {
      return;
    }
    if (!goods.goodsList.value.length) {
      proxy.$message.warning("请先选择出库商品");
      return;
    }

    // 出库数量不能大于剩余库存数量（库存未知的行不做拦截，由后端校验）
    const overStockRow = goods.goodsList.value.find((row) => {
      const stock = goods.getRowStock(row);
      return stock !== null && toNumber(row.quantity) > stock;
    });
    if (overStockRow) {
      proxy.$message.warning(`「${overStockRow.name}」出库数量不能大于剩余库存`);
      return;
    }

    // 调拨出库无出库单价 / 金额，提交时统一清零
    const isOther = form.outbound_type === OUTBOUND_TYPE.OTHER;
    const isSale = form.outbound_type === OUTBOUND_TYPE.SALE;
    if (isSale && !form.source_id) {
      proxy.$message.warning("请选择关联订单");
      return;
    }
    const payload = {
      warehouse_id: form.warehouse_id,
      outbound_type: form.outbound_type,
      source_id: isSale ? form.source_id : 0,
      source_no: isSale ? form.source_no : "",
      remark: form.remark,
      details: goods.goodsList.value.map((row) => ({
        sku_id: row.sku_id,
        stock_quality: row.stock_quality,
        unit_price: isOther ? 0 : toNumber(row.unit_price),
        quantity: toNumber(row.quantity),
        remark: row.remark || ""
      }))
    };

    startSubmit();
    try {
      const res = await outboundCreateApi(payload);
      if (res.status === 200) {
        router.push({ path: roterPre + "/inventory/outbound/index" });
      } else {
      }
    } catch (e) {
      proxy.$message.error(e?.message);
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
    outboundTypes: OUTBOUND_TYPE_OPTIONS.filter(item => item.value !== OUTBOUND_TYPE.OTHER), // 其他类型的出库单只能由系统生成，不能手动添加
    currentTypeDesc,
    goodsColumns,
    orderLoading,
    onOrderChange,
    submitting,
    submit,
    cancel,
    ...goods
  };
};
