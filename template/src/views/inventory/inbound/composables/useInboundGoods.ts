import { ref, computed } from "vue";
import type { ProductSkuItem } from "../../types";

// 库存属性（对齐「新增入库」接口 stock_quality 枚举）
export const STOCK_QUALITIES = [
  { label: "良品", value: 1 },
  { label: "次品", value: 2 }
];

const toNumber = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

/**
 * 入库商品明细：列表 / 选择 / 汇总 / 行工厂 / 增删
 *
 * 行字段说明：unique（行键/去重）、sku_id（提交用规格 ID）为标识字段；
 * image / name / spec / bar_code / product_bar_code / stock / price 为展示字段；
 * stock_quality / unit_price / quantity / gift_quantity / remark 对齐接口 details。
 */
export const useInboundGoods = () => {
  type GoodsRow = ReturnType<typeof createGoodsRow>;
  const goodsList = ref<GoodsRow[]>([]);
  const selectedGoods = ref<GoodsRow[]>([]);
  const stockQualities = STOCK_QUALITIES;
  // 关联订单商品销量映射：unique → count；订单回填与手动选品共用 unique，均从此表读取销量
  const orderCountMap = ref(new Map());

  const handleSelectionChange = (rows: GoodsRow[]) => {
    selectedGoods.value = rows;
  };

  // 普通选品无 count 字段 → 数量默认 1；订单回填带 count → 取订单数量
  const createGoodsRow = (item: ProductSkuItem) => ({
    unique: item.unique,
    sku_id: item.sku_id,
    image: item.image,
    name: item.product_name || item.name || "",
    spec: item.sku || "",
    bar_code: item.bar_code || "",
    product_bar_code: item.product_bar_code || "",
    stock: item.stock ?? "-",
    price: item.price ?? "0.00",
    stock_quality: 1,
    unit_price: toNumber(item.price),
    quantity: toNumber(item.count) || 1,
    gift_quantity: 0,
    remark: ""
  });

  // 单行金额小计 = 入库单价 * 入库数量
  const getRowSubtotal = (row: GoodsRow) => (toNumber(row.unit_price) * toNumber(row.quantity)).toFixed(2);

  // 关联订单销量：未选订单、或该商品不在订单商品中时展示 --
  const getOrderCount = (row: GoodsRow) => {
    const count = orderCountMap.value.get(row.unique);
    return count === undefined ? "--" : count;
  };

  // 汇总：商品种类 / 总件数（入库数量 + 赠送数量） / 总金额
  // 「调拨入库」无赠送数量列，但切换入库类型会清空明细，不存在残留的赠送数量
  const summary = computed(() => {
    const kind = goodsList.value.length;
    let count = 0;
    let amount = 0;
    goodsList.value.forEach((row) => {
      count += toNumber(row.quantity) + toNumber(row.gift_quantity);
      amount += toNumber(row.unit_price) * toNumber(row.quantity);
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

  // 订单回填：按订单商品重建明细列表，并同步销量映射供手动选品行查询
  const setGoodsFromOrder = (products: ProductSkuItem[] = []) => {
    const list = products.filter((item) => item.unique);
    orderCountMap.value = new Map(list.map((item) => [item.unique, toNumber(item.count)]));
    goodsList.value = list.map(createGoodsRow);
  };

  const clearGoods = () => {
    goodsList.value = [];
    orderCountMap.value = new Map();
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
    getRowSubtotal,
    getOrderCount,
    summary,
    handleProductSelect,
    setGoodsFromOrder,
    clearGoods,
    removeGoods,
    batchRemove
  };
};
