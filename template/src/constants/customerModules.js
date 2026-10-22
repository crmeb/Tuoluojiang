export const CUSTOMER_MODULE_KEYS = {
  LEADS:       'leads',
  CUSTOMER:    'customer',
  OPPORTUNITY: 'opportunity',
  CONTRACT:    'contract',
  ORDER:       'order',
  INVOICE:     'invoice',
  LIAISON:     'liaison',
}

// 客户模块与组件路径的映射关系
export const CUSTOMER_MODULE_COMPONENTS = {
  [CUSTOMER_MODULE_KEYS.LEADS]: [
    'customer/clue/index',       // 线索管理
    'customer/clue/pool',        // 线索池
  ],
  [CUSTOMER_MODULE_KEYS.CUSTOMER]: [
    'customer/list/index',       // 客户管理
    'customer/list/public',      // 公海池
    'customer/setup/label',      // 客户标签
  ],
  [CUSTOMER_MODULE_KEYS.OPPORTUNITY]: [
    'customer/opportunityManagement/index', // 商机管理
  ],
  [CUSTOMER_MODULE_KEYS.CONTRACT]: [
    'customer/signing/index',     // 合同签约
    'customer/whole/index',       // 合同收支
    'customer/setup/contractType/index', // 合同分类
  ],
  [CUSTOMER_MODULE_KEYS.ORDER]: [
    'customer/contract/index',    // 合同订单
    'customer/turnover/index',    // 业绩统计
    'customer/product/index',    // 产品
    'customer/product/addProduct', // 添加产品
    'customer/product/category', // 产品分类
  ],
  [CUSTOMER_MODULE_KEYS.INVOICE]: [
    'customer/invoice/index',    // 发票管理
    'fd/invoice/pending',        // 待开发票
  ],
  [CUSTOMER_MODULE_KEYS.LIAISON]: [
    'customer/liaison/index',    // 联系人
  ],
}
