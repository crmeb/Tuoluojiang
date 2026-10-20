/**
 * 把二维数组导出为单工作表的 .xlsx（exceljs 实现）。
 *
 * 入参沿用原 SheetJS 的配置结构，便于调用方不改动：
 * - merges：`{ s: { r, c }, e: { r, c } }`，0 起始；
 * - cols：`{ wpx }`（像素）/ `{ wch }`（字符数）/ `{ width }`（Excel 原始列宽）；
 * - rows：`{ hpx }`（像素）/ `{ hpt }`（磅），下标即行号（0 起始）。
 * 列宽 / 行高换算与原 SheetJS 写出的结果逐项对照过：最大字符宽按 6px 计、列宽取 1/256 精度，
 * 行高的 hpx 按 96dpi 直接等同于磅值，保证导出文件的尺寸不变。
 */
export interface SheetMerge {
  s: { r: number; c: number }
  e: { r: number; c: number }
}
export interface SheetCol {
  wpx?: number
  wch?: number
  width?: number
}
export interface SheetRow {
  hpx?: number
  hpt?: number
}
export interface SheetOptions {
  merges?: SheetMerge[]
  cols?: (SheetCol | null | undefined)[]
  rows?: (SheetRow | null | undefined)[]
  sheetName?: string
}

const MAX_DIGIT_WIDTH = 6

const round256 = (n: number) => Math.round(n * 256) / 256

/** 字符数 → Excel 列宽（SheetJS 的 char2width） */
const charToWidth = (wch: number) => round256((wch * MAX_DIGIT_WIDTH + 5) / MAX_DIGIT_WIDTH)
/** 像素 → 字符数，保留两位小数（SheetJS 的 px2char） */
const pxToChar = (px: number) => Math.floor(((px - 5) / MAX_DIGIT_WIDTH) * 100 + 0.5) / 100

function toColumnWidth(col: SheetCol): number | undefined {
  if (col.width != null) return col.width
  if (col.wpx != null) return charToWidth(pxToChar(col.wpx))
  if (col.wch != null) return charToWidth(col.wch)
  return undefined
}

function toRowHeight(row: SheetRow): number | undefined {
  if (row.hpt != null) return row.hpt
  if (row.hpx != null) return row.hpx
  return undefined
}

export async function aoaToXlsxBlob(data: unknown[][], options: SheetOptions = {}): Promise<Blob> {
  const { default: ExcelJS } = await import('exceljs')
  const workbook = new ExcelJS.Workbook()
  const sheet = workbook.addWorksheet(options.sheetName || 'sheet1')

  data.forEach((values, r) => {
    const row = sheet.getRow(r + 1)
    values.forEach((value, c) => {
      // 与 aoa_to_sheet 一致：null / undefined 不生成单元格
      if (value !== null && value !== undefined) row.getCell(c + 1).value = value as any
    })
  })

  options.cols?.forEach((col, i) => {
    const width = col && toColumnWidth(col)
    if (width != null) sheet.getColumn(i + 1).width = width
  })
  options.rows?.forEach((row, i) => {
    const height = row && toRowHeight(row)
    if (height != null) sheet.getRow(i + 1).height = height
  })
  options.merges?.forEach(({ s, e }) => {
    sheet.mergeCells(s.r + 1, s.c + 1, e.r + 1, e.c + 1)
  })

  const buffer = await workbook.xlsx.writeBuffer()
  return new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
}
