import { ref, computed } from "vue";
import type { ProductSkuItem, BillDetailItem, SkuInfo, ProductInfo, NumLike } from "../../types";
import { warehouseStockListApi } from "@/api/inventory";

// 库存属性（对齐「新增入库」接口 stock_quality 枚举，调拨明细缺失该字段时以入库为准）
export const STOCK_QUALITY = {
  GOOD: 1, // 良品
  DEFECT: 2 // 次品
};

export const STOCK_QUALITIES = [
  { label: "良品", value: STOCK_QUALITY.GOOD },
  { label: "次品", value: STOCK_QUALITY.DEFECT }
];

const toNumber = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

// 库存数值：接口未返回时返回 null 表示「未知」，与 0（无库存）区分
const toStock = (val: unknown) => {
  if (val === null || val === undefined || val === "") return null;
  const n = Number(val);
  return Number.isFinite(n) ? n : null;
};

/**
 * 调拨商品明细：列表 / 选择 / 汇总 / 行工厂 / 增删
 *
 * 行字段说明：unique（行键/去重）、sku_id（提交用规格 ID）为标识字段；
 * image / name / spec / code / bar_code / product_bar_code / price 为展示字段；
 * good_stock（良品可用库存）/ defect_stock（次品库存）随库存属性择一展示；
 * stock_quality / quantity / remark 对齐提交 details（quantity 为调拨数量）。
 * 注：调拨无「单价」编辑，金额小计与总售价均按商品售价 × 调拨数量计算；
 *     调拨数量不能大于剩余库存（剩余库存取自调出仓库）。
 */
export const useTransferGoods = () => {
  type GoodsRow = ReturnType<typeof createGoodsRow>;
  const goodsList = ref<GoodsRow[]>([]);
  const selectedGoods = ref<GoodsRow[]>([]);
  const stockQualities = STOCK_QUALITIES;

  const handleSelectionChange = (rows: GoodsRow[]) => {
    selectedGoods.value = rows;
  };

  // 库存：选品接口按调出仓库返回 available_stock（良品可用库存 = 良品库存 - 占用）与 defect_stock，
  // 二者均为 null 时表示库存未知（展示 "-"，不做数量上限约束）
  const createGoodsRow = (item: ProductSkuItem) => ({
    unique: item.unique,
    sku_id: item.sku_id ?? item.id,
    image: item.image,
    name: item.product_name || item.name || "",
    spec: item.sku || "",
    code: item.code || item.bar_code_num || "",
    bar_code: item.bar_code || "",
    product_bar_code: item.product_bar_code || "",
    good_stock: toStock(item.available_stock),
    defect_stock: toStock(item.defect_stock),
    price: item.price ?? "0.00",
    stock_quality: STOCK_QUALITY.GOOD,
    quantity: 1,
    remark: ""
  });

  // 编辑态回填：将「调拨详情」明细映射为表格行。
  // 详情接口不返回库存字段，good_stock / defect_stock 先置 null（未知），
  // 再由 fillStockFromWarehouse 按调出仓库补齐
  const createGoodsRowFromDetail = (item: BillDetailItem) => {
    const sku: SkuInfo = item.sku || {};
    const product: ProductInfo = item.product || {};
    return {
      // 行键沿用 SKU 的 unique，保证再次打开选品弹窗时能与已选项匹配上
      unique: sku.unique || `sku_${item.sku_id ?? sku.id}`,
      sku_id: item.sku_id ?? sku.id,
      image: sku.image || "",
      name: product.name || "",
      spec: sku.sku || "",
      code: sku.code || sku.bar_code_num || "",
      bar_code: sku.bar_code || "",
      product_bar_code: sku.product_bar_code || "",
      good_stock: null,
      defect_stock: null,
      price: sku.price ?? "0.00",
      stock_quality: item.stock_quality ?? STOCK_QUALITY.GOOD,
      quantity: toNumber(item.quantity) || 1,
      remark: item.remark || ""
    };
  };

  const setGoodsFromDetail = (details: BillDetailItem[] | null | undefined) => {
    goodsList.value = (details || []).map(createGoodsRowFromDetail);
  };

  // 回填后补齐剩余库存：库存列表接口 sku_id 仅支持单值，故按明细逐条并发查询后按 sku_id 回写
  const fillStockFromWarehouse = async (warehouseId: number | string) => {
    if (!warehouseId || !goodsList.value.length) return;
    const skuIds = goodsList.value.map((row) => row.sku_id).filter(Boolean);
    const stockList = await Promise.all(
      skuIds.map((skuId) =>
        warehouseStockListApi({ page: 1, limit: 1, warehouse_id: warehouseId, sku_id: skuId })
          .then((res) => (res.data && res.data.list && res.data.list[0]) || null)
          .catch(() => null)
      )
    );
    const stockMap = new Map<number | undefined, { good_stock?: NumLike; occupy_stock?: NumLike; defect_stock?: NumLike }>();
    stockList.forEach((stock, index) => {
      if (stock) stockMap.set(skuIds[index], stock);
    });

    goodsList.value.forEach((row) => {
      const stock = stockMap.get(row.sku_id);
      if (!stock) return;
      // 良品可用库存 = 良品库存 - 占用库存，与选品接口 available_stock 口径一致
      row.good_stock = Math.max(toNumber(stock.good_stock) - toNumber(stock.occupy_stock), 0);
      row.defect_stock = toNumber(stock.defect_stock);
    });
  };

  // 当前库存属性对应的剩余库存：良品取可用库存，次品取残次库存；null 表示接口未返回库存
  const getRowStock = (row: GoodsRow) =>
    row.stock_quality === STOCK_QUALITY.DEFECT ? row.defect_stock : row.good_stock;

  // 剩余库存展示：0 正常显示，仅库存未知时占位
  const getRowStockText = (row: GoodsRow) => {
    const stock = getRowStock(row);
    return stock === null ? "-" : stock;
  };

  // 调拨数量上限：库存未知时不限制
  const getRowMax = (row: GoodsRow) => {
    const stock = getRowStock(row);
    return stock === null ? Infinity : stock;
  };

  // 单行金额小计 = 售价 * 调拨数量
  const getRowSubtotal = (row: GoodsRow) => (toNumber(row.price) * toNumber(row.quantity)).toFixed(2);

  // 汇总：商品种类 / 总件数 / 总售价
  const summary = computed(() => {
    const kind = goodsList.value.length;
    let count = 0;
    let amount = 0;
    goodsList.value.forEach((row) => {
      count += toNumber(row.quantity);
      amount += toNumber(row.price) * toNumber(row.quantity);
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
    stockQualities,
    handleSelectionChange,
    setGoodsFromDetail,
    fillStockFromWarehouse,
    getRowStock,
    getRowStockText,
    getRowMax,
    getRowSubtotal,
    summary,
    handleProductSelect,
    clearGoods,
    removeGoods,
    batchRemove
  };
};
