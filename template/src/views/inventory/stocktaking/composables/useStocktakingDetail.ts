import { computed } from "vue";
import type { BillDetailItem, SkuInfo, ProductInfo } from "../../types";
import { useDetailDrawer } from "@/composables/useDetailDrawer";
import { checkDetailApi } from "@/api/inventory";
import { CHECK_STATUS_MAP } from "../constants";

// 数值型库存字段后端可能以字符串返回（如 "1.0000"），统一转数字
const toNumber = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

export const useStocktakingDetail = () => {
  const drawer = useDetailDrawer(checkDetailApi);

  // 基本信息字段：按「左右两列」逐行排列，单据备注跨整行
  const basicFields = computed(() => {
    const data = drawer.detail.value;
    if (!data) return [];

    const statusConf = CHECK_STATUS_MAP[data.status];

    return [
      { label: "盘点单号：", value: data.bill_no },
      { label: "盘点仓库：", value: data.warehouse && data.warehouse.name },
      {
        label: "盘点状态：",
        value: (statusConf && statusConf.label) || data.status,
        type: "tag",
        theme: statusConf?.theme,
        variant: statusConf?.variant
      },
      { label: "创建人：", value: data.creator_info?.name },
      { label: "创建时间：", value: data.created_at },
      { label: "盘点结束时间：", value: data.finished_at },
      { label: "单据备注：", value: data.remark, span: 2, align: "top" }
    ];
  });

  // 盘点商品明细：将接口 details 映射为表格展示行（账面 / 实盘 / 盈亏）
  const goodsList = computed(() => {
    const data = drawer.detail.value;
    if (!data || !data.details) return [];

    return (data.details as BillDetailItem[]).map((item) => {
      const sku: SkuInfo = item.sku || {};
      const product: ProductInfo = item.product || {};
      const goodBook = toNumber(item.book_good_stock);
      const defectBook = toNumber(item.book_defect_stock);
      const realGood = toNumber(item.real_good_stock);
      const realDefect = toNumber(item.real_defect_stock);
      return {
        image: sku.image || "",
        name: product.name || sku.name || "",
        spec: sku.sku || "",
        bar_code: sku.bar_code || "",
        product_bar_code: sku.product_bar_code || "",
        price: toNumber(sku.price),
        good_book_stock: goodBook,
        real_good_stock: realGood,
        good_diff: realGood - goodBook,
        defect_book_stock: defectBook,
        real_defect_stock: realDefect,
        defect_diff: realDefect - defectBook,
        remark: item.remark || ""
      };
    });
  });

  // 汇总：盘亏金额（负向盈亏 × 售价，负数）/ 盘盈金额（正向盈亏 × 售价）/ 总金额（两者之和）。
  // 良品与残次品盈亏均计入，接口未返回金额字段，以 sku.price 为单价前端累加
  const summary = computed(() => {
    let profitAmount = 0;
    let lossAmount = 0;
    goodsList.value.forEach((row) => {
      [row.good_diff, row.defect_diff].forEach((diff) => {
        if (diff > 0) profitAmount += diff * row.price;
        else if (diff < 0) lossAmount += diff * row.price;
      });
    });
    return {
      lossAmount,
      profitAmount,
      totalAmount: profitAmount + lossAmount
    };
  });

  // 商品表格列：对齐「新增盘点」商品明细（账面 / 实盘 / 盈亏，良品与残次品各一组）
  const goodsColumns = [
    { field: "goods", name: "商品信息", minWidth: 200, slot: "goods" },
    { field: "spec", name: "规格名称", minWidth: 120, slot: "spec" },
    { field: "good_book_stock", name: "良品账面库存", minWidth: 110 },
    { field: "real_good_stock", name: "良品实盘", minWidth: 90 },
    { field: "good_diff", name: "良品盈亏", minWidth: 90, slot: "good_diff" },
    { field: "defect_book_stock", name: "残次品账面库存", minWidth: 120 },
    { field: "real_defect_stock", name: "残次品实盘", minWidth: 100 },
    { field: "defect_diff", name: "残次品盈亏", minWidth: 100, slot: "defect_diff" }
  ];

  return {
    ...drawer,

    basicFields,
    goodsList,
    summary,
    goodsColumns
  };
};
