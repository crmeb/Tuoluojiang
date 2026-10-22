import { computed } from "vue";
import { useDetailDrawer } from "@/composables/useDetailDrawer";
import { transferDetailApi } from "@/api/inventory";
import { TRANSFER_TYPE_MAP, TRANSFER_STATUS_MAP } from "../constants";
import { STOCK_QUALITIES } from "./useTransferGoods";

// 库存属性：value -> 文案（对齐「新增调拨」stock_quality 枚举）
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

export const useTransferDetail = () => {
  const drawer = useDetailDrawer(transferDetailApi);

  // 基本信息字段：按「左右两列」逐行交错排列，单据备注跨整行
  const basicFields = computed(() => {
    const data = drawer.detail.value;
    if (!data) return [];

    const typeConf = TRANSFER_TYPE_MAP[data.transfer_type];
    const statusConf = TRANSFER_STATUS_MAP[data.status];

    return [
      { label: "调拨单号：", value: data.bill_no },
      { label: "调拨仓库：", value: `${data.out_warehouse?.name} → ${data.in_warehouse.name}`},
      {
        label: "调拨方式：",
        value: (typeConf && typeConf.label) || data.transfer_type,
        type: "tag",
        theme: typeConf?.theme,
        variant: typeConf?.variant
      },
      {
        label: "调拨状态：",
        value: (statusConf && statusConf.label) || data.status,
        type: "tag",
        theme: statusConf?.theme,
        variant: statusConf?.variant
      },
      { label: "创建人：", value: data.creator_info?.name },
      { label: "创建时间：", value: data.created_at },
      { label: "单据备注：", value: data.remark, span: 2, align: "top" }
    ];
  });

  // 调拨商品明细：将接口 details 映射为表格展示行
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
        cost: toAmount(sku.cost),
        count: toNumber(item.quantity),
        // 接口明细无小计字段，成本小计 = 成本单价 × 调拨数量
        subtotal: toAmount(toNumber(sku.cost) * toNumber(item.quantity))
      };
    });
  });

  // 汇总：商品种类 / 总件数 / 总成本（接口无合计字段，按明细成本 × 数量累加）
  const summary = computed(() => {
    const data = drawer.detail.value;
    const details = (data && data.details) || [];
    return {
      category_count: details.length,
      goods_count: details.reduce((total, item) => total + toNumber(item.quantity), 0),
      total_amount: details
        .reduce((total, item) => total + toNumber(item.sku && item.sku.cost) * toNumber(item.quantity), 0)
        .toFixed(2)
    };
  });

  // 商品表格列：对齐「调拨详情」商品表头
  const goodsColumns = [
    { field: "goods", name: "商品信息", minWidth: 200, slot: "goods" },
    { field: "spec", name: "规格名称", minWidth: 120, slot: "spec" },
    { field: "stock_attr", name: "库存属性", minWidth: 80 },
    { field: "cost", name: "成本", minWidth: 90 },
    { field: "count", name: "调拨数量", minWidth: 80 },
    { field: "subtotal", name: "成本小计", minWidth: 90 }
  ];

  return {
    ...drawer,

    basicFields,
    goodsList,
    summary,
    goodsColumns
  };
};
