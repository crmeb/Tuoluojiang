import { computed } from "vue";
import type { BillDetailItem, SkuInfo, ProductInfo } from "../../types";
import { useDetailDrawer } from "@/composables/useDetailDrawer";
import { convertDetailApi } from "@/api/inventory";
import { CONVERT_TYPE_MAP } from "../constants";

// 数值型金额 / 数量字段后端以字符串返回（如 "0.00" / "1.0000"），统一转数字
const toNumber = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

const toAmount = (val: unknown) => `¥${toNumber(val).toFixed(2)}`;

export const useConvertDetail = () => {
  const drawer = useDetailDrawer(convertDetailApi);

  // 基本信息字段：按「左右两列」逐行交错排列，单据备注跨整行
  const basicFields = computed(() => {
    const data = drawer.detail.value;
    if (!data) return [];

    const typeConf = CONVERT_TYPE_MAP[data.convert_type];

    return [
      { label: "转换单号：", value: data.bill_no },
      { label: "转换仓库：", value: data.warehouse && data.warehouse.name },
      {
        label: "转换类型：",
        value: (typeConf && typeConf.label) || data.convert_type,
        type: "tag",
        theme: typeConf?.theme,
        variant: typeConf?.variant
      },
      { label: "创建人：", value: data.creator_info?.name },
      { label: "创建时间：", value: data.created_at },
      { label: "单据备注：", value: data.remark, span: 2, align: "top" }
    ];
  });

  // 转换商品明细：将接口 details 映射为表格展示行
  const goodsList = computed(() => {
    const data = drawer.detail.value;
    if (!data || !data.details) return [];

    return (data.details as BillDetailItem[]).map((item) => {
      const sku: SkuInfo = item.sku || {};
      const product: ProductInfo = item.product || {};
      const costPrice = toNumber(item.cost_price ?? sku.cost_price ?? sku.cost ?? 0);
      const quantity = toNumber(item.quantity);
      return {
        image: sku.image || product.image || "",
        name: product.name || sku.name || "",
        spec: sku.sku || "",
        code: sku.code || sku.bar_code_num || "",
        bar_code: sku.bar_code || "",
        product_bar_code: sku.product_bar_code || "",
        cost_price: toAmount(costPrice),
        count: quantity,
        subtotal: toAmount(item.amount ?? costPrice * quantity),
        // 明细凭证图片：images 为附件 ID 数组，图片地址取自同级 files（附件对象含 url）
        files: (item.files || []).map((file) => file.url).filter(Boolean)
      };
    });
  });

  // 汇总：商品种类 / 总件数 / 总金额（接口无 total_amount 字段，按明细 成本价 × 数量 累加）
  const summary = computed(() => {
    const data = drawer.detail.value;
    const details = (data && data.details) || [];
    return {
      category_count: details.length,
      goods_count: details.reduce((total: number, item: BillDetailItem) => total + toNumber(item.quantity), 0),
      total_amount: details
        .reduce((total: number, item: BillDetailItem) => {
          const sku: SkuInfo = item.sku || {};
          const costPrice = toNumber(item.cost_price ?? sku.cost_price ?? sku.cost ?? 0);
          return total + costPrice * toNumber(item.quantity);
        }, 0)
        .toFixed(2)
    };
  });

  // 商品表格列：对齐「正次转换详情」商品表头（无剩余库存列）
  const goodsColumns = [
    { field: "goods", name: "商品信息", minWidth: 180, slot: "goods" },
    { field: "spec", name: "规格", minWidth: 150, slot: "spec" },
    { field: "cost_price", name: "成本价", minWidth: 80 },
    { field: "count", name: "转换数量", minWidth: 80 },
    { field: "subtotal", name: "金额小计", minWidth: 80 },
    // className：让该列单元格取消 overflow 裁剪，缩略图右上角的张数角标才不会被切掉
    { field: "files", name: "凭证图片", minWidth: 90, slot: "files", className: "convert-files-cell" }
  ];

  return {
    ...drawer,

    basicFields,
    goodsList,
    summary,
    goodsColumns
  };
};
