/**
 * 云盘 Excel「保存-打开」往返回归验证。
 *
 * 对应客户反馈的两个问题：
 * 1. 同列数字与文字混排时，保存后再打开文字全部丢失、只剩数字；
 *    根因是导出时给字体写了 family=1（OOXML 的 Roman 字族），
 *    LuckyExcel 读到后会把含中文的共享字符串按字符拆成富文本（值落到 ct.s），cell.v 为空。
 * 2. 每保存一次单元格就变大；
 *    根因是把 Luckysheet 的像素行高当作磅直接写入，每次往返放大 4/3；
 *    列宽则因为用 worksheet.columns[i] 判空而被整体丢弃。
 *
 * 运行：node scripts/verify-excel-roundtrip.mjs
 */
import assert from 'node:assert/strict'
import { createRequire } from 'node:module'
import { writeFileSync, readFileSync, unlinkSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join, dirname } from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'

const require = createRequire(import.meta.url)
const LuckyExcel = require('luckyexcel')
const root = join(dirname(fileURLToPath(import.meta.url)), '..')

const { exportExcel } = await import(pathToFileURL(join(root, 'src/components/openFile/export.js')).href)
const { normalizeLuckySheetCellValue } = await import(
  pathToFileURL(join(root, 'src/components/openFile/excelFormula.js')).href
)

const ROWS = 5
const ROW_HEIGHT = 20
const COLUMN_LEN = { 0: 72, 2: 120 }

// Luckysheet 运行时的真实数据形态：中文文本为 ct.t='g'，数字为 ct.t='n'
function makeSheet() {
  const data = Array.from({ length: ROWS }, () => Array(3).fill(null))
  data[0][0] = { v: '张三', m: '张三', ct: { fa: 'General', t: 'g' } }
  data[1][0] = { v: 123, m: '123', ct: { fa: 'General', t: 'n' } }
  data[2][0] = { v: '李四', m: '李四', ct: { fa: 'General', t: 'g' } }
  data[3][0] = { v: '混合ABC123', m: '混合ABC123', ct: { fa: 'General', t: 'g' } }
  data[4][0] = { v: '007', m: '007', ct: { fa: '@', t: 's' } }
  return {
    name: 'Sheet1',
    data,
    config: {
      rowlen: Object.fromEntries(Array.from({ length: ROWS }, (_, i) => [i, ROW_HEIGHT])),
      columnlen: { ...COLUMN_LEN }
    }
  }
}

// 导出成 xlsx 再用 LuckyExcel 读回来，还原成下一轮可用的 sheet
async function roundTrip(sheet) {
  const blob = await exportExcel([sheet], 'roundtrip', 1)
  const file = join(tmpdir(), `oa-excel-roundtrip-${Date.now()}-${Math.random()}.xlsx`)
  writeFileSync(file, Buffer.from(await blob.arrayBuffer()))

  const json = await new Promise((resolve, reject) => {
    try {
      LuckyExcel.transformExcelToLucky(readFileSync(file), resolve)
    } catch (error) {
      reject(error)
    }
  })

  try {
    unlinkSync(file)
  } catch (e) {
    // ignore
  }

  const imported = json.sheets[0]
  imported.celldata.forEach((cell) => normalizeLuckySheetCellValue(cell.v))

  const data = Array.from({ length: ROWS }, () => Array(3).fill(null))
  imported.celldata.forEach((cell) => {
    if (cell.r < ROWS && cell.c < 3) {
      data[cell.r][cell.c] = cell.v
    }
  })
  return { name: imported.name, data, config: imported.config }
}

let sheet = makeSheet()

// 连存三次：任何一处换算不互逆都会在第二、三轮暴露出来
for (let round = 1; round <= 3; round++) {
  sheet = await roundTrip(sheet)
  const at = (r) => sheet.data[r][0]

  // 1. 文字不能丢：数字与文字必须能在同一列共存
  assert.equal(at(0).v, '张三', `第 ${round} 次往返后 A1 文字丢失`)
  assert.equal(at(1).v, 123, `第 ${round} 次往返后 A2 数字丢失`)
  assert.equal(at(2).v, '李四', `第 ${round} 次往返后 A3 文字丢失`)
  assert.equal(at(3).v, '混合ABC123', `第 ${round} 次往返后 A4 中英混排文字丢失`)
  assert.equal(at(4).v, '007', `第 ${round} 次往返后 A5 文本格式数字丢失前导零`)

  // 文字不应被拆成富文本片段（ct.s 是 family=1 触发的旧 BUG 特征）
  assert.equal(at(0).ct.s, undefined, `第 ${round} 次往返后 A1 被拆成了富文本`)

  // 2. 行高不能逐次放大
  for (let r = 0; r < ROWS; r++) {
    assert.equal(sheet.config.rowlen[r], ROW_HEIGHT, `第 ${round} 次往返后第 ${r + 1} 行行高漂移`)
  }

  // 3. 列宽不能丢失，也不能错位到相邻列
  assert.notEqual(sheet.config.columnlen, undefined, `第 ${round} 次往返后列宽整体丢失`)
  for (const [col, width] of Object.entries(COLUMN_LEN)) {
    assert.equal(sheet.config.columnlen[col], width, `第 ${round} 次往返后第 ${Number(col) + 1} 列列宽漂移`)
  }
  assert.equal(sheet.config.columnlen[1], undefined, `第 ${round} 次往返后列宽错位到了未设置的 B 列`)
}

console.log('verify-excel-roundtrip: PASS')
