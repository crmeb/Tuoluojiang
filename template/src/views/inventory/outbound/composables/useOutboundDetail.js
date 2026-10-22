import { computed } from "vue";
import { useDetailDrawer } from "@/composables/useDetailDrawer";
import { outboundDetailApi } from "@/api/inventory";
import { OUTBOUND_TYPE_MAP, OUTBOUND_STATUS_MAP } from "../constants";
import { PAYMENT_STATUS_MAP } from "../../constants";
import { STOCK_QUALITIES } from "./useOutboundGoods";
import { getTotalAmount } from "../helper";

// 库存属性：value -> 文案（对齐「新增出库」stock_quality 枚举）
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

export const useOutboundDetail = () => {
  const drawer = useDetailDrawer(outboundDetailApi);

  // 基本信息字段：按「左右两列」逐行交错排列，单据备注跨整行
  const basicFields = computed(() => {
    const data = drawer.detail.value;
    if (!data) return [];

    const typeConf = OUTBOUND_TYPE_MAP[data.outbound_type];
    const statusConf = OUTBOUND_STATUS_MAP[data.status];
    const paymentConf = PAYMENT_STATUS_MAP[data.payment_status];

    // 关联付款：仅关联了订单时展示付款状态标签（对齐列表页 payment_status 列），否则展示占位符
    const paymentField = data.order
      ? {
          label: "关联付款：",
          value: (paymentConf && paymentConf.label) || data.payment_status,
          type: "tag",
          theme: paymentConf?.theme,
          variant: paymentConf?.variant
        }
      : { label: "关联付款：", value: "" };

    return [
      { label: "出库单号：", value: data.bill_no },
      { label: "出库仓库：", value: data.warehouse && data.warehouse.name },
      {
        label: "出库类型：",
        value: (typeConf && typeConf.label) || data.outbound_type,
        type: "tag",
        theme: typeConf?.theme,
        variant: typeConf?.variant
      },
      {
        label: "出库状态：",
        value: (statusConf && statusConf.label) || data.status,
        type: "tag",
        theme: statusConf?.theme,
        variant: statusConf?.variant
      },
      { label: "创建人：", value: data.creator_info?.name },
      { label: "创建时间：", value: data.created_at },
      { label: "关联订单：", value: data.source_no },
      paymentField,
      { label: "单据备注：", value: data.remark, span: 2, align: "top" }
    ];
  });

  // 出库商品明细：将接口 details 映射为表格展示行
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
        price: toAmount(sku?.price || 0),
        count: toNumber(item.quantity),
        subtotal: getTotalAmount([item])
      };
    });
  });

  // 汇总：商品种类 / 总件数 / 总金额
  const summary = computed(() => {
    const data = drawer.detail.value;
    const details = (data && data.details) || [];
    return {
      category_count: details.length,
      goods_count: details.reduce((total, item) => total + toNumber(item.quantity), 0),
      total_amount: getTotalAmount(details)
    };
  });

  // 商品表格列：对齐「出库详情」商品表头（出库无「赠送数量」）
  const goodsColumns = [
    { field: "goods", name: "商品信息", minWidth: 200, slot: "goods" },
    { field: "spec", name: "规格名称", minWidth: 120, slot: "spec" },
    { field: "stock_attr", name: "库存属性", minWidth: 80 },
    { field: "price", name: "出库单价", minWidth: 90 },
    { field: "count", name: "出库数量", minWidth: 80 },
    { field: "subtotal", name: "金额小记", minWidth: 90 }
  ];

  return {
    ...drawer,

    basicFields,
    goodsList,
    summary,
    goodsColumns
  };
};
