/**
 * 云盘 Excel 单元格数字/文本格式回归验证。
 *
 * 根因：
 * 1. 导出时若把数字以字符串交给 ExcelJS，会写成 t="s" 文本；
 * 2. 未写入 numFmt；
 * 3. LuckyExcel 再导入后缺少 ct.t='n'，Luckysheet 会当成文本，公式无法计算。
 *
 * 运行：node scripts/verify-excel-cell-type.mjs
 */
import assert from 'node:assert/strict'
import { createRequire } from 'node:module'
import { readFileSync, unlinkSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join, dirname } from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'
import { execSync } from 'node:child_process'

const require = createRequire(import.meta.url)
const ExcelJS = require('exceljs')
const LuckyExcel = require('luckyexcel')
const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const helperUrl = pathToFileURL(join(root, 'src/components/openFile/excelFormula.js')).href

const {
  toExcelJsCellValue,
  normalizeLuckySheetCellValue
} = await import(helperUrl)

// --- unit: export payload ---
{
  const numberCell = toExcelJsCellValue({
    ct: { fa: '0.00', t: 'n' },
    v: '1234.5',
    m: '1234.50'
  })
  assert.equal(typeof numberCell.value, 'number')
  assert.equal(numberCell.value, 1234.5)
  assert.equal(numberCell.numFmt, '0.00')

  const textCell = toExcelJsCellValue({
    ct: { fa: '@', t: 's' },
    v: '1234.5',
    m: '1234.5'
  })
  assert.equal(typeof textCell.value, 'string')
  assert.equal(textCell.numFmt, '@')

  const formulaCell = toExcelJsCellValue({
    f: '=A1+A2',
    v: '30',
    ct: { fa: '0', t: 'n' }
  })
  assert.deepEqual(formulaCell.value, { formula: 'A1+A2', result: 30 })
  assert.equal(formulaCell.numFmt, '0')
}

// --- unit: import normalize ---
{
  const imported = {
    v: '10',
    ct: { fa: '0.00' } // LuckyExcel 常缺 t
  }
  normalizeLuckySheetCellValue(imported)
  assert.equal(imported.v, 10)
  assert.equal(imported.ct.t, 'n')
  assert.equal(imported.ct.fa, '0.00')
  assert.equal(imported.qp, undefined)

  const quoted = { v: '10', qp: 1 }
  normalizeLuckySheetCellValue(quoted)
  assert.equal(quoted.v, 10)
  assert.equal(quoted.ct.t, 'n')
  assert.equal('qp' in quoted, false)

  const keepText = { v: '10', ct: { fa: '@', t: 's' }, qp: 1 }
  normalizeLuckySheetCellValue(keepText)
  assert.equal(keepText.v, '10')
  assert.equal(keepText.ct.t, 's')
}

// --- round-trip: number format must not become text ---
async function roundTrip(cell) {
  const file = join(tmpdir(), `oa-excel-type-${Date.now()}-${Math.random()}.xlsx`)
  const wb = new ExcelJS.Workbook()
  const ws = wb.addWorksheet('Sheet1')
  const payload = toExcelJsCellValue(cell)
  const target = ws.getCell('A1')
  target.value = payload.value
  if (payload.numFmt) {
    target.numFmt = payload.numFmt
  }
  await wb.xlsx.writeFile(file)

  const xml = execSync(`unzip -p ${file} xl/worksheets/sheet1.xml`).toString()
  const cellXml = xml.match(/<c r="A1"[\s\S]*?<\/c>/)[0]
  assert.equal(cellXml.includes('t="s"'), false, `number cell must not export as shared string: ${cellXml}`)

  const imported = await new Promise((resolve, reject) => {
    try {
      LuckyExcel.transformExcelToLucky(readFileSync(file), (exportJson) => {
        resolve(exportJson.sheets[0].celldata[0].v)
      })
    } catch (error) {
      reject(error)
    }
  })

  try {
    unlinkSync(file)
  } catch (e) {
    // ignore
  }

  normalizeLuckySheetCellValue(imported)
  return { cellXml, imported }
}

const { imported } = await roundTrip({
  ct: { fa: '0.00', t: 'n' },
  v: '1234.5',
  m: '1234.50'
})

assert.equal(typeof imported.v, 'number')
assert.equal(imported.v, 1234.5)
assert.equal(imported.ct.t, 'n')
assert.notEqual(imported.ct.fa, '@')
assert.equal(imported.qp, undefined)

console.log('verify-excel-cell-type: PASS')
