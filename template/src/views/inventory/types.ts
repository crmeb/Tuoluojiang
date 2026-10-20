/**
 * 仓储模块共用类型
 */
export type { FormInstance } from "element-plus";

type NumLike = number | string;

/**
 * 选品弹窗 / 订单商品 / 库存列表回传的 SKU 项。
 * 不同来源字段不完全一致，这里列出各业务行工厂会读取的字段。
 */
export interface ProductSkuItem {
  id?: number;
  unique: string;
  sku_id?: number;
  image?: string;
  product_name?: string;
  name?: string;
  sku?: string;
  code?: string;
  bar_code?: string;
  bar_code_num?: string;
  product_bar_code?: string;
  stock?: NumLike;
  available_stock?: NumLike | null;
  good_stock?: NumLike | null;
  defect_stock?: NumLike | null;
  occupy_stock?: NumLike;
  book_good_stock?: NumLike;
  book_defect_stock?: NumLike;
  price?: NumLike;
  cost_price?: NumLike;
  cost?: NumLike;
  count?: NumLike;
}

/** 单据列表行，操作按钮只用到 id / 单号 */
export interface BillRow {
  id: number;
  bill_no: string;
}

/** 库存列表行：商品 + SKU + 仓库关联，库存字段为字符串数值 */
export interface StockRow {
  id?: number;
  warehouse_id?: number;
  sku_id?: number;
  warehouse?: { name?: string } | null;
  product?: { name?: string; number?: string; unit_name?: string; category?: { name?: string } | null } | null;
  sku?: { name?: string; sku?: string; bar_code?: string; code?: string; product_bar_code?: string } | null;
  good_stock?: NumLike;
  defect_stock?: NumLike;
  occupy_stock?: NumLike;
  transit_stock?: NumLike;
}

/** 仓库 / 库管等基础资料行 */
export interface NamedRow {
  id: number;
  name: string;
  status?: number | string;
}

/** 搜索栏回传：查询条件对象，或重置标记 */
export type SearchValue = Record<string, any> | "reset";

/** 数值型字段后端可能以字符串返回，统一转数字，非法值归 0 */
export type { NumLike };

/** 单据明细关联的 SKU */
export interface SkuInfo {
  id?: number;
  unique?: string;
  image?: string;
  name?: string;
  sku?: string;
  code?: string;
  bar_code?: string;
  bar_code_num?: string;
  product_bar_code?: string;
  price?: NumLike;
  cost_price?: NumLike;
  cost?: NumLike;
}

/** 单据明细关联的商品 */
export interface ProductInfo {
  id?: number;
  name?: string;
  image?: string;
  number?: string;
  unit_name?: string;
}

/** 单据详情接口返回的明细项（details[]） */
export interface BillDetailItem {
  unique?: string;
  sku_id?: number;
  sku?: SkuInfo | null;
  product?: ProductInfo | null;
  stock_quality?: number;
  quantity?: NumLike;
  unit_price?: NumLike;
  cost_price?: NumLike;
  gift_quantity?: NumLike;
  amount?: NumLike;
  remark?: string;
  images?: string[];
  /** 凭证图片附件 */
  files?: Array<{ url?: string }>;
  book_good_stock?: NumLike;
  book_defect_stock?: NumLike;
  real_good_stock?: NumLike;
  real_defect_stock?: NumLike;
  occupy_stock?: NumLike;
  good_stock?: NumLike | null;
  defect_stock?: NumLike | null;
}
