export const getTotalAmount = (goods: Array<{ quantity?: number | string; sku?: { price?: number | string } | null }>) => {
  return goods.reduce((total, item) => total + Number(item.quantity) * Number(item.sku?.price || 0), 0);
}