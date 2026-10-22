import { ref, computed } from "vue";

// 库存属性枚举（对齐「新增入库」接口 stock_quality）
export const STOCK_QUALITY = {
  GOOD: 1, // 良品
  DEFECT: 2 // 次品
};

export const STOCK_QUALITIES = [
  { label: "良品", value: STOCK_QUALITY.GOOD },
  { label: "次品", value: STOCK_QUALITY.DEFECT }
];

const toNumber = (val) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

// 库存数值：接口未返回（如订单回填的商品明细）时返回 null 表示「未知」，与 0（无库存）区分
const toStock = (val) => {
  if (val === null || val === undefined || val === "") return null;
  const n = Number(val);
  return Number.isFinite(n) ? n : null;
};

/**
 * 出库商品明细：列表 / 选择 / 汇总 / 行工厂 / 增删
 *
 * 行字段说明：unique（行键/去重）、sku_id（提交用规格 ID）为标识字段；
 * image / name / spec / bar_code / product_bar_code / price 为展示字段；
 * good_stock（良品可用库存）/ defect_stock（次品库存）随库存属性择一展示；
 * stock_quality / unit_price / quantity / remark 对齐提交 details。
 * 注：出库明细无「赠送数量」，出库数量不能大于剩余库存。
 */
export const useOutboundGoods = () => {
  const goodsList = ref([]);
  const selectedGoods = ref([]);
  const stockQualities = STOCK_QUALITIES;

  const handleSelectionChange = (rows) => {
    selectedGoods.value = rows;
  };

  // 普通选品无 count 字段 → 数量默认 1；订单回填带 count → 取订单数量。
  // 库存：选品接口按仓库返回 available_stock（良品可用库存 = 良品库存 - 占用）与 defect_stock；
  // 订单商品接口不返回库存字段，两者均为 null（展示 "-"，不做数量上限约束）
  const createGoodsRow = (item) => ({
    unique: item.unique,
    sku_id: item.sku_id,
    image: item.image,
    name: item.product_name || item.name || "",
    spec: item.sku || "",
    bar_code: item.bar_code || "",
    product_bar_code: item.product_bar_code || "",
    good_stock: toStock(item.available_stock),
    defect_stock: toStock(item.defect_stock),
    price: item.price ?? "0.00",
    stock_quality: STOCK_QUALITY.GOOD,
    unit_price: toNumber(item.price),
    quantity: toNumber(item.count) || 1,
    remark: ""
  });

  // 当前库存属性对应的剩余库存：良品取可用库存，次品取残次库存；null 表示接口未返回库存
  const getRowStock = (row) =>
    row.stock_quality === STOCK_QUALITY.DEFECT ? row.defect_stock : row.good_stock;

  // 剩余库存展示：0 正常显示，仅库存未知时占位
  const getRowStockText = (row) => {
    const stock = getRowStock(row);
    return stock === null ? "-" : stock;
  };

  // 出库数量上限：库存未知时不限制
  const getRowMax = (row) => {
    const stock = getRowStock(row);
    return stock === null ? Infinity : stock;
  };

  // 单行金额小计 = 出库单价 * 出库数量
  const getRowSubtotal = (row) => (toNumber(row.unit_price) * toNumber(row.quantity)).toFixed(2);

  // 汇总：商品种类 / 总件数 / 总金额
  const summary = computed(() => {
    const kind = goodsList.value.length;
    let count = 0;
    let amount = 0;
    goodsList.value.forEach((row) => {
      count += toNumber(row.quantity);
      amount += toNumber(row.unit_price) * toNumber(row.quantity);
    });
    return { kind, count, amount: amount.toFixed(2) };
  });

  // 选择商品弹窗回调：合并选中项，保留已编辑行的数值，移除被取消勾选的行
  const handleProductSelect = (selectedList, ids) => {
    const existMap = new Map(goodsList.value.map((row) => [row.unique, row]));
    const seen = new Set();
    const next = [];
    selectedList.forEach((item) => {
      if (!item.unique || seen.has(item.unique) || !ids.includes(item.unique)) return;
      seen.add(item.unique);
      next.push(existMap.get(item.unique) || createGoodsRow(item));
    });
    goodsList.value = next;
  };

  // 订单回填：按订单商品重建明细列表
  const setGoodsFromOrder = (products = []) => {
    goodsList.value = products.filter((item) => item.unique).map(createGoodsRow);
  };

  const clearGoods = () => {
    goodsList.value = [];
  };

  const removeGoods = (index) => {
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
    stockQualities,
    handleSelectionChange,
    getRowStock,
    getRowStockText,
    getRowMax,
    getRowSubtotal,
    summary,
    handleProductSelect,
    setGoodsFromOrder,
    clearGoods,
    removeGoods,
    batchRemove
  };
};
