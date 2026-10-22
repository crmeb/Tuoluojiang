/**
 * 库存模块共用的行数据取值工具
 *
 * 兼容两种行数据结构：
 *   嵌套格式（stock/setting 列表）：row.product + row.sku + row.warehouse
 *   扁平格式（create/detail 商品明细）：row.name + row.spec + row.code + row.bar_code + row.product_bar_code
 *
 * 字段映射（按接口约定）：
 *   产品编码 = bar_code
 *   条码     = product_bar_code
 */

// 商品名称
export const getGoodsName = (row) => {
  if (row.product && row.product.name) return row.product.name;
  if (row.sku && row.sku.name) return row.sku.name;
  if (row.name) return row.name;
  return "--";
};

// 规格名称：优先取嵌套 sku.sku，其次扁平 spec，回落「单规格」
export const getSpecName = (row) => {
  if (row.sku && row.sku.sku) return row.sku.sku;
  if (row.spec) return row.spec;
  return "单规格";
};

// 产品编码（接口字段 bar_code）
export const getProductCode = (row) => {
  // 嵌套格式：row.sku.bar_code
  if (row.sku && row.sku.bar_code) return row.sku.bar_code;
  // 扁平格式：row.bar_code
  if (row.bar_code) return row.bar_code;
  // 兜底：SKU 编码 → 商品编号
  if (row.sku && row.sku.code) return row.sku.code;
  if (row.product && row.product.number) return row.product.number;
  return "--";
};

// 条码（接口字段 product_bar_code）
export const getBarcode = (row) => {
  if (row.sku && row.sku.product_bar_code) return row.sku.product_bar_code;
  if (row.product_bar_code) return row.product_bar_code;
  return "--";
};

// 商品单位
export const getUnit = (row) => {
  return (row.product && row.product.unit_name) || "";
};

// 库存数值："2.0000" → 2
export const formatStock = (val) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

/**
 * 规格信息行辅助：返回 { specName, productCode, barcode }
 * 用于 create/detail 视图中统一的「规格 / 编码 / 条码」三行展示
 */
export const getSpecInfo = (row) => ({
  specName: getSpecName(row),
  productCode: getProductCode(row),
  barcode: getBarcode(row)
});
