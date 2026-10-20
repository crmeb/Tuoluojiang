/**
 * 读取表格文件的第一个工作表，返回按行排列的字符串二维数组。
 *
 * - .xlsx 用 exceljs 解析（项目统一的 Excel 库）；
 * - .csv 用 papaparse 解析；
 * - .xls（Excel 97-2003）exceljs 不支持，按需懒加载 SheetJS 解析，不进入其他页面的包。
 *
 * 输出约定沿用原 `sheet_to_csv` + 按行切分的结果：行列范围取工作表的已用区域，
 * 每行列数相同，空单元格为 ''；只去掉区域末尾的整行空行（exceljs 会把带格式的空行算进区域）。
 * 与原实现不同的是单元格内容里的逗号、引号、换行不会再把一行拆错。
 */
import Papa from 'papaparse'

type Row = string[]

const pad = (n: number) => String(n).padStart(2, '0')

/** Excel 日期没有时区概念，两个库都按 UTC 还原成 Date，这里按 UTC 取值避免偏移 8 小时 */
function formatExcelDate(date: Date): string {
  if (Number.isNaN(date.getTime())) return ''
  const ymd = `${date.getUTCFullYear()}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())}`
  const h = date.getUTCHours(), m = date.getUTCMinutes(), s = date.getUTCSeconds()
  return h || m || s ? `${ymd} ${pad(h)}:${pad(m)}:${pad(s)}` : ymd
}

/** 单元格值转文本：兼容 exceljs 的富文本 / 公式 / 超链接 / 错误对象 */
function cellToText(value: unknown): string {
  if (value === null || value === undefined) return ''
  if (value instanceof Date) return formatExcelDate(value)
  // 去掉二进制浮点误差（如 0.30000000000000004），与 Excel 显示一致
  if (typeof value === 'number') return String(Number(value.toPrecision(15)))
  if (typeof value === 'boolean') return value ? 'TRUE' : 'FALSE'
  if (typeof value === 'object') {
    const v = value as Record<string, any>
    if (Array.isArray(v.richText)) return v.richText.map((t: { text: string }) => t.text).join('')
    if ('result' in v) return cellToText(v.result)
    if ('formula' in v || 'sharedFormula' in v) return ''
    if ('text' in v) return cellToText(v.text)
    if ('error' in v) return String(v.error)
  }
  return String(value)
}

function trimTrailingEmptyRows(rows: Row[]): Row[] {
  let end = rows.length
  while (end > 0 && rows[end - 1].every((cell) => cell === '')) end--
  return rows.slice(0, end)
}

async function readXlsx(buffer: ArrayBuffer): Promise<Row[]> {
  const { default: ExcelJS } = await import('exceljs')
  const workbook = new ExcelJS.Workbook()
  await workbook.xlsx.load(buffer)
  const sheet = workbook.worksheets[0]
  if (!sheet || !sheet.actualRowCount) return []
  const { top, left, bottom, right } = sheet.dimensions
  const rows: Row[] = []
  for (let r = top; r <= bottom; r++) {
    const row = sheet.getRow(r)
    const cells: Row = []
    // 合并单元格只有左上角有值，其余返回 master 的值；与 SheetJS 一致只保留左上角
    for (let c = left; c <= right; c++) {
      const cell = row.getCell(c)
      cells.push(cell.isMerged && cell.master.address !== cell.address ? '' : cellToText(cell.value))
    }
    rows.push(cells)
  }
  return rows
}

async function readXls(buffer: ArrayBuffer): Promise<Row[]> {
  const XLSX = await import('xlsx')
  const workbook = XLSX.read(buffer, { type: 'array', cellDates: true, UTC: true })
  const sheet = workbook.Sheets[workbook.SheetNames[0]]
  if (!sheet) return []
  const rows = XLSX.utils.sheet_to_json<unknown[]>(sheet, { header: 1, raw: true, defval: null, blankrows: true })
  const width = Math.max(0, ...rows.map((row) => row.length))
  return rows.map((row) => Array.from({ length: width }, (_, i) => cellToText(row[i])))
}

function readCsv(file: File): Promise<Row[]> {
  return new Promise((resolve, reject) => {
    Papa.parse<string[]>(file, {
      skipEmptyLines: false,
      complete: (result) => {
        const width = Math.max(0, ...result.data.map((row) => row.length))
        resolve(result.data.map((row) => Array.from({ length: width }, (_, i) => row[i] ?? '')))
      },
      error: reject,
    })
  })
}

export async function readFirstSheetRows(file: File): Promise<Row[]> {
  const ext = file.name.split('.').pop()?.toLowerCase()
  let rows: Row[]
  if (ext === 'csv') rows = await readCsv(file)
  else if (ext === 'xls') rows = await readXls(await file.arrayBuffer())
  else rows = await readXlsx(await file.arrayBuffer())
  return trimTrailingEmptyRows(rows)
}
