import dayjs from 'dayjs'



/**
 * 订单状态的文字判断
 * @param {Object} row
 * @returns {String}
 */
/** 订单行，只关心合同状态 */
interface ContractRow {
  contract_status?: number | string
}

export const getContractText = (row: ContractRow) => {
  let str = ''
  if (row.contract_status == '0') {
    str = '未开始'
  } else if (row.contract_status == '1') {
    str = '进行中'
  } else if (row.contract_status == '2') {
    str = '已结束'
  } else {
    str = '异常订单'
  }
  return str
}

/**
 * 订单状态按钮类型判断
 * @param {Object} row
 * @returns {String}
 */
export const getContractTagType = (row: ContractRow) => {
  let tagType: string | null = null
  if (row.contract_status == '0') {
    tagType = 'warning'
  } else if (row.contract_status == '1') {
    tagType = ''
  } else if (row.contract_status == '2') {
    tagType = 'info'
  } else {
    tagType = 'danger'
  }
  return tagType
}

/**
 * 根据发票状态判断的返回文字
 * * @param {Number} status
 * @returns {String}
 */
let invoiceText: Record<string, string> = {
  0: '待审核',
  1: '待开票',
  2: '已拒绝',
  3: '撤回开票',
  '-1': '已冲红',
  4: '申请冲红',
  5: '已开票'
}
export const getInvoiceText = (status: number | string) => {
  return invoiceText[status]
}

/**
 * 根据发票状态判断的返回颜色 class
 * * @param {Number} status
 * @returns {String}
 */
export const getInvoiceClassName = (status: number) => {
  let className = ''
  if (status === -1 || status === 6) {
    className = 'red'
  } else if (status === 0 ) {
    className = 'blue'
  } else if(status === 3){
 className = 'gray'
  } else if (status === 2 || status === 4) {
    className = 'red'
  } else if (status === 5  ) {
    className = 'green'
  }
  else if (status === 1) {
    className = 'yellow'
  }
  return className
}

/**
 * 获取发票名称
 * @param {Number} status
 * @returns {String}
 */
const invoiceType: Record<string, string> = {
  1: '个人普通发票',
  2: '企业普通发票',
  3: '企业专用发票'
}
export const getInvoiceType = (status: number | string) => {
  return invoiceType[status]
}


/**
 * 发票审核状态筛选下拉框
 */
export const selectInvoiceFd = [
  { label: '待开票', value: 1 },
  { label: '开票撤回', value: 3 },
  { label: '申请冲红', value: 4 },
  { label: '已开票', value: 5 },
  { label: '已冲红', value: -1 }
]
