/**
 * Excel 公式与单元格类型互转工具。
 *
 * 1) 公式：ExcelJS 写入时不能带前导 '='，LuckyExcel 导入会再补 '='。
 * 2) 数字：必须以 Number 写入 ExcelJS，并带上 numFmt；否则会落成文本(t="s")，
 *    再次打开后 ct.t 变成文本，公式无法正常计算。
 */

/** LuckySheet 单元格格式 */
export interface LuckySheetCellType {
  t?: string
  fa?: string
  /** 富文本片段 */
  s?: Array<{ v?: unknown } | null>
}

/** LuckySheet 单元格值对象（cell.v） */
export interface LuckySheetCell {
  v?: unknown
  f?: unknown
  ct?: LuckySheetCellType
  qp?: unknown
  [key: string]: unknown
}

/** 写入 ExcelJS 的单元格值与数字格式 */
export interface ExcelJsCellValue {
  value: unknown
  numFmt?: string
}

/**
 * 将 LuckySheet 公式转为 ExcelJS 可写入的公式（去掉前导 '='）。
 *
 * @param {unknown} formula LuckySheet 单元格 f 字段
 * @returns {string}
 */
export function toExcelJsFormula(formula: unknown): string {
  if (formula == null) {
    return ''
  }
  return String(formula).replace(/^=+/, '')
}

/**
 * 规范 LuckySheet 公式为单个前导 '='，用于打开文件或保存前清洗。
 *
 * @param {unknown} formula
 * @returns {string}
 */
export function normalizeLuckySheetFormula(formula: unknown): string {
  if (formula == null || formula === '') {
    return ''
  }
  return `=${toExcelJsFormula(formula)}`
}

/**
 * 是否为纯文本格式单元格。
 *
 * @param {{ ct?: { t?: string, fa?: string } } | null | undefined} cell
 * @returns {boolean}
 */
export function isLuckySheetTextCell(cell: LuckySheetCell | null | undefined): boolean {
  const ct = cell && cell.ct
  if (!ct) {
    return false
  }
  return ct.t === 's' || ct.fa === '@'
}

/**
 * 判断值是否可安全转为数字。
 *
 * @param {unknown} value
 * @returns {boolean}
 */
export function isNumericLike(value: unknown): boolean {
  if (typeof value === 'number') {
    return Number.isFinite(value)
  }
  if (typeof value !== 'string') {
    return false
  }
  const text = value.trim()
  if (!text) {
    return false
  }
  return /^-?\d+(\.\d+)?([eE][+-]?\d+)?$/.test(text)
}

/**
 * 将 LuckySheet 单元格转为 ExcelJS 的 value / numFmt。
 *
 * @param {Record<string, any> | null | undefined} cell
 * @returns {{ value: any, numFmt?: string }}
 */
export function toExcelJsCellValue(cell: LuckySheetCell | null | undefined): ExcelJsCellValue {
  if (!cell) {
    return { value: '' }
  }

  const ct: LuckySheetCellType = cell.ct || {}
  const isText = isLuckySheetTextCell(cell)
  let numFmt: string | undefined
  if (ct.fa && ct.fa !== 'General') {
    numFmt = ct.fa
  }

  if (cell.f) {
    let result = cell.v
    if (!isText && isNumericLike(result)) {
      result = Number(result)
    }
    return {
      value: { formula: toExcelJsFormula(cell.f), result },
      numFmt: isText ? '@' : numFmt
    }
  }

  if (!cell.v && ct.s && Array.isArray(ct.s)) {
    return {
      value: ct.s.map((item) => (item && item.v != null ? item.v : '')).join('')
    }
  }

  if (isText) {
    return {
      value: cell.v == null ? '' : String(cell.v),
      numFmt: '@'
    }
  }

  if (isNumericLike(cell.v) && ct.t !== 's') {
    return {
      value: Number(cell.v),
      numFmt
    }
  }

  return {
    value: cell.v,
    numFmt: numFmt && ct.t === 'd' ? numFmt : numFmt
  }
}

/**
 * 规范 LuckyExcel 导入后的单元格值：数字转 Number，并补齐 ct.t='n'。
 * 会原地修改 cellValue。
 *
 * @param {Record<string, any> | null | undefined} cellValue LuckySheet cell.v 对象
 * @returns {Record<string, any> | null | undefined}
 */
export function normalizeLuckySheetCellValue<T extends LuckySheetCell | null | undefined>(cellValue: T): T {
  if (!cellValue || typeof cellValue !== 'object') {
    return cellValue
  }

  if (cellValue.f) {
    cellValue.f = normalizeLuckySheetFormula(cellValue.f)
  }

  if (isLuckySheetTextCell(cellValue)) {
    return cellValue
  }

  if (!isNumericLike(cellValue.v)) {
    return cellValue
  }

  cellValue.v = Number(cellValue.v)
  if (!cellValue.ct) {
    cellValue.ct = { fa: 'General', t: 'n' }
  } else {
    if (!cellValue.ct.fa) {
      cellValue.ct.fa = 'General'
    }
    // 日期保留 d；其余数字统一为 n
    if (cellValue.ct.t !== 'd') {
      cellValue.ct.t = 'n'
    }
  }
  // LuckyExcel 对文本/共享字符串会打上 quotePrefix，数字需清除
  if ('qp' in cellValue) {
    delete cellValue.qp
  }

  return cellValue
}
