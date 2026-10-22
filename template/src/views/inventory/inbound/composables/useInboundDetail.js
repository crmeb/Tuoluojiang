import { computed } from "vue";
import { useDetailDrawer } from "@/composables/useDetailDrawer";
import { inboundDetailApi } from "@/api/inventory";
import { INBOUND_TYPE_MAP, INBOUND_STATUS_MAP, INBOUND_STATUS, INBOUND_TYPE } from "../constants";
import { PAYMENT_STATUS_MAP } from "../../constants";
import { STOCK_QUALITIES } from "./useInboundGoods";

// 库存属性：value -> 文案（对齐「新增入库」stock_quality 枚举）
const STOCK_QUALITY_MAP = STOCK_QUALITIES.reduce((map, item) => {
  map[item.value] = item.label;
  return map;
}, {});

// 数值型金额/数量字段后端以字符串返回（如 "0.00" / "1.0000"），统一转数字
const toNumber = (val) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

const toAmount = (val) => `¥${toNumber(val).toFixed(2)}`;

export const useInboundDetail = () => {
  const drawer = useDetailDrawer(inboundDetailApi);

  // 基本信息字段：按「左右两列」逐行交错排列，单据备注跨整行
  const basicFields = computed(() => {
    const data = drawer.detail.value;
    if (!data) return [];

    const typeConf = INBOUND_TYPE_MAP[data.inbound_type];
    const statusConf = INBOUND_STATUS_MAP[data.status];
    const paymentConf = PAYMENT_STATUS_MAP[data.payment_status];

    // 关联付款：仅关联了订单时展示付款状态标签（对齐出库单详情），否则展示占位符
    const paymentField = data.order
      ? {
        label: "关联付款：",
        value: (paymentConf && paymentConf.label) || data.payment_status,
        type: "tag",
        theme: paymentConf?.theme,
        variant: paymentConf?.variant
      }
      : { label: "关联付款：", value: "" };

    const operatorField = [
      { label: "入库操作人：", value: data.operator_info?.name },
      { label: "入库时间：", value: data.operated_at },
    ];

    const otherField = [
      { label: "关联订单：", value: data.source_no },
      paymentField,
      { label: "单据备注：", value: data.remark, span: 2, align: "top" }
    ];

    const fields = [
      { label: "入库单号：", value: data.bill_no },
      { label: "入库仓库：", value: data.warehouse && data.warehouse.name },
      {
        label: "入库类型：",
        value: (typeConf && typeConf.label) || data.inbound_type,
        type: "tag",
        theme: typeConf?.theme,
        variant: typeConf?.variant
      },
      {
        label: "入库状态：",
        value: (statusConf && statusConf.label) || data.status,
        type: "tag",
        theme: statusConf?.theme,
        variant: statusConf?.variant
      },
      { label: "创建人：", value: data.creator_info?.name },
      { label: "创建时间：", value: data.created_at },
    ];

    if (data.status === INBOUND_STATUS.COMPLETED) fields.push(...operatorField);
    fields.push(...otherField);

    return fields;
  });

  // 入库商品明细：将接口 details 映射为表格展示行
  const goodsList = computed(() => {
    const data = drawer.detail.value;
    if (!data || !data.details) return [];

    return data.details.map((item) => {
      const sku = item.sku || {};
      const product = item.product || {};
      return {
        image: sku.image || "",
        name: product.name || "",
        spec: sku.sku || "",
        bar_code: sku.bar_code || "",
        product_bar_code: sku.product_bar_code || "",
        stock_attr: STOCK_QUALITY_MAP[item.stock_quality] || "-",
        price: toAmount(item.unit_price),
        unit_price: toAmount(item.unit_price),
        count: toNumber(item.quantity),
        gift: toNumber(item.gift_quantity),
        subtotal: toAmount(item.amount),
        // 明细备注：新增入库时按商品行填写，未填则表格内展示占位符
        remark: item.remark || ""
      };
    });
  });

  // 汇总：商品种类 / 总件数（入库数量 + 赠送数量） / 总金额
  const summary = computed(() => {
    const data = drawer.detail.value;
    const details = (data && data.details) || [];
    return {
      category_count: details.length,
      goods_count: details.reduce(
        (total, item) => total + toNumber(item.quantity) + toNumber(item.gift_quantity),
        0
      ),
      total_amount: toNumber(data && data.total_amount).toFixed(2)
    };
  });

  // 仅采购入库有单价 / 赠送 / 金额等信息（对齐「新增入库」明细表头的分流规则）
  const isPurchase = computed(() => drawer.detail.value?.inbound_type === INBOUND_TYPE.PURCHASE);
  const isReturn = computed(() => drawer.detail.value?.inbound_type === INBOUND_TYPE.RETURN);
  const isOther = computed(() => drawer.detail.value?.inbound_type === INBOUND_TYPE.OTHER);

  // 商品表格列：依据 MasterGo「入库详情」商品表头，末列为明细备注。
  // 抽屉宽 800px、内容左右各留 20px，故各列 minWidth 合计控制在 750 以内避免横向滚动，
  // 备注超长由列内 el-tooltip 兜底展示全文。
  // 退货 / 调拨入库仅保留商品信息、规格名称与入库数量，其余列随类型隐藏
  const goodsColumns = computed(() => [
    { field: "goods", name: "商品信息", minWidth: 170, slot: "goods" },
    { field: "spec", name: "规格名称", minWidth: 98, slot: "spec" },
    { field: "stock_attr", name: "库存属性", minWidth: 76 },
    isPurchase.value && { field: "price", name: "入库单价", minWidth: 76 },
    { field: "count", name: "入库数量", minWidth: 76 },
    isReturn.value && { field: "unit_price", name: "售价", minWidth: 76 },
    isPurchase.value && { field: "gift", name: "赠送数量", minWidth: 76 },
    isPurchase.value && { field: "subtotal", name: "金额小记", minWidth: 78 },
    isPurchase.value && { field: "remark", name: "明细备注", minWidth: 100, slot: "remark" }
  ].filter(Boolean));

  return {
    ...drawer,

    basicFields,
    goodsList,
    summary,
    isPurchase,
    goodsColumns
  };
};
