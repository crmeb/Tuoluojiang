export const getTotalAmount = (goods) => {
  return goods.reduce((total, item) => total + Number(item.quantity) * Number(item.sku?.price || 0), 0);
}