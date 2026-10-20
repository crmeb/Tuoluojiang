import { ref, computed } from "vue";
import type { ProductSkuItem } from "../../types";
import { CONVERT_TYPE } from "../constants";

const toNumber = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

/**
 * 正次转换商品明细：列表 / 选择 / 汇总 / 行工厂 / 增删
 *
 * 行字段说明：unique（行键 / 去重）、sku_id（提交用规格 ID）为标识字段；
 * image / name / spec / code / bar_code / product_bar_code / cost_price 为展示字段；
 * good_stock（良品剩余库存）/ defect_stock（残次剩余库存）随转换类型择一展示；
 * quantity（转换数量）/ remark / images 对齐提交 details，其中 images 为行内图片附件
 * （每项含 url / id，url 用于回显，提交时取 id 拼成接口要求的附件 ID 数组）。
 *
 * 注：通用选品组件（specifications）仅返回商品售价 / 总库存等字段，未提供成本价与按
 *     良品 / 残次拆分的剩余库存，此处按可能的字段名做兜底映射（缺失以售价 / 总库存 / 0 兜底）。
 */
export const useConvertGoods = () => {
  type GoodsRow = ReturnType<typeof createGoodsRow>;
  const goodsList = ref<GoodsRow[]>([]);
  const selectedGoods = ref<GoodsRow[]>([]);

  const handleSelectionChange = (rows: GoodsRow[]) => {
    selectedGoods.value = rows;
  };

  const createGoodsRow = (item: ProductSkuItem) => ({
    unique: item.unique,
    sku_id: item.id,
    image: item.image,
    name: item.product_name || item.name || "",
    spec: item.sku || "",
    code: item.code || item.bar_code_num || "",
    bar_code: item.bar_code || "",
    product_bar_code: item.product_bar_code || "",
    cost_price: item.cost_price ?? item.cost ?? item.price ?? "0.00",
    good_stock: toNumber(item.good_stock ?? item.stock ?? 0),
    defect_stock: toNumber(item.defect_stock ?? 0),
    quantity: 1,
    images: [],
    remark: ""
  });

  // 当前转换类型对应的剩余库存：良品转残次取良品库存，残次转良品取残次库存
  const getRowStock = (row: GoodsRow, convertType: number) =>
    convertType === CONVERT_TYPE.DEFECT_TO_GOOD ? toNumber(row.defect_stock) : toNumber(row.good_stock);

  // 单行金额小计 = 成本价 * 转换数量
  const getRowSubtotal = (row: GoodsRow) => (toNumber(row.cost_price) * toNumber(row.quantity)).toFixed(2);

  // 汇总：商品种类 / 总件数 / 总金额（成本价 × 转换数量之和）
  const summary = computed(() => {
    const kind = goodsList.value.length;
    let count = 0;
    let amount = 0;
    goodsList.value.forEach((row) => {
      count += toNumber(row.quantity);
      amount += toNumber(row.cost_price) * toNumber(row.quantity);
    });
    return { kind, count, amount: amount.toFixed(2) };
  });

  // 选择商品弹窗回调：合并选中项，保留已编辑行的数值，移除被取消勾选的行
  const handleProductSelect = (selectedList: ProductSkuItem[], ids: string[]) => {
    const existMap = new Map(goodsList.value.map((row) => [row.unique, row]));
    const seen = new Set<string>();
    const next: GoodsRow[] = [];
    selectedList.forEach((item) => {
      if (!item.unique || seen.has(item.unique) || !ids.includes(item.unique)) return;
      seen.add(item.unique);
      next.push(existMap.get(item.unique) || createGoodsRow(item));
    });
    goodsList.value = next;
  };

  const clearGoods = () => {
    goodsList.value = [];
  };

  const removeGoods = (index: number) => {
    goodsList.value.splice(index, 1);
  };

  const batchRemove = () => {
    if (!selectedGoods.value.length) return;
    const removeSet = new Set(selectedGoods.value.map((row) => row.unique));
    goodsList.value = goodsList.value.filter((row) => !removeSet.has(row.unique));
    selectedGoods.value = [];
  };

  return {
    goodsList,
    selectedGoods,
    handleSelectionChange,
    getRowStock,
    getRowSubtotal,
    summary,
    handleProductSelect,
    clearGoods,
    removeGoods,
    batchRemove
  };
};
