/**
 * 库存查询模块共用的行数据取值工具
 * 列表 / 占用在途明细弹窗 / 台账抽屉均基于 stock 行（含 warehouse / product / sku 关联）取值
 *
 * 实际实现已统一到 inventory/utils.js，本文件仅做重导出以保持向后兼容。
 */

export {
  getGoodsName,
  getSpecName,
  getProductCode,
  getBarcode,
  getUnit,
  formatStock,
  getSpecInfo
} from "../utils";

// 向后兼容别名：getGoodsCode 等同于 getProductCode
export { getProductCode as getGoodsCode } from "../utils";
