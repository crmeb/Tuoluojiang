import { ref, computed } from "vue";

const toNumber = (val) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

/**
 * 盘点商品明细：列表 / 选择 / 汇总 / 行工厂 / 增删
 *
 * 行字段说明：unique（行键/去重）、sku_id（提交用规格 ID）为标识字段；
 * image / name / spec / code / bar_code / product_bar_code 为展示字段；
 * good_book_stock（良品账面库存）/ good_occupy（其中占用）/ defect_book_stock（残次品账面库存）
 * 为账面库存展示字段；real_good_stock（良品实盘）/ real_defect_stock（残次品实盘）/ remark
 * 对齐「新增盘点」接口 details。
 *
 * 注：选品组件（specifications）透传商品接口（products/attrs）按仓库过滤后的 SKU 原始字段，
 *     账面库存取 book_good_stock / book_defect_stock，占用取 occupy_stock；
 *     实盘数量默认等于账面库存（初始盈亏为 0），用户可在表格内编辑。
 */
export const useStocktakingGoods = () => {
  const goodsList = ref([]);
  const selectedGoods = ref([]);

  const handleSelectionChange = (rows) => {
    selectedGoods.value = rows;
  };

  const createGoodsRow = (item) => {
    const goodBookStock = toNumber(item.book_good_stock ?? item.good_stock);
    const defectBookStock = toNumber(item.book_defect_stock ?? item.defect_stock);
    return {
      unique: item.unique,
      sku_id: item.id,
      image: item.image,
      name: item.product_name || item.name || "",
      spec: item.sku || "",
      code: item.code || item.bar_code_num || "",
      bar_code: item.bar_code || "",
      product_bar_code: item.product_bar_code || "",
      // 账面库存（展示）
      good_book_stock: goodBookStock,
      good_occupy: toNumber(item.occupy_stock ?? 0),
      defect_book_stock: defectBookStock,
      // 实盘数量（可编辑），默认等于账面库存 -> 初始盈亏为 0
      real_good_stock: goodBookStock,
      real_defect_stock: defectBookStock,
      remark: ""
    };
  };

  // 编辑态回填：将「盘点详情」明细映射为表格行（实盘取已保存值，而非账面兜底）。
  // 详情明细未返回占用数量，good_occupy 以 0 兜底
  const createGoodsRowFromDetail = (item) => {
    const sku = item.sku || {};
    const product = item.product || {};
    const goodBookStock = toNumber(item.book_good_stock);
    const defectBookStock = toNumber(item.book_defect_stock);
    return {
      unique: item.unique || sku.unique || `sku_${item.sku_id ?? sku.id}`,
      sku_id: item.sku_id ?? sku.id,
      image: sku.image || product.image || "",
      name: product.name || sku.name || "",
      spec: sku.sku || "",
      code: sku.code || sku.bar_code_num || "",
      bar_code: sku.bar_code || "",
      product_bar_code: sku.product_bar_code || "",
      // 账面库存（展示）
      good_book_stock: goodBookStock,
      good_occupy: toNumber(item.occupy_stock ?? 0),
      defect_book_stock: defectBookStock,
      // 实盘数量（可编辑），编辑态取已保存值
      real_good_stock: toNumber(item.real_good_stock),
      real_defect_stock: toNumber(item.real_defect_stock),
      remark: item.remark || ""
    };
  };

  const setGoodsFromDetail = (details) => {
    goodsList.value = (details || []).map(createGoodsRowFromDetail);
  };

  // 表头「批量修改」：把输入数量应用到全部商品行的指定实盘字段
  // （field 取 real_good_stock / real_defect_stock，负数按 0 兜底）
  const batchFillStock = (field, value) => {
    const n = Math.max(toNumber(value), 0);
    goodsList.value.forEach((row) => {
      row[field] = n;
    });
  };

  // 单行良品盈亏 = 良品实盘 - 良品账面库存
  const getGoodDiff = (row) => toNumber(row.real_good_stock) - toNumber(row.good_book_stock);
  // 单行残次品盈亏 = 残次品实盘 - 残次品账面库存
  const getDefectDiff = (row) => toNumber(row.real_defect_stock) - toNumber(row.defect_book_stock);

  // 汇总：商品种类 / 盘盈总数（正向盈亏之和）/ 盘亏总数（负向盈亏绝对值之和）
  const summary = computed(() => {
    const kind = goodsList.value.length;
    let profit = 0;
    let loss = 0;
    goodsList.value.forEach((row) => {
      [getGoodDiff(row), getDefectDiff(row)].forEach((diff) => {
        if (diff > 0) profit += diff;
        else if (diff < 0) loss += -diff;
      });
    });
    return { kind, profit, loss };
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
    handleSelectionChange,
    setGoodsFromDetail,
    batchFillStock,
    getGoodDiff,
    getDefectDiff,
    summary,
    handleProductSelect,
    clearGoods,
    removeGoods,
    batchRemove
  };
};
